<?php

namespace App\Domain\Transactions\Services;

use App\Domain\Transactions\DTOs\ParsedTransactionDraft;
use App\Domain\Transactions\Exceptions\ParserUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ParserService implements ParserServiceInterface
{
    public function __construct(
        private readonly string $parserUrl,
        private readonly bool $parserEnabled,
    ) {}

    public function parse(string $rawText): ParsedTransactionDraft
    {
        if (! $this->parserEnabled) {
            throw new ParserUnavailableException('Parser service is disabled.');
        }

        try {
            $response = Http::timeout(10)->post("{$this->parserUrl}/parse", [
                'text' => $rawText,
            ]);

            if ($response->failed()) {
                throw new ParserUnavailableException('Parser service returned an error.');
            }

            return ParsedTransactionDraft::from($response->json());
        } catch (ConnectionException $e) {
            throw new ParserUnavailableException('Parser service is unreachable.', previous: $e);
        }
    }
}
