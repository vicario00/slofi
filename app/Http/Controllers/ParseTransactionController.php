<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\Exceptions\ParserUnavailableException;
use App\Domain\Transactions\Resources\ParsedTransactionDraftResource;
use App\Domain\Transactions\Services\ParserServiceInterface;
use App\Http\Requests\ParseTransactionRequest;
use Illuminate\Http\JsonResponse;

class ParseTransactionController extends Controller
{
    public function __construct(
        private readonly ParserServiceInterface $parserService,
    ) {}

    public function __invoke(ParseTransactionRequest $request): ParsedTransactionDraftResource|JsonResponse
    {
        try {
            $draft = $this->parserService->parse($request->raw_text);
        } catch (ParserUnavailableException) {
            return response()->json(['message' => 'Parser service unavailable.'], 503);
        }

        return new ParsedTransactionDraftResource($draft);
    }
}
