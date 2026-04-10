<?php

namespace App\Domain\RecurringExpenses\Actions;

use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Models\User;

class CancelRecurringExpenseAction
{
    public function execute(RecurringExpense $expense, User $user): RecurringExpense
    {
        abort_if($expense->user_id !== $user->id, 403);

        $expense->is_active = false;
        $expense->save();

        return $expense;
    }
}
