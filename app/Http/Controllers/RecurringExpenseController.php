<?php

namespace App\Http\Controllers;

use App\Domain\RecurringExpenses\Actions\CancelRecurringExpenseAction;
use App\Domain\RecurringExpenses\Actions\CreateRecurringExpenseAction;
use App\Domain\RecurringExpenses\DTOs\CreateRecurringExpenseData;
use App\Domain\RecurringExpenses\Models\RecurringExpense;
use App\Domain\RecurringExpenses\Resources\RecurringExpenseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecurringExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $expenses = RecurringExpense::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->get();

        return RecurringExpenseResource::collection($expenses);
    }

    public function store(Request $request, CreateRecurringExpenseAction $action): JsonResponse
    {
        $data = CreateRecurringExpenseData::from($request);
        $expense = $action->execute($data, $request->user());

        return (new RecurringExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(Request $request, RecurringExpense $recurringExpense): RecurringExpenseResource
    {
        abort_if($recurringExpense->user_id !== $request->user()->id, 403);

        return new RecurringExpenseResource($recurringExpense->load('category'));
    }

    public function destroy(Request $request, RecurringExpense $recurringExpense, CancelRecurringExpenseAction $action): RecurringExpenseResource
    {
        $expense = $action->execute($recurringExpense, $request->user());

        return new RecurringExpenseResource($expense);
    }
}
