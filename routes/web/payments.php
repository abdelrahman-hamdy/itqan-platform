<?php

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
| Payment processing, history, refunds, and payment gateway integration.
*/

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymobWebhookController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Payment Processing Routes
    |--------------------------------------------------------------------------
    */

    // Payment Processing (ID-based)
    Route::get('/courses/{courseId}/payment', [PaymentController::class, 'create'])->name('payments.create')->where('courseId', '[0-9]+');
    Route::post('/courses/{courseId}/payment', [PaymentController::class, 'store'])->name('payments.store')->where('courseId', '[0-9]+');
    Route::get('/payments/{payment}/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/{payment}/failed', [PaymentController::class, 'failed'])->name('payments.failed');

    /*
    |--------------------------------------------------------------------------
    | Payment Management Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    /*
    |--------------------------------------------------------------------------
    | Payment Flow (Gateway System)
    |--------------------------------------------------------------------------
    */

    Route::post('/payments/{payment}/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
    Route::get('/payments/{payment}/callback', [PaymobWebhookController::class, 'callback'])->name('payments.callback');

    /*
    |--------------------------------------------------------------------------
    | Payment Methods API
    |--------------------------------------------------------------------------
    */

    Route::get('/api/payment-methods/{academy}', [PaymentController::class, 'getPaymentMethods'])->name('api.payment-methods');
});

/*
|--------------------------------------------------------------------------
| Payment Gateway Webhooks (Global - No Subdomain)
|--------------------------------------------------------------------------
*/

// Webhooks (no authentication required - validated via signatures)
// Rate limited to prevent abuse, CSRF excluded since webhooks use signature validation
Route::prefix('webhooks')->middleware(['throttle:60,1'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    // Payment gateway webhooks (validated via HMAC)
    Route::post('paymob', [PaymobWebhookController::class, 'handle'])->name('webhooks.paymob');
});
