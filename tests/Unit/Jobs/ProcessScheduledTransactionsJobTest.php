<?php

namespace Tests\Unit\Jobs;

use App\Domain\Accounts\Models\Account;
use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Installments\Models\InstallmentPlan;
use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Jobs\ProcessScheduledTransactionsJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessScheduledTransactionsJobTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createAccount(User $user): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => 'Checking',
            'type' => 'checking',
            'balance' => '5000.00',
            'currency' => 'MXN',
            'icon' => null,
            'color' => '#000000',
        ]);
    }

    private function createCreditCard(User $user, int $cutoffDay = 15): CreditCard
    {
        return CreditCard::create([
            'user_id' => $user->id,
            'name' => 'My Visa',
            'last_four' => '1234',
            'cutoff_day' => $cutoffDay,
            'payment_grace_days' => 10,
            'credit_limit' => 10000.00,
            'currency' => 'MXN',
            'color' => '#0000FF',
        ]);
    }

    public function test_processes_monthly_recurring_expense_on_due_day(): void
    {
        $today = Carbon::create(2026, 4, 15); // April 15
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $account = $this->createAccount($user);

        // starts_at: March 15 (same day of month last month), last_run_at = March 15
        RecurringExpense::create([
            'user_id' => $user->id,
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'amount' => '500.00',
            'description' => 'Gym',
            'frequency' => 'monthly',
            'starts_at' => '2026-03-15',
            'last_run_at' => '2026-03-15',
            'is_active' => true,
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Gym',
            'amount' => '500.00',
        ]);

        // Verify last_run_at updated — load fresh model to check the date cast
        $expense = RecurringExpense::where('description', 'Gym')->first();
        $this->assertEquals('2026-04-15', $expense->last_run_at->toDateString());

        Carbon::setTestNow();
    }

    public function test_skips_recurring_expense_not_due(): void
    {
        $today = Carbon::create(2026, 4, 15);
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $account = $this->createAccount($user);

        // last_run_at = today: already ran today
        RecurringExpense::create([
            'user_id' => $user->id,
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'amount' => '500.00',
            'description' => 'Gym',
            'frequency' => 'monthly',
            'starts_at' => '2026-03-15',
            'last_run_at' => '2026-04-15', // ran today already
            'is_active' => true,
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $this->assertDatabaseCount('transactions', 0);

        Carbon::setTestNow();
    }

    public function test_processes_installment_on_cutoff_day(): void
    {
        $today = Carbon::create(2026, 4, 15);
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $card = $this->createCreditCard($user, cutoffDay: 15); // cutoff_day = 15 = today

        InstallmentPlan::create([
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'description' => 'Laptop',
            'total_amount' => '1200.00',
            'installment_amount' => '400.00',
            'total_installments' => 3,
            'paid_installments' => 0,
            'starts_at' => '2026-02-15',
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'payable_type' => CreditCard::class,
            'payable_id' => $card->id,
            'amount' => '400.00',
        ]);

        $this->assertDatabaseHas('installment_plans', [
            'description' => 'Laptop',
            'paid_installments' => 1,
        ]);

        Carbon::setTestNow();
    }

    public function test_skips_installment_when_not_cutoff_day(): void
    {
        $today = Carbon::create(2026, 4, 15);
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $card = $this->createCreditCard($user, cutoffDay: 20); // cutoff_day = 20 ≠ today

        InstallmentPlan::create([
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'description' => 'TV',
            'total_amount' => '900.00',
            'installment_amount' => '300.00',
            'total_installments' => 3,
            'paid_installments' => 0,
            'starts_at' => '2026-01-20',
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $this->assertDatabaseCount('transactions', 0);

        Carbon::setTestNow();
    }

    public function test_marks_plan_completed_on_last_installment(): void
    {
        $today = Carbon::create(2026, 4, 15);
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $card = $this->createCreditCard($user, cutoffDay: 15);

        $plan = InstallmentPlan::create([
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'description' => 'Camera',
            'total_amount' => '900.00',
            'installment_amount' => '300.00',
            'total_installments' => 3,
            'paid_installments' => 2, // last one pending
            'starts_at' => '2026-02-15',
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $plan->refresh();

        $this->assertEquals(3, $plan->paid_installments);
        $this->assertTrue($plan->is_completed);

        Carbon::setTestNow();
    }

    public function test_deactivates_expired_recurring_expense(): void
    {
        $today = Carbon::create(2026, 4, 15);
        Carbon::setTestNow($today);

        $user = $this->createUser();
        $account = $this->createAccount($user);

        $expense = RecurringExpense::create([
            'user_id' => $user->id,
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'amount' => '300.00',
            'description' => 'Expired Subscription',
            'frequency' => 'monthly',
            'starts_at' => '2026-01-15',
            'ends_at' => '2026-04-14', // yesterday
            'is_active' => true,
        ]);

        (new ProcessScheduledTransactionsJob)->handle();

        $expense->refresh();

        $this->assertFalse($expense->is_active);
        $this->assertDatabaseCount('transactions', 0);

        Carbon::setTestNow();
    }
}
