<?php

namespace App\Domain\RecurringExpenses\Actions;

use App\Domain\RecurringExpenses\DTOs\CreateRecurringExpenseData;
use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Models\User;

class CreateRecurringExpenseAction
{
    public function execute(CreateRecurringExpenseData $data, User $user): RecurringExpense
    {
        // Resolve payable and verify ownership
        $payable = $data->payable_type::find($data->payable_id);

        if (! $payable || $payable->user_id !== $user->id) {
            abort(403, 'This resource does not belong to you.');
        }

        return RecurringExpense::create([
            'user_id' => $user->id,
            'payable_type' => $data->payable_type,
            'payable_id' => $data->payable_id,
            'amount' => $data->amount,
            'description' => $data->description,
            'category_id' => $data->category_id,
            'frequency' => $data->frequency,
            'starts_at' => $data->starts_at,
            'ends_at' => $data->ends_at,
            'is_active' => true,
        ]);
    }
}
