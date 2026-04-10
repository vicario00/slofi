<?php

namespace App\Domain\Installments\Actions;

use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Installments\DTOs\CreateInstallmentPlanData;
use App\Domain\Installments\Models\InstallmentPlan;
use App\Models\User;

class CreateInstallmentPlanAction
{
    public function execute(CreateInstallmentPlanData $data, User $user): InstallmentPlan
    {
        // Verify credit card belongs to user
        $creditCard = CreditCard::find($data->credit_card_id);

        if (! $creditCard || $creditCard->user_id !== $user->id) {
            abort(403, 'This credit card does not belong to you.');
        }

        // Calculate installment_amount
        if ($data->installment_amount !== null) {
            $installmentAmount = $data->installment_amount;
        } elseif (fmod($data->total_amount, $data->total_installments) == 0.0) {
            $installmentAmount = $data->total_amount / $data->total_installments;
        } else {
            throw new \InvalidArgumentException(
                'installment_amount is required when total_amount is not evenly divisible'
            );
        }

        return InstallmentPlan::create([
            'user_id' => $user->id,
            'credit_card_id' => $data->credit_card_id,
            'description' => $data->description,
            'total_amount' => $data->total_amount,
            'installment_amount' => $installmentAmount,
            'total_installments' => $data->total_installments,
            'paid_installments' => 0,
            'starts_at' => $data->starts_at,
        ]);
    }
}
