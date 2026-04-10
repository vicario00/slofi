<?php

namespace App\Domain\Transactions\DTOs;

use Spatie\LaravelData\Data;

class RegisterTransactionData extends Data
{
    public function __construct(
        public readonly string $payable_type,
        public readonly int $payable_id,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $description,
        public readonly string $transacted_at,
        public readonly ?string $merchant = null,
        public readonly ?string $notes = null,
        public readonly ?int $target_payable_id = null,
        public readonly ?int $category_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'payable_type' => ['required', 'in:account,credit_card'],
            'payable_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:income,expense,transfer'],
            'description' => ['required', 'string', 'max:255'],
            'transacted_at' => ['required', 'date'],
            'merchant' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'target_payable_id' => ['required_if:type,transfer', 'nullable', 'integer'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
