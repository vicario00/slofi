<?php

namespace App\Jobs;

use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Installments\Models\InstallmentPlan;
use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Domain\Transactions\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessScheduledTransactionsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $today = Carbon::today();
        $this->processRecurringExpenses($today);
        $this->processInstallments($today);
    }

    protected function processRecurringExpenses(Carbon $today): void
    {
        RecurringExpense::active()->get()->each(function (RecurringExpense $expense) use ($today) {
            if ($expense->ends_at && $today->gt($expense->ends_at)) {
                $expense->update(['is_active' => false]);

                return;
            }

            if (! $this->isRecurringExpenseDue($expense, $today)) {
                return;
            }

            DB::transaction(function () use ($expense, $today) {
                Transaction::create([
                    'user_id' => $expense->user_id,
                    'payable_type' => $expense->payable_type,
                    'payable_id' => $expense->payable_id,
                    'amount' => $expense->amount,
                    'type' => 'expense',
                    'description' => $expense->description,
                    'category_id' => $expense->category_id,
                    'transacted_at' => $today->toDateString(),
                ]);

                $expense->last_run_at = $today->toDateString();
                $expense->save();
            });
        });
    }

    protected function isRecurringExpenseDue(RecurringExpense $expense, Carbon $today): bool
    {
        $lastRunAt = $expense->last_run_at;

        return match ($expense->frequency) {
            'daily' => true,
            'weekly' => $lastRunAt === null || $today->gte($lastRunAt->copy()->addDays(7)),
            'biweekly' => $lastRunAt === null || $today->gte($lastRunAt->copy()->addDays(14)),
            'monthly' => $this->isMonthlyDue($expense, $today),
            'yearly' => $this->isYearlyDue($expense, $today),
            default => false,
        };
    }

    protected function isMonthlyDue(RecurringExpense $expense, Carbon $today): bool
    {
        $lastRunAt = $expense->last_run_at;
        $targetDay = $expense->starts_at->day;

        // Clamp to last day of month if day doesn't exist in current month
        $dueDay = min($targetDay, $today->daysInMonth);

        if ($today->day !== $dueDay) {
            return false;
        }

        return $lastRunAt === null || $today->gt($lastRunAt);
    }

    protected function isYearlyDue(RecurringExpense $expense, Carbon $today): bool
    {
        $lastRunAt = $expense->last_run_at;
        $startsAt = $expense->starts_at;

        if ($today->month !== $startsAt->month) {
            return false;
        }

        // Clamp to last day of month
        $dueDay = min($startsAt->day, $today->daysInMonth);

        if ($today->day !== $dueDay) {
            return false;
        }

        return $lastRunAt === null || $today->gt($lastRunAt);
    }

    protected function processInstallments(Carbon $today): void
    {
        InstallmentPlan::active()->with('creditCard')->get()->each(function (InstallmentPlan $plan) use ($today) {
            $creditCard = $plan->creditCard;

            if (! $creditCard) {
                return;
            }

            $cutoffDay = $creditCard->cutoff_day;
            // Clamp cutoff_day to last day of month if necessary
            $dueCutoffDay = min($cutoffDay, $today->daysInMonth);

            if ($today->day !== $dueCutoffDay) {
                return;
            }

            DB::transaction(function () use ($plan, $today) {
                $nextInstallment = $plan->paid_installments + 1;

                Transaction::create([
                    'user_id' => $plan->user_id,
                    'payable_type' => CreditCard::class,
                    'payable_id' => $plan->credit_card_id,
                    'amount' => $plan->installment_amount,
                    'type' => 'expense',
                    'description' => $plan->description." (cuota {$nextInstallment}/{$plan->total_installments})",
                    'transacted_at' => $today->toDateString(),
                ]);

                $plan->paid_installments += 1;
                $plan->save();
            });
        });
    }
}
