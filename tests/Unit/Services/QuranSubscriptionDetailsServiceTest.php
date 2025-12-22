<?php

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\QuranSubscriptionDetailsService;
use Carbon\Carbon;

describe('QuranSubscriptionDetailsService', function () {
    beforeEach(function () {
        $this->service = new QuranSubscriptionDetailsService();
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('getSubscriptionDetails()', function () {
        it('returns complete subscription details array', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'subscription_type' => 'individual',
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'billing_cycle' => BillingCycle::MONTHLY,
                'total_sessions' => 12,
                'sessions_used' => 3,
                'sessions_remaining' => 9,
                'total_price' => 500.00,
                'final_price' => 450.00,
                'discount_amount' => 50.00,
                'currency' => 'SAR',
                'auto_renew' => true,
                'trial_used' => 0,
                'is_trial_active' => false,
                'starts_at' => Carbon::now(),
                'next_payment_at' => Carbon::now()->addMonth(),
                'last_payment_at' => Carbon::now()->subDay(),
                'progress_percentage' => 25,
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

        it('returns correct subscription type', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'subscription_type' => 'individual',
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['subscription_type'])->toBe('individual');
        });

        it('returns correct status values', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::ACTIVE)
                ->and($details['payment_status'])->toBe(SubscriptionPaymentStatus::PAID);
        });

        it('returns correct date values', function () {
            $startsAt = Carbon::now()->startOfDay();
            $nextPaymentAt = Carbon::now()->addMonth();
            $lastPaymentAt = Carbon::now()->subDay();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'starts_at' => $startsAt,
                'next_payment_at' => $nextPaymentAt,
                'last_payment_at' => $lastPaymentAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['starts_at']->toDateString())->toBe($startsAt->toDateString())
                ->and($details['next_payment_at']->toDateString())->toBe($nextPaymentAt->toDateString())
                ->and($details['last_payment_at']->toDateString())->toBe($lastPaymentAt->toDateString());
        });

        it('returns correct session counts', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'total_sessions' => 20,
                'sessions_used' => 5,
                'sessions_remaining' => 15,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['total_sessions'])->toBe(20)
                ->and($details['sessions_used'])->toBe(5)
                ->and($details['sessions_remaining'])->toBe(15);
        });

        it('calculates sessions percentage correctly', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'total_sessions' => 10,
                'sessions_used' => 3,
                'sessions_remaining' => 7,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(30.0);
        });

        it('returns correct billing cycle information', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'billing_cycle' => BillingCycle::MONTHLY,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle'])->toBe(BillingCycle::MONTHLY)
                ->and($details['billing_cycle_text'])->toBeString()
                ->and($details['billing_cycle_ar'])->toBeString();
        });

        it('returns correct pricing information', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'currency' => 'SAR',
                'total_price' => 600.00,
                'final_price' => 540.00,
                'discount_amount' => 60.00,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['currency'])->toBe('SAR')
                ->and($details['total_price'])->not->toBeNull()
                ->and($details['final_price'])->not->toBeNull()
                ->and($details['discount_amount'])->not->toBeNull();
        });

        it('returns correct status badge classes', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toBeString()
                ->and($details['payment_status_badge_class'])->toBeString();
        });

        it('returns correct trial information', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'is_trial_active' => true,
                'trial_used' => 1,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['is_trial_active'])->toBeTrue()
                ->and($details['trial_used'])->toBe(1);
        });

        it('returns correct auto renew flag', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'auto_renew' => true,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['auto_renew'])->toBeTrue();
        });

        it('calculates days until next payment correctly', function () {
            $nextPaymentAt = Carbon::now()->addDays(15);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'next_payment_at' => $nextPaymentAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBeGreaterThanOrEqual(14)
                ->and($details['days_until_next_payment'])->toBeLessThanOrEqual(16);
        });

        it('returns null days until next payment when no next payment date', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'next_payment_at' => null,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBeNull();
        });

        it('returns progress percentage from subscription', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'progress_percentage' => 75,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['progress_percentage'])->not->toBeNull();
        });

        it('handles expired subscriptions with paused_at date', function () {
            $pausedAt = Carbon::now()->subDays(5);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'paused_at' => $pausedAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::EXPIRED)
                ->and($details['paused_at'])->not->toBeNull()
                ->and($details['paused_at']->toDateString())->toBe($pausedAt->toDateString());
        });

        it('handles cancelled subscriptions correctly', function () {
            $cancelledAt = Carbon::now()->subDays(3);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::CANCELLED,
                'cancelled_at' => $cancelledAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::CANCELLED)
                ->and($details['cancelled_at'])->not->toBeNull()
                ->and($details['cancelled_at']->toDateString())->toBe($cancelledAt->toDateString());
        });

        it('handles zero sessions correctly', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'total_sessions' => 0,
                'sessions_used' => 0,
                'sessions_remaining' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['total_sessions'])->toBe(0)
                ->and($details['sessions_used'])->toBe(0)
                ->and($details['sessions_remaining'])->toBe(0)
                ->and($details['sessions_percentage'])->toBe(0.0);
        });

        it('caps sessions percentage at 100', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'total_sessions' => 10,
                'sessions_used' => 12,
                'sessions_remaining' => -2,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(100.0);
        });

        it('handles quarterly billing cycle', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'billing_cycle' => BillingCycle::QUARTERLY,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle'])->toBe(BillingCycle::QUARTERLY)
                ->and($details['billing_cycle_text'])->toBeString()
                ->and($details['billing_cycle_ar'])->toBeString();
        });

        it('handles yearly billing cycle', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'billing_cycle' => BillingCycle::YEARLY,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['billing_cycle'])->toBe(BillingCycle::YEARLY)
                ->and($details['billing_cycle_text'])->toBeString()
                ->and($details['billing_cycle_ar'])->toBeString();
        });

        it('handles pending payment status', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'payment_status' => SubscriptionPaymentStatus::PENDING,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status'])->toBe(SubscriptionPaymentStatus::PENDING)
                ->and($details['payment_status_badge_class'])->toBeString();
        });

        it('handles failed payment status', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status'])->toBe(SubscriptionPaymentStatus::FAILED)
                ->and($details['payment_status_badge_class'])->toBeString();
        });

        it('handles completed subscriptions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::COMPLETED,
                'total_sessions' => 10,
                'sessions_used' => 10,
                'sessions_remaining' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::COMPLETED)
                ->and($details['sessions_remaining'])->toBe(0)
                ->and($details['sessions_percentage'])->toBe(100.0);
        });

        it('handles expired subscriptions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status'])->toBe(SubscriptionStatus::EXPIRED)
                ->and($details['status_badge_class'])->toBeString();
        });

        it('handles circle subscription type', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'subscription_type' => 'group',
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['subscription_type'])->toBe('group');
        });

        it('returns zero discount when no discount applied', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'total_price' => 500.00,
                'final_price' => 500.00,
                'discount_amount' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['discount_amount'])->not->toBeNull();
        });

        it('handles negative days until next payment', function () {
            $nextPaymentAt = Carbon::now()->subDays(5);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'next_payment_at' => $nextPaymentAt,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['days_until_next_payment'])->toBeLessThan(0);
        });

        it('returns correct data for trial subscription', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'is_trial_active' => true,
                'trial_used' => 0,
                'total_sessions' => 2,
                'sessions_used' => 0,
                'sessions_remaining' => 2,
                'final_price' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['is_trial_active'])->toBeTrue()
                ->and($details['trial_used'])->toBe(0)
                ->and($details['total_sessions'])->toBe(2)
                ->and($details['final_price'])->not->toBeNull();
        });

        it('handles refunded payment status', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'payment_status' => SubscriptionPaymentStatus::REFUNDED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status'])->toBe(SubscriptionPaymentStatus::REFUNDED)
                ->and($details['payment_status_badge_class'])->toBeString();
        });
    });
});
