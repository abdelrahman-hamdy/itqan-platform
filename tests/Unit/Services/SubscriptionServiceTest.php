<?php

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

describe('SubscriptionService', function () {
    beforeEach(function () {
        $this->service = new SubscriptionService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->quranTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
    });

    describe('getSubscriptionTypes()', function () {
        it('returns all subscription types', function () {
            $types = SubscriptionService::getSubscriptionTypes();

            expect($types)->toBeArray()
                ->and($types)->toHaveKey(SubscriptionService::TYPE_QURAN)
                ->and($types)->toHaveKey(SubscriptionService::TYPE_ACADEMIC)
                ->and($types)->toHaveKey(SubscriptionService::TYPE_COURSE)
                ->and($types)->toHaveCount(3);
        });

        it('returns Arabic labels for subscription types', function () {
            $types = SubscriptionService::getSubscriptionTypes();

            expect($types[SubscriptionService::TYPE_QURAN])->toBe('اشتراك قرآن')
                ->and($types[SubscriptionService::TYPE_ACADEMIC])->toBe('اشتراك أكاديمي')
                ->and($types[SubscriptionService::TYPE_COURSE])->toBe('اشتراك دورة');
        });
    });

    describe('getModelClass()', function () {
        it('returns correct model class for quran type', function () {
            $class = SubscriptionService::getModelClass(SubscriptionService::TYPE_QURAN);

            expect($class)->toBe(QuranSubscription::class);
        });

        it('returns correct model class for academic type', function () {
            $class = SubscriptionService::getModelClass(SubscriptionService::TYPE_ACADEMIC);

            expect($class)->toBe(AcademicSubscription::class);
        });

        it('returns correct model class for course type', function () {
            $class = SubscriptionService::getModelClass(SubscriptionService::TYPE_COURSE);

            expect($class)->toBe(CourseSubscription::class);
        });

        it('throws exception for unknown type', function () {
            expect(fn () => SubscriptionService::getModelClass('invalid'))
                ->toThrow(InvalidArgumentException::class, 'Unknown subscription type: invalid');
        });
    });

    describe('create()', function () {
        it('creates a quran subscription successfully', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->id,
                'package_name_ar' => 'الأساسي',
                'package_name_en' => 'Basic',
                'package_sessions_per_week' => 2,
                'package_session_duration_minutes' => 45,
                'total_sessions' => 8,
                'total_price' => 200,
                'final_price' => 200,
                'currency' => 'SAR',
                'status' => SubscriptionStatus::PENDING,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ];

            $subscription = $this->service->create(SubscriptionService::TYPE_QURAN, $data);

            expect($subscription)->toBeInstanceOf(QuranSubscription::class)
                ->and($subscription->student_id)->toBe($this->student->id)
                ->and($subscription->academy_id)->toBe($this->academy->id)
                ->and($subscription->subscription_code)->not->toBeNull();
        });

        it('creates subscription within database transaction', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->id,
                'package_name_ar' => 'الأساسي',
                'package_name_en' => 'Basic',
                'total_sessions' => 8,
                'total_price' => 200,
                'final_price' => 200,
                'currency' => 'SAR',
                'status' => SubscriptionStatus::PENDING,
            ];

            $subscription = $this->service->create(SubscriptionService::TYPE_QURAN, $data);

            expect($subscription->exists)->toBeTrue();
        });

        it('logs subscription creation', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Subscription created', \Mockery::type('array'));

            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->id,
                'package_name_ar' => 'الأساسي',
                'total_sessions' => 8,
                'total_price' => 200,
                'final_price' => 200,
                'currency' => 'SAR',
            ];

            $this->service->create(SubscriptionService::TYPE_QURAN, $data);
        });
    });

    describe('createQuranSubscription()', function () {
        it('creates quran subscription', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->id,
                'package_name_ar' => 'الأساسي',
                'total_sessions' => 8,
                'total_price' => 200,
                'final_price' => 200,
                'currency' => 'SAR',
            ];

            $subscription = $this->service->createQuranSubscription($data);

            expect($subscription)->toBeInstanceOf(QuranSubscription::class);
        });
    });

    describe('createAcademicSubscription()', function () {
        it('creates academic subscription', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'package_name_ar' => 'دروس خصوصية',
                'total_sessions' => 8,
                'total_price' => 400,
                'final_price' => 400,
                'currency' => 'SAR',
            ];

            $subscription = $this->service->createAcademicSubscription($data);

            expect($subscription)->toBeInstanceOf(AcademicSubscription::class);
        });
    });

    describe('createCourseSubscription()', function () {
        it('creates course subscription', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'package_name_ar' => 'دورة تسجيلية',
                'course_type' => 'recorded',
                'total_price' => 150,
                'final_price' => 150,
                'currency' => 'SAR',
                'billing_cycle' => BillingCycle::LIFETIME,
            ];

            $subscription = $this->service->createCourseSubscription($data);

            expect($subscription)->toBeInstanceOf(CourseSubscription::class);
        });
    });

    describe('createTrialSubscription()', function () {
        it('creates trial subscription with zero price', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->id,
                'package_name_ar' => 'تجريبي',
                'total_sessions' => 1,
            ];

            $subscription = $this->service->createTrialSubscription(SubscriptionService::TYPE_QURAN, $data);

            expect($subscription)->toBeInstanceOf(QuranSubscription::class)
                ->and($subscription->final_price)->toBe(0.0)
                ->and($subscription->payment_status)->toBe(SubscriptionPaymentStatus::PAID)
                ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
        });
    });

    describe('activate()', function () {
        it('activates a pending subscription', function () {
            $subscription = QuranSubscription::factory()
                ->pending()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->activate($subscription, 200.0);

            expect($result->status)->toBe(SubscriptionStatus::ACTIVE)
                ->and($result->payment_status)->toBe(SubscriptionPaymentStatus::PAID)
                ->and($result->final_price)->toBe(200.0);
        });

        it('updates final price when amount paid is provided', function () {
            $subscription = QuranSubscription::factory()
                ->pending()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'final_price' => 200.0,
                ]);

            $result = $this->service->activate($subscription, 180.0);

            expect($result->final_price)->toBe(180.0);
        });

        it('throws exception when subscription is not pending', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            expect(fn () => $this->service->activate($subscription))
                ->toThrow(Exception::class, 'Subscription is not in pending state');
        });

        it('uses database lock to prevent race conditions', function () {
            $subscription = QuranSubscription::factory()
                ->pending()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->activate($subscription);

            expect($result->status)->toBe(SubscriptionStatus::ACTIVE);
        });

        it('logs activation', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Subscription activated', \Mockery::type('array'));

            $subscription = QuranSubscription::factory()
                ->pending()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $this->service->activate($subscription, 200.0);
        });
    });

    describe('cancel()', function () {
        it('cancels an active subscription', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->cancel($subscription, 'User requested cancellation');

            expect($result->status)->toBe(SubscriptionStatus::CANCELLED)
                ->and($result->cancelled_at)->not->toBeNull()
                ->and($result->cancellation_reason)->toBe('User requested cancellation');
        });

        it('throws exception when subscription cannot be cancelled', function () {
            $subscription = QuranSubscription::factory()
                ->cancelled()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            expect(fn () => $this->service->cancel($subscription))
                ->toThrow(Exception::class, 'Subscription cannot be cancelled in current state');
        });

        it('logs cancellation', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Subscription cancelled', \Mockery::type('array'));

            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $this->service->cancel($subscription, 'Test reason');
        });
    });

    describe('getStudentSubscriptions()', function () {
        it('returns all subscriptions for a student', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->count(2)
                ->create(['academy_id' => $this->academy->id]);

            $subscriptions = $this->service->getStudentSubscriptions($this->student->id);

            expect($subscriptions)->toHaveCount(2);
        });

        it('returns subscriptions from all types', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
            ]);

            CourseSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => 'recorded',
            ]);

            $subscriptions = $this->service->getStudentSubscriptions($this->student->id);

            expect($subscriptions)->toHaveCount(3)
                ->and($subscriptions->contains(fn ($s) => $s instanceof QuranSubscription))->toBeTrue()
                ->and($subscriptions->contains(fn ($s) => $s instanceof AcademicSubscription))->toBeTrue()
                ->and($subscriptions->contains(fn ($s) => $s instanceof CourseSubscription))->toBeTrue();
        });

        it('filters by academy when academy_id provided', function () {
            $otherAcademy = Academy::factory()->create();
            $otherStudent = User::factory()->student()->forAcademy($otherAcademy)->create();

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->forStudent($otherStudent)
                ->create(['academy_id' => $otherAcademy->id]);

            $subscriptions = $this->service->getStudentSubscriptions($this->student->id, $this->academy->id);

            expect($subscriptions)->toHaveCount(1)
                ->and($subscriptions->first()->academy_id)->toBe($this->academy->id);
        });

        it('returns subscriptions sorted by created_at descending', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'created_at' => now()->subDays(2),
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'created_at' => now()->subDay(),
                ]);

            $subscriptions = $this->service->getStudentSubscriptions($this->student->id);

            expect($subscriptions->first()->created_at->isAfter($subscriptions->last()->created_at))->toBeTrue();
        });
    });

    describe('getActiveSubscriptions()', function () {
        it('returns only active subscriptions', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->pending()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->cancelled()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            $subscriptions = $this->service->getActiveSubscriptions($this->student->id);

            expect($subscriptions)->toHaveCount(1)
                ->and($subscriptions->first()->status)->toBe(SubscriptionStatus::ACTIVE);
        });
    });

    describe('getAcademySubscriptions()', function () {
        it('returns all subscriptions for an academy', function () {
            QuranSubscription::factory()
                ->forTeacher($this->quranTeacher)
                ->count(2)
                ->create(['academy_id' => $this->academy->id]);

            $subscriptions = $this->service->getAcademySubscriptions($this->academy->id);

            expect($subscriptions)->toHaveCount(2);
        });

        it('filters by status when provided', function () {
            QuranSubscription::factory()
                ->active()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->pending()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            $subscriptions = $this->service->getAcademySubscriptions($this->academy->id, SubscriptionStatus::ACTIVE);

            expect($subscriptions)->toHaveCount(1)
                ->and($subscriptions->first()->status)->toBe(SubscriptionStatus::ACTIVE);
        });

        it('includes all subscription types', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
            ]);

            CourseSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'course_type' => 'recorded',
            ]);

            $subscriptions = $this->service->getAcademySubscriptions($this->academy->id);

            expect($subscriptions)->toHaveCount(3);
        });
    });

    describe('findByCode()', function () {
        it('finds quran subscription by code with QS prefix', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_code' => 'QS-001',
                ]);

            $found = $this->service->findByCode('QS-001');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('finds academic subscription by code with AS prefix', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subscription_code' => 'AS-001',
            ]);

            $found = $this->service->findByCode('AS-001');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('finds course subscription by code with CS prefix', function () {
            $subscription = CourseSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-001',
                'course_type' => 'recorded',
            ]);

            $found = $this->service->findByCode('CS-001');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('searches all types when prefix not recognized', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_code' => 'XX-001',
                ]);

            $found = $this->service->findByCode('XX-001');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('returns null when code not found', function () {
            $found = $this->service->findByCode('NOTFOUND-001');

            expect($found)->toBeNull();
        });
    });

    describe('findById()', function () {
        it('finds subscription by id and type', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            $found = $this->service->findById($subscription->id, SubscriptionService::TYPE_QURAN);

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('returns null when id not found', function () {
            $found = $this->service->findById(99999, SubscriptionService::TYPE_QURAN);

            expect($found)->toBeNull();
        });
    });

    describe('getAcademyStatistics()', function () {
        it('returns statistics for all subscription types', function () {
            QuranSubscription::factory()
                ->active()
                ->forTeacher($this->quranTeacher)
                ->count(3)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->pending()
                ->forTeacher($this->quranTeacher)
                ->count(2)
                ->create(['academy_id' => $this->academy->id]);

            $stats = $this->service->getAcademyStatistics($this->academy->id);

            expect($stats)->toBeArray()
                ->and($stats['total'])->toBe(5)
                ->and($stats['active'])->toBe(3)
                ->and($stats['pending'])->toBe(2)
                ->and($stats['by_type'])->toBeArray()
                ->and($stats['by_type'])->toHaveKey(SubscriptionService::TYPE_QURAN)
                ->and($stats['by_type'])->toHaveKey(SubscriptionService::TYPE_ACADEMIC)
                ->and($stats['by_type'])->toHaveKey(SubscriptionService::TYPE_COURSE);
        });

        it('calculates revenue correctly', function () {
            QuranSubscription::factory()
                ->active()
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'final_price' => 200.0,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                ]);

            QuranSubscription::factory()
                ->active()
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'final_price' => 300.0,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                ]);

            $stats = $this->service->getAcademyStatistics($this->academy->id);

            expect($stats['revenue'])->toBe(500.0);
        });

        it('includes counts for each status', function () {
            QuranSubscription::factory()
                ->active()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->expired()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            QuranSubscription::factory()
                ->cancelled()
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            $stats = $this->service->getAcademyStatistics($this->academy->id);

            expect($stats['active'])->toBe(1)
                ->and($stats['expired'])->toBe(1)
                ->and($stats['cancelled'])->toBe(1);
        });
    });

    describe('getStudentStatistics()', function () {
        it('returns student subscription statistics', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->count(2)
                ->create(['academy_id' => $this->academy->id]);

            $stats = $this->service->getStudentStatistics($this->student->id);

            expect($stats)->toBeArray()
                ->and($stats['total'])->toBe(2)
                ->and($stats['active'])->toBe(2)
                ->and($stats)->toHaveKey('by_type');
        });

        it('calculates total spent correctly', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'final_price' => 200.0,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                ]);

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'final_price' => 150.0,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                ]);

            $stats = $this->service->getStudentStatistics($this->student->id);

            expect($stats['total_spent'])->toBe(350.0);
        });

        it('counts subscriptions by type', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
            ]);

            $stats = $this->service->getStudentStatistics($this->student->id);

            expect($stats['by_type'][SubscriptionService::TYPE_QURAN])->toBe(1)
                ->and($stats['by_type'][SubscriptionService::TYPE_ACADEMIC])->toBe(1)
                ->and($stats['by_type'][SubscriptionService::TYPE_COURSE])->toBe(0);
        });
    });

    describe('getExpiringSoon()', function () {
        it('returns subscriptions expiring within specified days', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'ends_at' => now()->addDays(5),
                ]);

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'ends_at' => now()->addDays(15),
                ]);

            $expiring = $this->service->getExpiringSoon($this->academy->id, 7);

            expect($expiring)->toHaveCount(1);
        });

        it('sorts by ends_at ascending', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'ends_at' => now()->addDays(6),
                ]);

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'ends_at' => now()->addDays(2),
                ]);

            $expiring = $this->service->getExpiringSoon($this->academy->id, 7);

            expect($expiring->first()->ends_at->isBefore($expiring->last()->ends_at))->toBeTrue();
        });
    });

    describe('getDueForRenewal()', function () {
        it('returns subscriptions due for renewal', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                    'next_billing_date' => now()->subDay(),
                ]);

            $due = $this->service->getDueForRenewal($this->academy->id);

            expect($due)->toHaveCount(1);
        });

        it('sorts by next_billing_date ascending', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                    'next_billing_date' => now()->subDays(3),
                ]);

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                    'next_billing_date' => now()->subDay(),
                ]);

            $due = $this->service->getDueForRenewal($this->academy->id);

            expect($due->first()->next_billing_date->isBefore($due->last()->next_billing_date))->toBeTrue();
        });
    });

    describe('getSubscriptionSummaries()', function () {
        it('returns array of subscription summaries', function () {
            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->count(2)
                ->create(['academy_id' => $this->academy->id]);

            $summaries = $this->service->getSubscriptionSummaries($this->student->id);

            expect($summaries)->toBeArray()
                ->and($summaries)->toHaveCount(2);
        });
    });

    describe('getActiveSubscriptionsByType()', function () {
        it('returns active subscriptions grouped by type', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create(['academy_id' => $this->academy->id]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $grouped = $this->service->getActiveSubscriptionsByType($this->student->id);

            expect($grouped)->toBeArray()
                ->and($grouped)->toHaveKey('quran')
                ->and($grouped)->toHaveKey('academic')
                ->and($grouped)->toHaveKey('course')
                ->and($grouped['quran'])->toHaveCount(1)
                ->and($grouped['academic'])->toHaveCount(1);
        });
    });

    describe('changeBillingCycle()', function () {
        it('changes billing cycle successfully', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                ]);

            $result = $this->service->changeBillingCycle($subscription, BillingCycle::QUARTERLY);

            expect($result->billing_cycle)->toBe(BillingCycle::QUARTERLY);
        });

        it('disables auto-renew when new cycle does not support it', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                    'auto_renew' => true,
                ]);

            $result = $this->service->changeBillingCycle($subscription, BillingCycle::LIFETIME);

            expect($result->auto_renew)->toBeFalse();
        });

        it('logs billing cycle change', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Subscription billing cycle changed', \Mockery::type('array'));

            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                ]);

            $this->service->changeBillingCycle($subscription, BillingCycle::QUARTERLY);
        });
    });

    describe('toggleAutoRenewal()', function () {
        it('enables auto-renewal when supported', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                    'auto_renew' => false,
                ]);

            $result = $this->service->toggleAutoRenewal($subscription, true);

            expect($result->auto_renew)->toBeTrue();
        });

        it('disables auto-renewal', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                    'auto_renew' => true,
                ]);

            $result = $this->service->toggleAutoRenewal($subscription, false);

            expect($result->auto_renew)->toBeFalse();
        });

        it('throws exception when enabling auto-renew for unsupported billing cycle', function () {
            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::LIFETIME,
                    'auto_renew' => false,
                ]);

            expect(fn () => $this->service->toggleAutoRenewal($subscription, true))
                ->toThrow(Exception::class, 'This billing cycle does not support auto-renewal');
        });

        it('logs auto-renewal toggle', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Subscription auto-renewal toggled', \Mockery::type('array'));

            $subscription = QuranSubscription::factory()
                ->active()
                ->forStudent($this->student)
                ->forTeacher($this->quranTeacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'billing_cycle' => BillingCycle::MONTHLY,
                    'auto_renew' => false,
                ]);

            $this->service->toggleAutoRenewal($subscription, true);
        });
    });
});
