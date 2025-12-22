<?php

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\SubscriptionRenewalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('SubscriptionRenewalService', function () {
    beforeEach(function () {
        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->service = new SubscriptionRenewalService(
            $this->paymentService,
            $this->notificationService
        );
        $this->academy = Academy::factory()->create();
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('processAllDueRenewals()', function () {
        it('processes all subscriptions due for renewal', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'billing_cycle' => BillingCycle::MONTHLY,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $this->paymentService
                ->shouldReceive('processSubscriptionRenewal')
                ->once()
                ->andReturn(['success' => true]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionRenewedNotification')
                ->once();

            $results = $this->service->processAllDueRenewals();

            expect($results['processed'])->toBe(1)
                ->and($results['successful'])->toBe(1)
                ->and($results['failed'])->toBe(0);
        });

        it('handles failed renewals', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'billing_cycle' => BillingCycle::MONTHLY,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $this->paymentService
                ->shouldReceive('processSubscriptionRenewal')
                ->once()
                ->andReturn(['success' => false, 'error' => 'Payment failed']);

            $this->notificationService
                ->shouldReceive('sendPaymentFailedNotification')
                ->once();

            $results = $this->service->processAllDueRenewals();

            expect($results['processed'])->toBe(1)
                ->and($results['successful'])->toBe(0)
                ->and($results['failed'])->toBe(1);
        });

        it('returns empty results when no subscriptions are due', function () {
            $results = $this->service->processAllDueRenewals();

            expect($results['processed'])->toBe(0)
                ->and($results['successful'])->toBe(0)
                ->and($results['failed'])->toBe(0);
        });

        it('handles exceptions during renewal processing', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'billing_cycle' => BillingCycle::MONTHLY,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $this->paymentService
                ->shouldReceive('processSubscriptionRenewal')
                ->once()
                ->andThrow(new \Exception('Payment gateway error'));

            Log::shouldReceive('error')->atLeast()->once();
            Log::shouldReceive('info')->atLeast()->once();

            $results = $this->service->processAllDueRenewals();

            expect($results['failed'])->toBe(1)
                ->and($results['errors'])->toHaveCount(1);
        });
    });

    describe('processRenewal()', function () {
        it('skips subscriptions not eligible for renewal', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
                'next_billing_date' => now()->addDays(10),
                'subscription_type' => 'group',
            ]);

            Log::shouldReceive('info')->atLeast()->once();

            $result = $this->service->processRenewal($subscription);

            expect($result)->toBe(false);
        });

        it('uses trait method attemptAutoRenewal when available', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'billing_cycle' => BillingCycle::MONTHLY,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $this->paymentService
                ->shouldReceive('processSubscriptionRenewal')
                ->once()
                ->andReturn(['success' => true]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionRenewedNotification')
                ->once();

            $result = $this->service->processRenewal(QuranSubscription::first());

            expect($result)->toBe(true);
        });
    });

    describe('sendRenewalReminders()', function () {
        it('sends 7-day renewal reminders', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(7),
                'renewal_reminder_sent_at' => null,
                'subscription_type' => 'group',
            ]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionExpiringNotification')
                ->once();

            Log::shouldReceive('info')->atLeast()->once();

            $results = $this->service->sendRenewalReminders();

            expect($results['sent'])->toBeGreaterThanOrEqual(1);
        });

        it('sends 3-day renewal reminders', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(3),
                'subscription_type' => 'group',
            ]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionExpiringNotification')
                ->once();

            Log::shouldReceive('info')->atLeast()->once();

            $results = $this->service->sendRenewalReminders();

            expect($results['sent'])->toBeGreaterThanOrEqual(1);
        });

        it('does not send duplicate 7-day reminders', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(7),
                'renewal_reminder_sent_at' => now()->subDay(),
                'subscription_type' => 'group',
            ]);

            Log::shouldReceive('info')->atLeast()->once();

            $results = $this->service->sendRenewalReminders();

            expect($results['sent'])->toBe(0);
        });

        it('handles errors during reminder sending', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(7),
                'renewal_reminder_sent_at' => null,
                'subscription_type' => 'group',
            ]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionExpiringNotification')
                ->once()
                ->andThrow(new \Exception('Notification service error'));

            Log::shouldReceive('info')->atLeast()->once();

            $results = $this->service->sendRenewalReminders();

            expect($results['errors'])->toHaveCount(1);
        });
    });

    describe('getDueForRenewal()', function () {
        it('returns Quran subscriptions due for renewal', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $dueSubscriptions = $this->service->getDueForRenewal();

            expect($dueSubscriptions)->toHaveCount(1);
        });

        it('excludes subscriptions with auto_renew disabled', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $dueSubscriptions = $this->service->getDueForRenewal();

            expect($dueSubscriptions)->toHaveCount(0);
        });

        it('excludes subscriptions not active', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'auto_renew' => true,
                'next_billing_date' => now(),
                'subscription_type' => 'group',
            ]);

            $dueSubscriptions = $this->service->getDueForRenewal();

            expect($dueSubscriptions)->toHaveCount(0);
        });

        it('excludes subscriptions with future billing dates', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(5),
                'subscription_type' => 'group',
            ]);

            $dueSubscriptions = $this->service->getDueForRenewal();

            expect($dueSubscriptions)->toHaveCount(0);
        });
    });

    describe('getFailedRenewals()', function () {
        it('returns failed Quran subscription renewals', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'updated_at' => now()->subDays(5),
                'subscription_type' => 'group',
            ]);

            $failedRenewals = $this->service->getFailedRenewals($this->academy->id, 30);

            expect($failedRenewals)->toHaveCount(1);
        });

        it('filters by time period', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'updated_at' => now()->subDays(40),
                'subscription_type' => 'group',
            ]);

            $failedRenewals = $this->service->getFailedRenewals($this->academy->id, 30);

            expect($failedRenewals)->toHaveCount(0);
        });

        it('returns results sorted by updated_at descending', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $older = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'updated_at' => now()->subDays(10),
                'subscription_type' => 'group',
            ]);

            $newer = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => User::factory()->student()->forAcademy($this->academy)->create()->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'updated_at' => now()->subDays(5),
                'subscription_type' => 'group',
            ]);

            $failedRenewals = $this->service->getFailedRenewals($this->academy->id, 30);

            expect($failedRenewals->first()->id)->toBe($newer->id)
                ->and($failedRenewals->last()->id)->toBe($older->id);
        });
    });

    describe('manualRenewal()', function () {
        it('renews subscription manually with payment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_cycle' => BillingCycle::MONTHLY,
                'next_billing_date' => now()->addDays(5),
                'subscription_type' => 'group',
            ]);

            $this->notificationService
                ->shouldReceive('sendSubscriptionRenewedNotification')
                ->once();

            Log::shouldReceive('info')->atLeast()->once();

            $renewed = $this->service->manualRenewal($subscription, 500.00);

            expect($renewed->status)->toBe(SubscriptionStatus::ACTIVE)
                ->and($renewed->last_payment_date)->not->toBeNull();
        });

        it('throws exception if subscription cannot be renewed', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::CANCELLED,
                'subscription_type' => 'group',
            ]);

            expect(fn () => $this->service->manualRenewal($subscription, 500.00))
                ->toThrow(\Exception::class, 'Subscription cannot be renewed in current state');
        });
    });

    describe('reactivate()', function () {
        it('reactivates expired subscription with payment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'billing_cycle' => BillingCycle::MONTHLY,
                'auto_renew' => false,
                'subscription_type' => 'group',
            ]);

            Log::shouldReceive('info')->atLeast()->once();

            $reactivated = $this->service->reactivate($subscription, 500.00);

            expect($reactivated->status)->toBe(SubscriptionStatus::ACTIVE)
                ->and($reactivated->auto_renew)->toBe(true)
                ->and($reactivated->last_payment_date)->not->toBeNull();
        });

        it('throws exception if subscription is not expired', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'subscription_type' => 'group',
            ]);

            expect(fn () => $this->service->reactivate($subscription, 500.00))
                ->toThrow(\Exception::class, 'Only expired subscriptions can be reactivated');
        });
    });

    describe('getRenewalStatistics()', function () {
        it('calculates renewal statistics for academy', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'last_payment_date' => now()->subDays(5),
                'final_price' => 500.00,
                'subscription_type' => 'group',
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student2->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::EXPIRED,
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'updated_at' => now()->subDays(3),
                'subscription_type' => 'group',
            ]);

            $stats = $this->service->getRenewalStatistics($this->academy->id, 30);

            expect($stats['period_days'])->toBe(30)
                ->and($stats['successful_renewals'])->toBe(1)
                ->and($stats['failed_renewals'])->toBe(1)
                ->and($stats['total_renewals'])->toBe(2)
                ->and($stats['total_revenue'])->toBe(500.00)
                ->and($stats['by_type'])->toHaveKey('quran')
                ->and($stats['by_type'])->toHaveKey('academic');
        });

        it('calculates upcoming renewals', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'next_billing_date' => now()->addDays(5),
                'subscription_type' => 'group',
            ]);

            $stats = $this->service->getRenewalStatistics($this->academy->id, 30);

            expect($stats['upcoming_renewals'])->toBe(1);
        });
    });
});
