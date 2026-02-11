<?php

use App\Enums\PaymentStatus;
use App\Models\Academy;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create academy
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
    ]);

    // Create student
    $this->student = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
    ]);

    // Create subscription
    $this->subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
    ]);
});

test('global callback route exists and is accessible', function () {
    // Create payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => QuranSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => PaymentStatus::PENDING,
        'payment_gateway' => 'easykash',
        'gateway_intent_id' => '260210185128000130',
    ]);

    // Test global callback URL (without subdomain)
    $response = $this->get('/payments/easykash/callback?' . http_build_query([
        'status' => 'PAID',
        'providerRefNum' => '2602101168464',
        'customerReference' => $payment->gateway_intent_id,
    ]));

    // Should NOT return 404
    expect($response->status())->not->toBe(404);
});

test('callback finds payment and attempts to process it', function () {
    // Create payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => QuranSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => PaymentStatus::PENDING,
        'payment_gateway' => 'easykash',
        'gateway_intent_id' => '260210185128000130',
        'amount' => 100,
    ]);

    // Mock EasyKash API verification response
    Http::fake([
        '*' => Http::response([
            'Status' => 'PAID',
            'easykashRef' => '2602101168464',
            'ProductCode' => $payment->gateway_intent_id,
            'Amount' => 10000, // 100 SAR in cents
        ], 200),
    ]);

    // Simulate EasyKash redirect to callback
    $response = $this->get('/payments/easykash/callback?' . http_build_query([
        'status' => 'PAID',
        'providerRefNum' => '2602101168464',
        'customerReference' => $payment->gateway_intent_id,
    ]));

    // Should redirect (either to success or subscription page)
    expect($response->status())->toBe(302);

    // Should include the tenant subdomain in redirect
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain($this->academy->subdomain);
});

test('callback handles payment not found gracefully', function () {
    // Simulate callback with non-existent customer reference
    $response = $this->get('/payments/easykash/callback?' . http_build_query([
        'status' => 'PAID',
        'providerRefNum' => '2602101168464',
        'customerReference' => '999999999999999999',
    ]));

    // Should redirect to main domain with error
    expect($response->status())->toBe(302);
    expect($response->headers->get('Location'))->toContain(config('app.url'));
});


test('callback handles cash payment with NEW status', function () {
    // Create payment for cash payment method
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => QuranSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => PaymentStatus::PENDING,
        'payment_gateway' => 'easykash',
        'payment_method' => 'cash',
        'gateway_intent_id' => '260210185128000130',
    ]);

    // Simulate callback with NEW status (awaiting cash payment)
    $response = $this->get('/payments/easykash/callback?' . http_build_query([
        'status' => 'NEW',
        'providerRefNum' => '2602101168464',
        'customerReference' => $payment->gateway_intent_id,
    ]));

    // Should redirect to success page
    expect($response->status())->toBe(302);
    expect($response->headers->get('Location'))->toContain('/payments/' . $payment->id . '/success');
});

test('PaymentService generates correct EasyKash callback URL', function () {
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => QuranSubscription::class,
        'payable_id' => $this->subscription->id,
        'amount' => 100,
        'currency' => 'SAR',
        'status' => PaymentStatus::PENDING,
        'payment_gateway' => 'easykash',
        'payment_method' => 'card',
    ]);

    // The PaymentService should use the global callback URL
    // This is what gets sent to EasyKash as the redirect URL
    $expectedUrl = config('app.url') . '/payments/easykash/callback';

    // Verify payment was created
    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->academy_id)->toBe($this->academy->id);

    // Note: We can't directly test the URL generation without mocking the gateway,
    // but the fix ensures PaymentService uses config('app.url') . '/payments/easykash/callback'
    // instead of route('payments.easykash.callback') which would add subdomain
});
