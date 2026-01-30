<?php

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
| Payment processing, history, and payment gateway integration.
*/

use App\Http\Controllers\EasyKashWebhookController;
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
    Route::get('/payments/{payment}/success', [PaymentController::class, 'showSuccess'])->name('payments.success');
    Route::get('/payments/{payment}/failed', [PaymentController::class, 'showFailed'])->name('payments.failed');

    /*
    |--------------------------------------------------------------------------
    | Payment Management Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');

    /*
    |--------------------------------------------------------------------------
    | Card Tokenization Callback (must be before {payment} wildcard routes)
    |--------------------------------------------------------------------------
    */

    Route::get('/payments/tokenization/callback', [PaymentController::class, 'tokenizationCallback'])
        ->name('payments.tokenization.callback')
        ->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | Payment Flow (Gateway System)
    |--------------------------------------------------------------------------
    */

    Route::post('/payments/{payment}/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');

    // Debug route - test if path is accessible
    Route::get('/payments/{payment}/callback-test', function ($subdomain, $payment) {
        return response()->json(['subdomain' => $subdomain, 'payment' => $payment, 'working' => true]);
    })->name('payments.callback.test');

    // Paymob payment callback - using closure to debug routing issue
    Route::get('/payments/{payment}/callback', function (\Illuminate\Http\Request $request, $subdomain, $payment) {
        \Illuminate\Support\Facades\Log::channel('payments')->info('Callback route reached via closure', [
            'subdomain' => $subdomain,
            'payment' => $payment,
            'all_params' => $request->all(),
        ]);

        return app(PaymobWebhookController::class)->callback($request, $payment);
    })->name('payments.callback');

    // EasyKash tenant-specific callback (for per-academy payment accounts)
    Route::get('/payments/easykash/callback', [EasyKashWebhookController::class, 'callback'])
        ->name('payments.easykash.tenant.callback');

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
    Route::post('easykash', [EasyKashWebhookController::class, 'handle'])->name('webhooks.easykash');
});

// EasyKash redirect callback (user returns after payment)
Route::get('/payments/easykash/callback', [EasyKashWebhookController::class, 'callback'])
    ->name('payments.easykash.callback');
