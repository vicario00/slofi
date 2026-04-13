<?php

namespace App\Domain\Transactions\Services;

use App\Domain\Transactions\DTOs\ParsedTransactionDraft;
use App\Domain\Transactions\Exceptions\ParserUnavailableException;

interface ParserServiceInterface
{
    /**
     * Parse free text into a structured transaction draft.
     *
     * @throws ParserUnavailableException
     */
    public function parse(string $rawText): ParsedTransactionDraft;
}
