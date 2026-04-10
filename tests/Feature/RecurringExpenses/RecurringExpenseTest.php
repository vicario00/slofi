<?php

namespace Tests\Feature\RecurringExpenses;

use App\Domain\Accounts\Models\Account;
use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function createAccount(User $user, array $overrides = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Checking',
            'type' => 'checking',
            'balance' => '5000.00',
            'currency' => 'MXN',
            'icon' => null,
            'color' => '#000000',
        ], $overrides));
    }

    private function createRecurringExpense(User $user, Account $account, array $overrides = []): RecurringExpense
    {
        return RecurringExpense::create(array_merge([
            'user_id' => $user->id,
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'amount' => '500.00',
            'description' => 'Netflix',
            'frequency' => 'monthly',
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ], $overrides));
    }

    public function test_can_create_recurring_expense(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);

        $response = $this->postJson('/api/recurring-expenses', [
            'description' => 'Netflix',
            'amount' => 250.00,
            'frequency' => 'monthly',
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'starts_at' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['description' => 'Netflix'])
            ->assertJsonFragment(['frequency' => 'monthly']);

        $this->assertDatabaseHas('recurring_expenses', [
            'description' => 'Netflix',
            'user_id' => $user->id,
            'is_active' => 1,
        ]);
    }

    public function test_can_list_recurring_expenses(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);
        $this->createRecurringExpense($user, $account, ['description' => 'Netflix']);
        $this->createRecurringExpense($user, $account, ['description' => 'Spotify']);

        $response = $this->getJson('/api/recurring-expenses');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_cancel_recurring_expense(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);
        $expense = $this->createRecurringExpense($user, $account);

        $response = $this->deleteJson("/api/recurring-expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['is_active' => false]);

        // Not deleted from DB
        $this->assertDatabaseHas('recurring_expenses', ['id' => $expense->id, 'is_active' => 0]);
    }

    public function test_cannot_access_other_users_expense(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $account = $this->createAccount($owner);
        $expense = $this->createRecurringExpense($owner, $account);

        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/recurring-expenses/{$expense->id}");

        $response->assertStatus(403);
    }

    public function test_invalid_frequency_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);

        $response = $this->postJson('/api/recurring-expenses', [
            'description' => 'Bad Expense',
            'amount' => 100.00,
            'frequency' => 'hourly',
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'starts_at' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }

    public function test_can_create_recurring_expense_with_ends_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);
        $startsAt = now()->toDateString();
        $endsAt = now()->addYear()->toDateString();

        $response = $this->postJson('/api/recurring-expenses', [
            'description' => 'Gym with expiry',
            'amount' => 400.00,
            'frequency' => 'monthly',
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.ends_at', $endsAt);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $account = $this->createAccount($user);

        $response = $this->postJson('/api/recurring-expenses', [
            'description' => 'Invalid dates',
            'amount' => 100.00,
            'frequency' => 'monthly',
            'payable_type' => Account::class,
            'payable_id' => $account->id,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-05-01', // before starts_at
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    }
}
