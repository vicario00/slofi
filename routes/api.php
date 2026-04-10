<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TagRuleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Accounts
    Route::apiResource('accounts', AccountController::class);

    // Credit Cards
    Route::apiResource('credit-cards', CreditCardController::class);
    Route::get('credit-cards/{credit_card}/balance', [CreditCardController::class, 'balance']);

    // Tags
    Route::apiResource('tags', TagController::class);
    Route::apiResource('tags.rules', TagRuleController::class)->shallow();

    // Transactions
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show']);

    // Categories
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'destroy']);

    // Recurring Expenses
    Route::apiResource('recurring-expenses', RecurringExpenseController::class)
        ->only(['index', 'store', 'show', 'destroy']);

    // Installment Plans
    Route::apiResource('installment-plans', InstallmentPlanController::class)
        ->only(['index', 'store', 'show', 'destroy']);
});
