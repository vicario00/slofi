<?php

namespace App\Domain\Transactions\DTOs;

use Spatie\LaravelData\Data;

class ParsedTransactionDraft extends Data
{
    public function __construct(
        public readonly ?float $amount,
        public readonly ?string $type,
        public readonly ?string $merchant,
        public readonly ?string $description,
        public readonly ?string $transacted_at,
        public readonly ?string $notes,
        public readonly ?string $suggested_category_slug,
        public readonly array $suggested_tags = [],
        public readonly float $confidence = 0.0,
        public readonly bool $requires_confirmation = true,
        public readonly ?string $inferred_payable_type = null,
        public readonly ?int $inferred_payable_id = null,
    ) {}
}
