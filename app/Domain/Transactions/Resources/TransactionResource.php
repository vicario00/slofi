<?php

namespace App\Domain\Transactions\Resources;

use App\Domain\Categories\Resources\CategoryResource;
use App\Domain\Tags\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'merchant' => $this->merchant,
            'transacted_at' => $this->transacted_at,
            'notes' => $this->notes,
            'transfer_pair_id' => $this->transfer_pair_id,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at,
        ];
    }
}
