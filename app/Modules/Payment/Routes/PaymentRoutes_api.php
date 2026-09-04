<?php

use App\Modules\Payment\Http\Controllers\PaymentController;
use App\Modules\Payment\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Modules\Payment\Http\Controllers\PaymentMethodController;

Route::middleware(['identify.tenant', 'auth:sanctum'])->prefix('payment-methods')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index']);
    Route::post('/', [PaymentMethodController::class, 'store']);
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
    Route::post('/{id}/default', [PaymentMethodController::class, 'setDefault']);
});

Route::middleware(['identify.tenant', 'auth:sanctum'])->prefix('payments')->group(function () {
    Route::post('initiate', [PaymentController::class, 'initiate']);
    Route::get('transactions', [PaymentController::class, 'index']);
    Route::get('transactions/{id}', [PaymentController::class, 'show']);
});

Route::post('webhook/{provider}/{tenant}', [WebhookController::class, 'handle'])->name('payment.webhook');
