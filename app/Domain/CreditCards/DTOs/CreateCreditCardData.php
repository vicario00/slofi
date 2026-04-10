<?php

namespace App\Domain\CreditCards\DTOs;

use Spatie\LaravelData\Data;

class CreateCreditCardData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $cutoff_day,
        public readonly int $payment_grace_days,
        public readonly float $credit_limit,
        public readonly string $currency = 'MXN',
        public readonly ?string $last_four = null,
        public readonly ?string $color = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cutoff_day' => ['required', 'integer', 'min:1', 'max:28'],
            'payment_grace_days' => ['required', 'integer', 'min:1', 'max:45'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'currency' => ['string', 'size:3'],
            'last_four' => ['nullable', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'color' => ['nullable', 'string'],
        ];
    }
}
