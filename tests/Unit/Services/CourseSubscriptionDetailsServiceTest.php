<?php

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\User;
use App\Services\CourseSubscriptionDetailsService;
use Carbon\Carbon;

describe('CourseSubscriptionDetailsService', function () {
    beforeEach(function () {
        $this->service = new CourseSubscriptionDetailsService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('getSubscriptionDetails()', function () {
        it('returns complete details for recorded course subscription', function () {
            $subscription = CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-TEST-001',
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'enrolled_at' => now(),
                'total_lessons' => 10,
                'completed_lessons' => 5,
                'watch_time_minutes' => 120,
                'progress_percentage' => 50,
                'lifetime_access' => true,
                'enrollment_type' => CourseSubscription::ENROLLMENT_TYPE_PAID,
                'total_price' => 500,
                'final_price' => 450,
                'price_paid' => 450,
                'original_price' => 500,
                'currency' => 'SAR',
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)
                ->toBeArray()
                ->toHaveKey('subscription_type', CourseSubscription::COURSE_TYPE_RECORDED)
                ->toHaveKey('status', SubscriptionStatus::ACTIVE)
                ->toHaveKey('payment_status', SubscriptionPaymentStatus::PAID)
                ->toHaveKey('starts_at')
                ->toHaveKey('ends_at')
                ->toHaveKey('enrolled_at')
                ->toHaveKey('total_sessions', 10)
                ->toHaveKey('sessions_used', 5)
                ->toHaveKey('sessions_remaining', 5)
                ->toHaveKey('sessions_percentage')
                ->toHaveKey('billing_cycle')
                ->toHaveKey('billing_cycle_text', 'شراء لمرة واحدة')
                ->toHaveKey('billing_cycle_ar', 'شراء لمرة واحدة')
                ->toHaveKey('currency', 'SAR')
                ->toHaveKey('total_price', 500)
                ->toHaveKey('final_price', 450)
                ->toHaveKey('price_paid', 450)
                ->toHaveKey('original_price', 500)
                ->toHaveKey('status_badge_class')
                ->toHaveKey('payment_status_badge_class')
                ->toHaveKey('lifetime_access', true)
                ->toHaveKey('access_status')
                ->toHaveKey('enrollment_type', CourseSubscription::ENROLLMENT_TYPE_PAID)
                ->toHaveKey('enrollment_type_label')
                ->toHaveKey('progress_percentage', 50)
                ->toHaveKey('completion_rate')
                ->toHaveKey('course_type', CourseSubscription::COURSE_TYPE_RECORDED)
                ->toHaveKey('is_interactive', false)
                ->toHaveKey('is_recorded', true)
                ->toHaveKey('completed_lessons', 5)
                ->toHaveKey('total_lessons', 10)
                ->toHaveKey('watch_time_minutes', 120)
                ->toHaveKey('watch_time_formatted')
                ->toHaveKey('certificate_issued')
                ->toHaveKey('can_earn_certificate')
                ->toHaveKey('completion_certificate_url');
        });

        it('returns complete details for interactive course subscription', function () {
            $subscription = CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-TEST-002',
                'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'enrolled_at' => now(),
                'total_possible_attendance' => 20,
                'attendance_count' => 15,
                'progress_percentage' => 75,
                'final_grade' => 85.5,
                'lifetime_access' => false,
                'enrollment_type' => CourseSubscription::ENROLLMENT_TYPE_PAID,
                'total_price' => 1000,
                'final_price' => 900,
                'price_paid' => 900,
                'currency' => 'SAR',
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)
                ->toBeArray()
                ->toHaveKey('subscription_type', CourseSubscription::COURSE_TYPE_INTERACTIVE)
                ->toHaveKey('course_type', CourseSubscription::COURSE_TYPE_INTERACTIVE)
                ->toHaveKey('is_interactive', true)
                ->toHaveKey('is_recorded', false)
                ->toHaveKey('attendance_count', 15)
                ->toHaveKey('total_possible_attendance', 20)
                ->toHaveKey('attendance_percentage')
                ->toHaveKey('final_grade', 85.5)
                ->toHaveKey('has_passed', true)
                ->toHaveKey('total_sessions', 20)
                ->toHaveKey('sessions_used', 15)
                ->toHaveKey('sessions_remaining', 5);

            expect($details['attendance_percentage'])->toBe(75.0);
        });

        it('includes quiz data when available', function () {
            $subscription = CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-TEST-003',
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'quiz_attempts' => 3,
                'quiz_passed' => true,
                'final_score' => 92.5,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)
                ->toHaveKey('quiz_attempts', 3)
                ->toHaveKey('quiz_passed', true)
                ->toHaveKey('final_score', 92.5);
        });

        it('includes certificate information', function () {
            $subscription = CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-TEST-004',
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'certificate_issued' => true,
                'completion_certificate_url' => 'https://example.com/certificate.pdf',
                'progress_percentage' => 100,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)
                ->toHaveKey('certificate_issued', true)
                ->toHaveKey('can_earn_certificate', false)
                ->toHaveKey('completion_certificate_url', 'https://example.com/certificate.pdf');
        });

        it('calculates sessions percentage correctly', function () {
            $subscription = CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_code' => 'CS-TEST-005',
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'total_lessons' => 20,
                'completed_lessons' => 10,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(50.0);
        });

        it('handles zero total sessions gracefully', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-006',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'total_lessons' => 0,
                'completed_lessons' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(0.0);
        });

        it('includes last accessed timestamp', function () {
            $lastAccessed = now()->subDays(2);
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-007',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'last_accessed_at' => $lastAccessed,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['last_accessed_at'])->toBeInstanceOf(Carbon::class);
        });

        it('includes completion date when available', function () {
            $completionDate = now()->subDay();
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-008',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'completion_date' => $completionDate,
                'status' => SubscriptionStatus::COMPLETED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['completion_date'])->toBeInstanceOf(Carbon::class);
        });
    });

    describe('getRenewalMessage()', function () {
        it('returns null for lifetime access subscriptions', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-009',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => true,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toBeNull();
        });

        it('returns null when ends_at is not set', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-010',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => null,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toBeNull();
        });

        it('returns expiry message when access has expired', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-011',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => now()->subDay(),
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toBe('انتهت صلاحية الوصول للدورة. يرجى تجديد الاشتراك للمتابعة.');
        });

        it('returns warning message when access expires in 7 days', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-012',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => now()->addDays(7),
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toContain('ستنتهي صلاحية الوصول للدورة بعد');
        });

        it('returns warning message when access expires in 5 days', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-013',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => now()->addDays(5),
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toContain('ستنتهي صلاحية الوصول للدورة بعد');
        });

        it('returns null when access expires in more than 7 days', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-014',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => now()->addDays(30),
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toBeNull();
        });

        it('returns warning message when access expires in exactly 1 day', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-015',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'lifetime_access' => false,
                'ends_at' => now()->addDay(),
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $message = $this->service->getRenewalMessage($subscription);

            expect($message)->toContain('ستنتهي صلاحية الوصول للدورة بعد');
        });
    });

    describe('getProgressMessage()', function () {
        describe('interactive courses', function () {
            it('returns completion message when attendance is 90% or more', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-016',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                    'total_possible_attendance' => 20,
                    'attendance_count' => 18,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('أنت على وشك إنهاء الدورة! واصل التقدم الرائع.');
            });

            it('returns encouragement message when attendance is between 50% and 90%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-017',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                    'total_possible_attendance' => 20,
                    'attendance_count' => 14,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toContain('لقد أكملت')
                    ->and($message)->toContain('% من الدورة. استمر!');
            });

            it('returns started message when attendance is below 50%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-018',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                    'total_possible_attendance' => 20,
                    'attendance_count' => 5,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('لقد بدأت الدورة. واصل الحضور لتحقيق أفضل النتائج.');
            });

            it('returns not started message when attendance is 0%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-019',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                    'total_possible_attendance' => 20,
                    'attendance_count' => 0,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('لم تبدأ الدورة بعد. انتظر الجلسة القادمة للبدء.');
            });
        });

        describe('recorded courses', function () {
            it('returns congratulations message when progress is 100%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-020',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                    'progress_percentage' => 100,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('مبروك! لقد أكملت الدورة بنجاح.');
            });

            it('returns near completion message when progress is between 90% and 100%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-021',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                    'progress_percentage' => 95,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('أنت على وشك إنهاء الدورة! واصل التقدم الرائع.');
            });

            it('returns encouragement message when progress is between 50% and 90%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-022',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                    'progress_percentage' => 70,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toContain('لقد أكملت')
                    ->and($message)->toContain('% من الدورة. استمر!');
            });

            it('returns started message when progress is below 50%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-023',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                    'progress_percentage' => 25,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('لقد بدأت الدورة. واصل المشاهدة لإكمال الدروس.');
            });

            it('returns start message when progress is 0%', function () {
                $subscription = CourseSubscription::create([
                    'subscription_code' => 'CS-TEST-024',
                'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                    'progress_percentage' => 0,
                ]);

                $message = $this->service->getProgressMessage($subscription);

                expect($message)->toBe('ابدأ الدورة الآن لتحقيق أهدافك التعليمية.');
            });
        });

        it('handles missing attendance_percentage for interactive courses', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-025',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                'total_possible_attendance' => 0,
                'attendance_count' => 0,
            ]);

            $message = $this->service->getProgressMessage($subscription);

            expect($message)->toBe('لم تبدأ الدورة بعد. انتظر الجلسة القادمة للبدء.');
        });

        it('handles missing progress_percentage for recorded courses', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-026',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'progress_percentage' => null,
            ]);

            $message = $this->service->getProgressMessage($subscription);

            expect($message)->toBe('ابدأ الدورة الآن لتحقيق أهدافك التعليمية.');
        });
    });

    describe('status badge classes', function () {
        it('returns correct badge class for active status', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-027',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('green');
        });

        it('returns correct badge class for pending status', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-028',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SubscriptionStatus::PENDING,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('yellow');
        });

        it('returns correct badge class for completed status', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-029',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SubscriptionStatus::COMPLETED,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['status_badge_class'])->toContain('purple');
        });

        it('returns correct badge class for paid payment status', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-030',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status_badge_class'])->toContain('green');
        });

        it('returns correct badge class for pending payment status', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-031',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'payment_status' => SubscriptionPaymentStatus::PENDING,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['payment_status_badge_class'])->toContain('yellow');
        });
    });

    describe('edge cases', function () {
        it('handles subscription with all null optional fields', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-032',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'last_accessed_at' => null,
                'completion_date' => null,
                'final_grade' => null,
                'final_score' => null,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details)
                ->toBeArray()
                ->toHaveKey('last_accessed_at', null)
                ->toHaveKey('completion_date', null)
                ->toHaveKey('final_grade', null)
                ->toHaveKey('final_score', null);
        });

        it('handles interactive course with zero attendance', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-033',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_INTERACTIVE,
                'total_possible_attendance' => 10,
                'attendance_count' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['attendance_percentage'])->toBe(0.0)
                ->and($details['sessions_used'])->toBe(0)
                ->and($details['sessions_remaining'])->toBe(10);
        });

        it('handles recorded course with zero progress', function () {
            $subscription = CourseSubscription::create([
                'subscription_code' => 'CS-TEST-034',
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'course_type' => CourseSubscription::COURSE_TYPE_RECORDED,
                'total_lessons' => 15,
                'completed_lessons' => 0,
                'progress_percentage' => 0,
            ]);

            $details = $this->service->getSubscriptionDetails($subscription);

            expect($details['sessions_percentage'])->toBe(0.0)
                ->and($details['progress_percentage'])->toBe(0);
        });
    });
});
