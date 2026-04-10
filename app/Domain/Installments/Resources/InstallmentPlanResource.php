<?php

namespace App\Domain\Installments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'installment_amount' => $this->installment_amount,
            'total_installments' => $this->total_installments,
            'paid_installments' => $this->paid_installments,
            'starts_at' => $this->starts_at?->toDateString(),
            'cancelled_at' => $this->cancelled_at,
            'is_completed' => $this->is_completed,
            'is_active' => $this->is_active,
            'credit_card_id' => $this->credit_card_id,
            'created_at' => $this->created_at,
        ];
    }
}
