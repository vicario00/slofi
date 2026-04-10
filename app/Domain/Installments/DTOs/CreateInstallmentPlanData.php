<?php

namespace App\Domain\Installments\DTOs;

use Spatie\LaravelData\Data;

class CreateInstallmentPlanData extends Data
{
    public function __construct(
        public readonly string $description,
        public readonly float $total_amount,
        public readonly int $total_installments,
        public readonly int $credit_card_id,
        public readonly string $starts_at,
        public readonly ?float $installment_amount = null,
    ) {}

    public static function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'total_installments' => ['required', 'integer', 'min:2', 'max:60'],
            'credit_card_id' => ['required', 'integer', 'exists:credit_cards,id'],
            'starts_at' => ['required', 'date'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
