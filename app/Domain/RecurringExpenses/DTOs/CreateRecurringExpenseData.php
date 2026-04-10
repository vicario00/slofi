<?php

namespace App\Domain\RecurringExpenses\DTOs;

use Spatie\LaravelData\Data;

class CreateRecurringExpenseData extends Data
{
    public function __construct(
        public readonly string $description,
        public readonly float $amount,
        public readonly string $frequency,
        public readonly string $payable_type,
        public readonly int $payable_id,
        public readonly string $starts_at,
        public readonly ?int $category_id = null,
        public readonly ?string $ends_at = null,
    ) {}

    public static function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'in:daily,weekly,biweekly,monthly,yearly'],
            'payable_type' => ['required', 'string', 'in:App\Domain\Accounts\Models\Account,App\Domain\CreditCards\Models\CreditCard'],
            'payable_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
