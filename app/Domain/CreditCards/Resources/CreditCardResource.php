<?php

namespace App\Domain\CreditCards\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_four' => $this->last_four,
            'cutoff_day' => $this->cutoff_day,
            'payment_grace_days' => $this->payment_grace_days,
            'payment_due_date' => $this->payment_due_date,
            'credit_limit' => $this->credit_limit,
            'currency' => $this->currency,
            'color' => $this->color,
            'created_at' => $this->created_at,
        ];
    }
}
