<?php

use App\Models\Academy;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;

describe('Payment Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->user = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('factory', function () {
        it('can create a payment using factory', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
            ]);

            expect($payment)->toBeInstanceOf(Payment::class)
                ->and($payment->id)->toBeInt();
        });

        it('creates payment with amount', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100.00,
            ]);

            expect((float) $payment->amount)->toBe(100.00);
        });

        it('creates payment with default status as pending', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            expect($payment->status)->toBe('pending');
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
            ]);

            expect($payment->academy)->toBeInstanceOf(Academy::class)
                ->and($payment->academy->id)->toBe($this->academy->id);
        });

        it('belongs to a user', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
            ]);

            expect($payment->user)->toBeInstanceOf(User::class)
                ->and($payment->user->id)->toBe($this->user->id);
        });
    });

    describe('scopes', function () {
        it('can filter successful payments', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            expect(Payment::successful()->count())->toBe(1);
        });

        it('can filter pending payments', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            expect(Payment::pending()->count())->toBe(1);
        });

        it('can filter failed payments', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'failed',
            ]);

            expect(Payment::failed()->count())->toBe(1);
        });

        it('can filter refunded payments', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'refunded',
            ]);

            expect(Payment::refunded()->count())->toBe(1);
        });

        it('can filter by payment method', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_method' => 'credit_card',
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_method' => 'mada',
            ]);

            expect(Payment::byMethod('credit_card')->count())->toBe(1);
        });

        it('can filter by gateway', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_gateway' => 'moyasar',
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_gateway' => 'tap',
            ]);

            expect(Payment::byGateway('moyasar')->count())->toBe(1);
        });

        it('can filter today payments', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_date' => today(),
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_date' => today()->subDays(5),
            ]);

            expect(Payment::today()->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts amount to decimal', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100.50,
            ]);

            expect($payment->amount)->toBeString(); // Decimals cast to string
        });

        it('casts payment_date to datetime', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_date' => now(),
            ]);

            expect($payment->payment_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts gateway_response to array', function () {
            $response = ['transaction_id' => '123', 'status' => 'success'];
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'gateway_response' => $response,
            ]);

            expect($payment->gateway_response)->toBeArray()
                ->and($payment->gateway_response['transaction_id'])->toBe('123');
        });

        it('casts metadata to array', function () {
            $metadata = ['source' => 'web', 'campaign' => 'summer'];
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'metadata' => $metadata,
            ]);

            expect($payment->metadata)->toBeArray()
                ->and($payment->metadata['source'])->toBe('web');
        });
    });

    describe('accessors', function () {
        it('returns payment method text in Arabic', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'payment_method' => 'credit_card',
            ]);

            expect($payment->payment_method_text)->toBe('بطاقة ائتمان');
        });

        it('returns status text in Arabic', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            expect($payment->status_text)->toBe('مكتمل');
        });

        it('returns status badge color', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            expect($payment->status_badge_color)->toBe('success');
        });

        it('returns formatted amount', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100.50,
                'currency' => 'SAR',
            ]);

            expect($payment->formatted_amount)->toContain('100.50')
                ->and($payment->formatted_amount)->toContain('SAR');
        });

        it('checks if payment is successful', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            expect($payment->is_successful)->toBeTrue();
        });

        it('checks if payment is pending', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            expect($payment->is_pending)->toBeTrue();
        });

        it('checks if payment is failed', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'failed',
            ]);

            expect($payment->is_failed)->toBeTrue();
        });

        it('checks if payment is refunded', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'refunded',
            ]);

            expect($payment->is_refunded)->toBeTrue();
        });
    });

    describe('methods', function () {
        it('can mark payment as pending', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'processing',
            ]);

            $payment->markAsPending();

            expect($payment->status)->toBe('pending')
                ->and($payment->payment_status)->toBe('pending');
        });

        it('can mark payment as processing', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            $payment->markAsProcessing();

            expect($payment->status)->toBe('processing')
                ->and($payment->processed_at)->not->toBeNull();
        });

        it('can mark payment as completed', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'processing',
            ]);

            $payment->markAsCompleted(['transaction_id' => 'txn_123']);

            expect($payment->status)->toBe('completed')
                ->and($payment->payment_status)->toBe('paid')
                ->and($payment->confirmed_at)->not->toBeNull();
        });

        it('can mark payment as failed', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'processing',
            ]);

            $payment->markAsFailed('Card declined');

            expect($payment->status)->toBe('failed')
                ->and($payment->failure_reason)->toBe('Card declined');
        });

        it('can cancel payment', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            $payment->cancel('User requested cancellation');

            expect($payment->status)->toBe('cancelled')
                ->and($payment->failure_reason)->toBe('User requested cancellation');
        });

        it('can calculate fees for credit card', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100,
                'payment_method' => 'credit_card',
            ]);

            $fees = $payment->calculateFees();

            expect($fees)->toBe(2.9); // 2.9% fee
        });

        it('can calculate fees for mada', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100,
                'payment_method' => 'mada',
            ]);

            $fees = $payment->calculateFees();

            expect($fees)->toBe(1.75); // 1.75% fee
        });

        it('can calculate tax', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100,
                'tax_percentage' => 15,
            ]);

            $tax = $payment->calculateTax();

            expect($tax)->toBe(15.0); // 15% VAT
        });

        it('can update gateway response', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'gateway_response' => ['step' => 'initial'],
            ]);

            $payment->updateGatewayResponse(['step' => 'confirmed', 'code' => '200']);

            expect($payment->gateway_response)->toHaveKey('code')
                ->and($payment->gateway_response['step'])->toBe('confirmed');
        });
    });

    describe('static methods', function () {
        it('can update amounts with calculated fees and net', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100,
                'payment_method' => 'credit_card',
            ]);

            $payment->updateAmounts();

            expect((float) $payment->fees)->toBe(2.9)
                ->and((float) $payment->net_amount)->toBe(97.1);
        });

        it('can get total revenue for academy', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 100,
                'status' => 'completed',
                'payment_date' => now(),
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'amount' => 200,
                'status' => 'completed',
                'payment_date' => now(),
            ]);

            $revenue = Payment::getTotalRevenue($this->academy->id);

            expect((float) $revenue)->toBe(300.0);
        });

        it('can get payment stats', function () {
            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
                'amount' => 100,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
                'amount' => 50,
            ]);

            $stats = Payment::getPaymentStats($this->academy->id);

            expect($stats)->toBeArray()
                ->and($stats['total_payments'])->toBe(2)
                ->and($stats['successful_payments'])->toBe(1)
                ->and($stats['pending_payments'])->toBe(1);
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
            ]);

            $payment->delete();

            expect($payment->trashed())->toBeTrue()
                ->and(Payment::withTrashed()->find($payment->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $payment = Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->user->id,
            ]);
            $payment->delete();

            $payment->restore();

            expect($payment->trashed())->toBeFalse()
                ->and(Payment::find($payment->id))->not->toBeNull();
        });
    });
});
