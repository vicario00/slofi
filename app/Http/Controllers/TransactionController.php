<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\Actions\RegisterTransactionAction;
use App\Domain\Transactions\DTOs\RegisterTransactionData;
use App\Domain\Transactions\Models\Transaction;
use App\Domain\Transactions\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = QueryBuilder::for(
            Transaction::query()
                ->where('user_id', $request->user()->id)
                ->with('tags')
        )
            ->allowedFilters(
                AllowedFilter::exact('payable_type'),
                AllowedFilter::exact('payable_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('from', fn ($q, $v) => $q->whereDate('transacted_at', '>=', $v)),
                AllowedFilter::callback('to', fn ($q, $v) => $q->whereDate('transacted_at', '<=', $v)),
                AllowedFilter::callback('tag_id', fn ($q, $v) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $v))),
            )
            ->allowedSorts('transacted_at', 'amount', 'created_at')
            ->defaultSort('-transacted_at')
            ->paginate($request->get('per_page', 20));

        return TransactionResource::collection($transactions);
    }

    public function store(Request $request, RegisterTransactionAction $action): JsonResponse
    {
        $data = RegisterTransactionData::from($request);
        $transaction = $action->execute($request->user(), $data);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }

    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);

        return new TransactionResource($transaction->load('tags'));
    }
}
