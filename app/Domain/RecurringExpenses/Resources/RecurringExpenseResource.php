<?php

namespace App\Domain\RecurringExpenses\Resources;

use App\Domain\Categories\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'last_run_at' => $this->last_run_at?->toDateString(),
            'is_active' => $this->is_active,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at,
        ];
    }
}
