<?php

namespace App\Domain\Transactions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParsedTransactionDraftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'amount' => $this->resource->amount,
            'type' => $this->resource->type,
            'merchant' => $this->resource->merchant,
            'description' => $this->resource->description,
            'transacted_at' => $this->resource->transacted_at,
            'notes' => $this->resource->notes,
            'suggested_category_slug' => $this->resource->suggested_category_slug,
            'suggested_tags' => $this->resource->suggested_tags,
            'confidence' => $this->resource->confidence,
            'requires_confirmation' => $this->resource->requires_confirmation,
            'inferred_payable_type' => $this->resource->inferred_payable_type,
            'inferred_payable_id' => $this->resource->inferred_payable_id,
        ];
    }
}
