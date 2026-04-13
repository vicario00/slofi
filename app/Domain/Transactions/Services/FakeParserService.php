<?php

namespace App\Domain\Transactions\Services;

use App\Domain\Transactions\DTOs\ParsedTransactionDraft;
use App\Domain\Transactions\Exceptions\ParserUnavailableException;

class FakeParserService implements ParserServiceInterface
{
    private bool $shouldFail = false;

    private ParsedTransactionDraft $draft;

    public function __construct(?ParsedTransactionDraft $draft = null)
    {
        $this->draft = $draft ?? $this->defaultDraft();
    }

    public static function returning(ParsedTransactionDraft $draft): self
    {
        return new self($draft);
    }

    public static function unavailable(): self
    {
        $instance = new self;
        $instance->shouldFail = true;

        return $instance;
    }

    public function parse(string $rawText): ParsedTransactionDraft
    {
        if ($this->shouldFail) {
            throw new ParserUnavailableException('Fake: parser unavailable.');
        }

        return $this->draft;
    }

    private function defaultDraft(): ParsedTransactionDraft
    {
        return ParsedTransactionDraft::from([
            'amount' => 150.0,
            'type' => 'expense',
            'merchant' => 'Test Merchant',
            'description' => 'Test purchase',
            'transacted_at' => '2026-04-13',
            'notes' => null,
            'suggested_category_slug' => 'shopping',
            'suggested_tags' => ['test'],
            'confidence' => 0.85,
            'requires_confirmation' => false,
            'inferred_payable_type' => null,
            'inferred_payable_id' => null,
        ]);
    }
}
