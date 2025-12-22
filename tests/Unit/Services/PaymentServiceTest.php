<?php

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Contracts\Payment\SupportsRefunds;
use App\Enums\NotificationType;
use App\Enums\PaymentResultStatus;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentStateMachine;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('PaymentService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->user = User::factory()->student()->forAcademy($this->academy)->create();

        $this->gatewayManager = Mockery::mock(PaymentGatewayManager::class);
        $this->stateMachine = new PaymentStateMachine();
        $this->service = new PaymentService($this->gatewayManager, $this->stateMachine);

        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('processPayment()', function () {
        it('processes payment successfully with pending status', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::pending(
                transactionId: 'txn_123',
                redirectUrl: 'https://payment.gateway/redirect',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('success', true)
                ->toHaveKey('pending', true)
                ->toHaveKey('requires_redirect', true)
                ->toHaveKey('redirect_url', 'https://payment.gateway/redirect');

            $payment->refresh();
            expect($payment->gateway_intent_id)->toBe('txn_123')
                ->and($payment->redirect_url)->toBe('https://payment.gateway/redirect');
        });

        it('processes payment successfully with immediate success', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_123',
                gatewayOrderId: 'order_456',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            NotificationService::shouldReceive('make')
                ->andReturnSelf();

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('success', true)
                ->toHaveKey('data')
                ->and($response['data']['transaction_id'])->toBe('txn_123');

            $payment->refresh();
            expect($payment->status)->toBe('success')
                ->and($payment->transaction_id)->toBe('txn_123')
                ->and($payment->gateway_order_id)->toBe('order_456')
                ->and($payment->paid_at)->not->toBeNull();
        });

        it('uses default gateway when payment has no gateway specified', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => null,
            ]);

            config(['payments.default' => 'paymob']);

            $result = PaymentResult::pending(transactionId: 'txn_123');

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $response = $this->service->processPayment($payment);

            expect($response)->toHaveKey('success', true);
        });

        it('handles gateway not configured error', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(false);

            Log::shouldReceive('channel')
                ->with('payments')
                ->andReturnSelf();
            Log::shouldReceive('error');

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('success', false)
                ->toHaveKey('error')
                ->and($response['success'])->toBeFalse();
        });

        it('handles PaymentException gracefully', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $exception = PaymentException::processingError('Test error', 'خطأ في الاختبار');

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andThrow($exception);

            Log::shouldReceive('channel')
                ->with('payments')
                ->andReturnSelf();
            Log::shouldReceive('error');

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('success', false)
                ->toHaveKey('error')
                ->toHaveKey('error_code')
                ->and($response['error'])->toBe('خطأ في الاختبار');
        });

        it('handles generic exception', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andThrow(new \Exception('Unexpected error'));

            Log::shouldReceive('channel')
                ->with('payments')
                ->andReturnSelf();
            Log::shouldReceive('error');

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('success', false)
                ->toHaveKey('error')
                ->and($response['error'])->toBe('حدث خطأ أثناء معالجة الدفع');
        });

        it('logs payment attempt', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::pending(transactionId: 'txn_123');

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $this->service->processPayment($payment);

            $auditLog = DB::table('payment_audit_logs')
                ->where('payment_id', $payment->id)
                ->where('event_type', 'payment_attempt')
                ->first();

            expect($auditLog)->not->toBeNull();
        });

        it('includes iframe URL when present in result', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::pending(
                transactionId: 'txn_123',
                iframeUrl: 'https://payment.gateway/iframe'
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $response = $this->service->processPayment($payment);

            expect($response)
                ->toHaveKey('requires_iframe', true)
                ->toHaveKey('iframe_url', 'https://payment.gateway/iframe');

            $payment->refresh();
            expect($payment->iframe_url)->toBe('https://payment.gateway/iframe');
        });
    });

    describe('processSubscriptionRenewal()', function () {
        it('processes subscription renewal successfully', function () {
            $quranSubscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->user->id,
            ]);

            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'payable_type' => QuranSubscription::class,
                'payable_id' => $quranSubscription->id,
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_renewal_123',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            Log::shouldReceive('channel')
                ->with('payments')
                ->andReturnSelf();
            Log::shouldReceive('info');

            NotificationService::shouldReceive('make')
                ->andReturnSelf();

            $response = $this->service->processSubscriptionRenewal($payment);

            expect($response)->toHaveKey('success', true);

            $payment->refresh();
            expect($payment->notes)->toContain('Auto-renewal processed');
        });

        it('handles renewal failure', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->andThrow(new \Exception('Gateway error'));

            Log::shouldReceive('channel')
                ->with('payments')
                ->andReturnSelf();
            Log::shouldReceive('info');
            Log::shouldReceive('error');

            $response = $this->service->processSubscriptionRenewal($payment);

            expect($response)
                ->toHaveKey('success', false)
                ->toHaveKey('error')
                ->and($response['error'])->toBe('فشل في تجديد الاشتراك تلقائياً');
        });
    });

    describe('verifyPayment()', function () {
        it('verifies payment successfully', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'transaction_id' => 'txn_123',
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_123',
                rawResponse: ['verified' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('verifyPayment')
                ->with('txn_123', [])
                ->andReturn($result);

            $verifiedResult = $this->service->verifyPayment($payment);

            expect($verifiedResult->isSuccessful())->toBeTrue()
                ->and($verifiedResult->transactionId)->toBe('txn_123');
        });

        it('uses gateway_intent_id if transaction_id is null', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'transaction_id' => null,
                'gateway_intent_id' => 'intent_789',
            ]);

            $result = PaymentResult::success(transactionId: 'intent_789');

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('verifyPayment')
                ->with('intent_789', [])
                ->andReturn($result);

            $verifiedResult = $this->service->verifyPayment($payment);

            expect($verifiedResult->isSuccessful())->toBeTrue();
        });

        it('returns failed result when no transaction ID exists', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'transaction_id' => null,
                'gateway_intent_id' => null,
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $result = $this->service->verifyPayment($payment);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errorCode)->toBe('NO_TRANSACTION_ID');
        });
    });

    describe('refund()', function () {
        it('processes full refund successfully', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'success',
                'payment_gateway' => 'paymob',
                'transaction_id' => 'txn_123',
            ]);

            $gateway = Mockery::mock(PaymentGatewayInterface::class, SupportsRefunds::class);

            $result = PaymentResult::success(
                transactionId: 'refund_txn_456',
                rawResponse: ['refund' => 'success']
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($gateway);

            $gateway->shouldReceive('refund')
                ->with('txn_123', null, null)
                ->andReturn($result);

            $refundResult = $this->service->refund($payment);

            expect($refundResult->isSuccessful())->toBeTrue();

            $payment->refresh();
            expect($payment->status)->toBe('refunded')
                ->and($payment->refunded_amount)->toBe(10000)
                ->and($payment->refunded_at)->not->toBeNull();

            $auditLog = DB::table('payment_audit_logs')
                ->where('payment_id', $payment->id)
                ->where('event_type', 'payment_refunded')
                ->first();

            expect($auditLog)->not->toBeNull();
        });

        it('processes partial refund successfully', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'success',
                'payment_gateway' => 'paymob',
                'transaction_id' => 'txn_123',
            ]);

            $gateway = Mockery::mock(PaymentGatewayInterface::class, SupportsRefunds::class);

            $result = PaymentResult::success(
                transactionId: 'refund_txn_456',
                rawResponse: ['refund' => 'success']
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($gateway);

            $gateway->shouldReceive('refund')
                ->with('txn_123', 5000, 'Partial refund')
                ->andReturn($result);

            $refundResult = $this->service->refund($payment, 5000, 'Partial refund');

            expect($refundResult->isSuccessful())->toBeTrue();

            $payment->refresh();
            expect($payment->status)->toBe('partially_refunded')
                ->and($payment->refunded_amount)->toBe(5000);
        });

        it('rejects refund when status does not allow refund', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'transaction_id' => 'txn_123',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $result = $this->service->refund($payment);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errorCode)->toBe('REFUND_NOT_ALLOWED');
        });

        it('rejects refund when gateway does not support refunds', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'success',
                'payment_gateway' => 'paymob',
                'transaction_id' => 'txn_123',
            ]);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $result = $this->service->refund($payment);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errorCode)->toBe('REFUND_NOT_SUPPORTED');
        });

        it('rejects refund when no transaction ID exists', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'success',
                'payment_gateway' => 'paymob',
                'transaction_id' => null,
            ]);

            $gateway = Mockery::mock(PaymentGatewayInterface::class, SupportsRefunds::class);

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($gateway);

            $result = $this->service->refund($payment);

            expect($result->isFailed())->toBeTrue()
                ->and($result->errorCode)->toBe('NO_TRANSACTION_ID');
        });
    });

    describe('getAvailablePaymentMethods()', function () {
        it('returns available payment methods from configured gateways', function () {
            $gateway1 = Mockery::mock(PaymentGatewayInterface::class);
            $gateway1->shouldReceive('getSupportedMethods')
                ->andReturn(['card', 'wallet']);

            $this->gatewayManager->shouldReceive('getConfiguredGateways')
                ->andReturn([
                    'paymob' => $gateway1,
                ]);

            $methods = $this->service->getAvailablePaymentMethods();

            expect($methods)
                ->toHaveKey('paymob_card')
                ->toHaveKey('paymob_wallet')
                ->and($methods['paymob_card']['name'])->toBe('بطاقة ائتمانية')
                ->and($methods['paymob_wallet']['name'])->toBe('محفظة إلكترونية')
                ->and($methods['paymob_card']['gateway'])->toBe('paymob')
                ->and($methods['paymob_card']['method'])->toBe('card');
        });

        it('returns multiple methods from multiple gateways', function () {
            $gateway1 = Mockery::mock(PaymentGatewayInterface::class);
            $gateway1->shouldReceive('getSupportedMethods')
                ->andReturn(['card']);

            $gateway2 = Mockery::mock(PaymentGatewayInterface::class);
            $gateway2->shouldReceive('getSupportedMethods')
                ->andReturn(['wallet']);

            $this->gatewayManager->shouldReceive('getConfiguredGateways')
                ->andReturn([
                    'paymob' => $gateway1,
                    'tap' => $gateway2,
                ]);

            $methods = $this->service->getAvailablePaymentMethods();

            expect($methods)
                ->toHaveKey('paymob_card')
                ->toHaveKey('tap_wallet')
                ->toHaveCount(2);
        });
    });

    describe('calculateFees()', function () {
        it('calculates fees for card payment method', function () {
            config(['payments.fees.card' => 0.025]);

            $fees = $this->service->calculateFees(100.00, 'card');

            expect($fees)
                ->toHaveKey('fee_rate', 0.025)
                ->toHaveKey('fee_amount', 2.5)
                ->toHaveKey('total_with_fees', 102.5);
        });

        it('calculates fees for wallet payment method', function () {
            config(['payments.fees.wallet' => 0.02]);

            $fees = $this->service->calculateFees(100.00, 'wallet');

            expect($fees)
                ->toHaveKey('fee_rate', 0.02)
                ->toHaveKey('fee_amount', 2.0)
                ->toHaveKey('total_with_fees', 102.0);
        });

        it('uses default fee rate for unknown payment method', function () {
            $fees = $this->service->calculateFees(100.00, 'unknown_method');

            expect($fees)
                ->toHaveKey('fee_rate', 0.025)
                ->toHaveKey('fee_amount', 2.5)
                ->toHaveKey('total_with_fees', 102.5);
        });

        it('rounds fee amount to 2 decimal places', function () {
            config(['payments.fees.card' => 0.027]);

            $fees = $this->service->calculateFees(33.33, 'card');

            expect($fees['fee_amount'])->toBe(0.9);
        });
    });

    describe('gateway()', function () {
        it('returns specific gateway when name is provided', function () {
            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $result = $this->service->gateway('paymob');

            expect($result)->toBe($this->gateway);
        });

        it('returns default gateway when name is null', function () {
            $this->gatewayManager->shouldReceive('driver')
                ->with(null)
                ->andReturn($this->gateway);

            $result = $this->service->gateway();

            expect($result)->toBe($this->gateway);
        });
    });

    describe('payment notifications', function () {
        it('sends success notification for successful payment', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_123',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $notificationService = Mockery::mock(NotificationService::class);
            app()->instance(NotificationService::class, $notificationService);

            $notificationService->shouldReceive('sendPaymentSuccessNotification')
                ->once()
                ->withArgs(function ($user, $data) use ($payment) {
                    return $user->id === $this->user->id
                        && $data['amount'] === 100.00
                        && $data['payment_id'] === $payment->id;
                });

            $this->service->processPayment($payment);
        });

        it('sends failure notification for failed payment', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::failed(
                errorCode: 'CARD_DECLINED',
                errorMessage: 'Card was declined',
                errorMessageAr: 'تم رفض البطاقة'
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $notificationService = Mockery::mock(NotificationService::class);
            app()->instance(NotificationService::class, $notificationService);

            $notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data) {
                    return $user->id === $this->user->id
                        && $type === NotificationType::PAYMENT_FAILED
                        && $data['amount'] === 100.00;
                });

            $this->service->processPayment($payment);
        });

        it('includes subscription context in notification for Quran subscription', function () {
            $quranSubscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->user->id,
            ]);

            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
                'payable_type' => QuranSubscription::class,
                'payable_id' => $quranSubscription->id,
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_123',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $notificationService = Mockery::mock(NotificationService::class);
            app()->instance(NotificationService::class, $notificationService);

            $notificationService->shouldReceive('sendPaymentSuccessNotification')
                ->once()
                ->withArgs(function ($user, $data) use ($quranSubscription) {
                    return $data['subscription_type'] === 'quran'
                        && $data['subscription_id'] === $quranSubscription->id;
                });

            $this->service->processPayment($payment);
        });

        it('handles notification failure gracefully', function () {
            $payment = Payment::factory()->forAcademy($this->academy)->create([
                'user_id' => $this->user->id,
                'amount' => 100.00,
                'status' => 'pending',
                'payment_gateway' => 'paymob',
            ]);

            $result = PaymentResult::success(
                transactionId: 'txn_123',
                rawResponse: ['success' => true]
            );

            $this->gatewayManager->shouldReceive('driver')
                ->with('paymob')
                ->andReturn($this->gateway);

            $this->gateway->shouldReceive('isConfigured')
                ->andReturn(true);

            $this->gateway->shouldReceive('createPaymentIntent')
                ->andReturn($result);

            $notificationService = Mockery::mock(NotificationService::class);
            app()->instance(NotificationService::class, $notificationService);

            $notificationService->shouldReceive('sendPaymentSuccessNotification')
                ->once()
                ->andThrow(new \Exception('Notification service error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($payment) {
                    return str_contains($message, 'Failed to send payment notification')
                        && $context['payment_id'] === $payment->id;
                });

            $response = $this->service->processPayment($payment);

            expect($response)->toHaveKey('success', true);
        });
    });
});
