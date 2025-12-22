<?php

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Services\AcademicSubscriptionDetailsService;
use Carbon\Carbon;

describe('AcademicSubscriptionDetailsService', function () {
    beforeEach(function () {
        $this->service = new AcademicSubscriptionDetailsService();
        $this->academy = Academy::factory()->create();
    });

    describe('getSubscriptionDetails()', function () {
        it('returns complete subscription details with all required fields', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'billing_cycle' => BillingCycle::MONTHLY,
                'currency' => 'SAR',
                'total_price' => 1000,
                'final_price' => 900,
                'discount_amount' => 100,
                'starts_at' => Carbon::now()->subDays(5),
                'ends_at' => Carbon::now()->addDays(25),
                'next_payment_at' => Carbon::now()->addDays(25),
                'last_payment_at' => Carbon::now()->subDays(5),
                'auto_renew' => true,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)->toBeArray()
                ->and($details)->toHaveKeys([
                    'subscription_type',
                    'status',
                    'payment_status',
                    'starts_at',
                    'next_payment_at',
                    'last_payment_at',
                    'paused_at',
                    'cancelled_at',
                    'total_sessions',
                    'sessions_used',
                    'sessions_remaining',
                    'sessions_percentage',
                    'billing_cycle',
                    'billing_cycle_text',
                    'billing_cycle_ar',
                    'currency',
                    'total_price',
                    'final_price',
                    'discount_amount',
                    'status_badge_class',
                    'payment_status_badge_class',
                    'is_trial_active',
                    'trial_used',
                    'auto_renew',
                    'days_until_next_payment',
                    'progress_percentage',
                ]);
        });

        it('returns subscription_type as academic', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['subscription_type'])->toBe('academic');
        });

        it('returns correct status and payment status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::ACTIVE)
                ->and($details['payment_status'])->toBe(SubscriptionPaymentStatus::PAID);
        });

        it('returns correct date fields', function () {
            $startsAt = Carbon::now()->subDays(10);
            $nextPaymentAt = Carbon::now()->addDays(20);
            $lastPaymentAt = Carbon::now()->subDays(10);

            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => $startsAt,
                'next_payment_at' => $nextPaymentAt,
                'last_payment_at' => $lastPaymentAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['starts_at']->toDateString())->toBe($startsAt->toDateString())
                ->and($details['next_payment_at']->toDateString())->toBe($nextPaymentAt->toDateString())
                ->and($details['last_payment_at']->toDateString())->toBe($lastPaymentAt->toDateString())
                ->and($details['paused_at'])->toBeNull()
                ->and($details['cancelled_at'])->toBeNull();
        });

        it('returns correct session counts', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'sessions_per_cycle' => 10,
                'sessions_used' => 3,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['total_sessions'])->toBe(10)
                ->and($details['sessions_used'])->toBe(3)
                ->and($details['sessions_remaining'])->toBe(7);
        });

        it('calculates sessions percentage correctly', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'sessions_per_cycle' => 10,
                'sessions_used' => 3,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(30.0);
        });

        it('returns correct billing information', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'billing_cycle' => BillingCycle::MONTHLY,
                'currency' => 'SAR',
                'total_price' => 1000,
                'final_price' => 900,
                'discount_amount' => 100,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle'])->toBe(BillingCycle::MONTHLY)
                ->and($details['billing_cycle_text'])->toBe('Monthly')
                ->and($details['billing_cycle_ar'])->toBe('شهرية')
                ->and($details['currency'])->toBe('SAR')
                ->and($details['total_price'])->toBe(1000)
                ->and($details['final_price'])->toBe(900)
                ->and($details['discount_amount'])->toBe(100);
        });

        it('returns correct badge classes for active status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('green')
                ->and($details['payment_status_badge_class'])->toContain('green');
        });

        it('returns trial info as false for academic subscriptions', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['is_trial_active'])->toBe(false)
                ->and($details['trial_used'])->toBe(false);
        });

        it('returns auto_renew flag', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'auto_renew' => true,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['auto_renew'])->toBe(true);
        });

        it('handles null auto_renew as false', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'auto_renew' => null,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['auto_renew'])->toBe(false);
        });

        it('calculates days until next payment correctly', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'next_payment_at' => Carbon::now()->addDays(15),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBe(15);
        });

        it('returns null days until next payment when next_payment_at is null', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'next_payment_at' => null,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBeNull();
        });

        it('calculates progress percentage based on time elapsed', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(10),
                'ends_at' => Carbon::now()->addDays(20),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            // 10 days elapsed out of 30 total = 33.3%
            expect($details['progress_percentage'])->toBeGreaterThan(30)
                ->and($details['progress_percentage'])->toBeLessThan(35);
        });

        it('handles sessions percentage when total sessions is zero', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'sessions_per_cycle' => 0,
                'sessions_used' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(0.0);
        });

        it('caps sessions percentage at 100', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'sessions_per_cycle' => 10,
                'sessions_used' => 15, // More than total
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(100.0);
        });
    });

    describe('calculateProgressPercentage()', function () {
        it('calculates progress correctly for mid-subscription', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(15),
                'ends_at' => Carbon::now()->addDays(15),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            // 15 days elapsed out of 30 total = 50%
            expect($details['progress_percentage'])->toBe(50.0);
        });

        it('returns zero when starts_at is null', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => null,
                'ends_at' => Carbon::now()->addDays(30),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBe(0.0);
        });

        it('returns zero when ends_at is null', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(10),
                'ends_at' => null,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBe(0.0);
        });

        it('returns zero when total days is zero or negative', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now(), // Same date
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBe(0.0);
        });

        it('caps progress percentage at 100', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(40),
                'ends_at' => Carbon::now()->subDays(10), // Already expired
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBe(100.0);
        });

        it('rounds progress percentage to one decimal place', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(1),
                'ends_at' => Carbon::now()->addDays(2),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            // 1 day out of 3 days = 33.333...%
            expect($details['progress_percentage'])->toBe(33.3);
        });

        it('handles newly started subscription', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addDays(30),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBeGreaterThanOrEqual(0)
                ->and($details['progress_percentage'])->toBeLessThan(5);
        });

        it('handles subscription ending today', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'starts_at' => Carbon::now()->subDays(30),
                'ends_at' => Carbon::now(),
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->toBeGreaterThan(95)
                ->and($details['progress_percentage'])->toBeLessThanOrEqual(100);
        });
    });

    describe('billing cycle handling', function () {
        it('handles quarterly billing cycle', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'billing_cycle' => BillingCycle::QUARTERLY,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle_text'])->toBe('Quarterly')
                ->and($details['billing_cycle_ar'])->toBe('ربع سنوية');
        });

        it('handles yearly billing cycle', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'billing_cycle' => BillingCycle::YEARLY,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle_text'])->toBe('Yearly')
                ->and($details['billing_cycle_ar'])->toBe('سنوية');
        });
    });

    describe('status badge handling', function () {
        it('returns correct badge class for pending status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::PENDING,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('yellow');
        });

        it('returns correct badge class for cancelled status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::CANCELLED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('red');
        });

        it('returns correct badge class for paused status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::PAUSED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('blue');
        });

        it('returns correct badge class for expired status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::EXPIRED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('gray');
        });

        it('returns correct badge class for completed status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::COMPLETED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('purple');
        });
    });

    describe('payment status badge handling', function () {
        it('returns correct badge class for pending payment status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'payment_status' => SubscriptionPaymentStatus::PENDING,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status_badge_class'])->toContain('yellow');
        });

        it('returns correct badge class for failed payment status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status_badge_class'])->toContain('red');
        });

        it('returns correct badge class for refunded payment status', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'payment_status' => SubscriptionPaymentStatus::REFUNDED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status_badge_class'])->toContain('orange');
        });
    });

    describe('edge cases', function () {
        it('handles negative days until next payment', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'next_payment_at' => Carbon::now()->subDays(5), // Past due
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBeLessThan(0);
        });

        it('handles paused_at and cancelled_at dates', function () {
            $pausedAt = Carbon::now()->subDays(5);
            $cancelledAt = Carbon::now()->subDays(3);

            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'paused_at' => $pausedAt,
                'cancelled_at' => $cancelledAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['paused_at']->toDateString())->toBe($pausedAt->toDateString())
                ->and($details['cancelled_at']->toDateString())->toBe($cancelledAt->toDateString());
        });

        it('handles zero discount amount', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'total_price' => 1000,
                'final_price' => 1000,
                'discount_amount' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['discount_amount'])->toBe(0);
        });

        it('handles full session usage', function () {
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'sessions_per_cycle' => 10,
                'sessions_used' => 10,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_remaining'])->toBe(0)
                ->and($details['sessions_percentage'])->toBe(100.0);
        });
    });
});
