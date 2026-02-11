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

/*
|--------------------------------------------------------------------------
| Subdomain-Specific Routes
|--------------------------------------------------------------------------
*/

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Payment Processing Routes
    |--------------------------------------------------------------------------
    */

    // Payment Processing (ID-based)
    Route::get('/courses/{courseId}/payment', [PaymentController::class, 'create'])->name('payments.create')->where('courseId', '[0-9]+');
    Route::post('/courses/{courseId}/payment', [PaymentController::class, 'store'])->name('payments.store')->where('courseId', '[0-9]+');

    // Payment success/failed pages - use closure to bypass automatic model binding
    // This allows manual loading without global scopes in the controller
    Route::get('/payments/{paymentId}/success', function ($subdomain, $paymentId) {
        return app(PaymentController::class)->showSuccess($paymentId);
    })->name('payments.success')->where('paymentId', '[0-9]+');

    Route::get('/payments/{paymentId}/failed', function ($subdomain, $paymentId) {
        return app(PaymentController::class)->showFailed($paymentId);
    })->name('payments.failed')->where('paymentId', '[0-9]+');

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

    // Paymob payment callback
    // Note: Using closure wrapper because direct controller binding [PaymobWebhookController::class, 'callback']
    // causes routing issues with the subdomain parameter. The closure properly passes the payment ID.
    Route::get('/payments/{payment}/callback', function (\Illuminate\Http\Request $request, $subdomain, $payment) {
        return app(PaymobWebhookController::class)->callback($request, $payment);
    })->name('payments.callback');

    // EasyKash callback - REDIRECT to global route to avoid subdomain routing issues
    Route::get('/payments/easykash/callback', function (\Illuminate\Http\Request $request) {
        // Redirect to global callback with all query parameters
        return redirect()->to('/payments/easykash/callback?' . http_build_query($request->query()));
    })->name('payments.easykash.tenant.callback');

    /*
    |--------------------------------------------------------------------------
    | Payment Methods API
    |--------------------------------------------------------------------------
    */

    Route::get('/api/payment-methods/{academy}', [PaymentController::class, 'getPaymentMethods'])->name('api.payment-methods');
});

/*
|--------------------------------------------------------------------------
| Global Routes (No Subdomain) - Defined AFTER subdomain routes
|--------------------------------------------------------------------------
*/

// EasyKash callback (fallback global route - works without subdomain)
// The callback handler uses withoutGlobalScopes() to find payments across all tenants
// This is defined AFTER the subdomain route so subdomain routes take priority
Route::get('/payments/easykash/callback', [EasyKashWebhookController::class, 'callback'])
    ->name('payments.easykash.callback');
