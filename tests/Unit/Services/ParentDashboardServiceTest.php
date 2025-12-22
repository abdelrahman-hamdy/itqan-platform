<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ParentDashboardService;

describe('ParentDashboardService', function () {
    beforeEach(function () {
        $this->service = new ParentDashboardService();
        $this->academy = Academy::factory()->create();
        $this->parent = ParentProfile::factory()->create([
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getDashboardData()', function () {
        it('returns dashboard data structure with all keys', function () {
            $result = $this->service->getDashboardData($this->parent);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['children', 'stats', 'upcoming_sessions', 'recent_activity']);
        });

        it('returns empty collection when parent has no children', function () {
            $result = $this->service->getDashboardData($this->parent);

            expect($result['children'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result['children'])->toBeEmpty();
        });

        it('returns children with user relationship loaded', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            $result = $this->service->getDashboardData($this->parent);

            expect($result['children'])->toHaveCount(1)
                ->and($result['children']->first()->relationLoaded('user'))->toBeTrue();
        });

        it('only returns children for the academy', function () {
            $otherAcademy = Academy::factory()->create();

            $studentInAcademy = User::factory()->student()->forAcademy($this->academy)->create();
            $studentOtherAcademy = User::factory()->student()->forAcademy($otherAcademy)->create();

            StudentProfile::factory()->create([
                'user_id' => $studentInAcademy->id,
                'parent_id' => $this->parent->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $studentOtherAcademy->id,
                'parent_id' => $this->parent->id,
            ]);

            $result = $this->service->getDashboardData($this->parent);

            expect($result['children'])->toHaveCount(1)
                ->and($result['children']->first()->user_id)->toBe($studentInAcademy->id);
        });
    });

    describe('getFamilyStatistics()', function () {
        it('returns statistics structure with all keys', function () {
            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKeys([
                    'total_children',
                    'active_subscriptions',
                    'upcoming_sessions',
                    'total_certificates',
                    'outstanding_payments',
                ]);
        });

        it('returns zero values when parent has no children', function () {
            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['total_children'])->toBe(0)
                ->and($stats['active_subscriptions'])->toBe(0)
                ->and($stats['upcoming_sessions'])->toBe(0)
                ->and($stats['total_certificates'])->toBe(0)
                ->and($stats['outstanding_payments'])->toBe(0);
        });

        it('counts total children correctly', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student1->id,
                'parent_id' => $this->parent->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student2->id,
                'parent_id' => $this->parent->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['total_children'])->toBe(2);
        });

        it('counts active quran subscriptions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['active_subscriptions'])->toBe(2);
        });

        it('counts active academic subscriptions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            AcademicSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['active_subscriptions'])->toBe(1);
        });

        it('counts active course subscriptions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            CourseSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['active_subscriptions'])->toBe(1);
        });

        it('aggregates all subscription types', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            AcademicSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            CourseSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['active_subscriptions'])->toBe(3);
        });

        it('counts upcoming quran sessions in next 7 days', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(3),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(5),
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['upcoming_sessions'])->toBe(2);
        });

        it('counts upcoming academic sessions in next 7 days', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['upcoming_sessions'])->toBe(1);
        });

        it('excludes sessions beyond 7 days', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(8),
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['upcoming_sessions'])->toBe(0);
        });

        it('excludes completed sessions from upcoming count', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->addDays(3),
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['upcoming_sessions'])->toBe(0);
        });

        it('counts total certificates across all children', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student1->id,
                'parent_id' => $this->parent->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student2->id,
                'parent_id' => $this->parent->id,
            ]);

            Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student1->id,
            ]);

            Certificate::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student2->id,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['total_certificates'])->toBe(3);
        });

        it('calculates outstanding payments sum', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'pending',
                'amount' => 100.50,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'processing',
                'amount' => 50.25,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['outstanding_payments'])->toBe(150.75);
        });

        it('excludes completed payments from outstanding', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'completed',
                'amount' => 100.00,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'pending',
                'amount' => 50.00,
            ]);

            $stats = $this->service->getFamilyStatistics($this->parent);

            expect($stats['outstanding_payments'])->toBe(50.00);
        });
    });

    describe('getUpcomingSessionsForAllChildren()', function () {
        it('returns empty collection when no children exist', function () {
            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($sessions)->toBeEmpty();
        });

        it('returns quran sessions for children', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toHaveCount(1);
        });

        it('returns academic sessions for children', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(4),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toHaveCount(1);
        });

        it('merges quran and academic sessions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(4),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toHaveCount(2);
        });

        it('sorts sessions by scheduled_at ascending', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            $session1 = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(5),
            ]);

            $session2 = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(1),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions->first()->id)->toBe($session2->id)
                ->and($sessions->last()->id)->toBe($session1->id);
        });

        it('respects custom days parameter', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(5),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent, 3);

            expect($sessions)->toHaveCount(1);
        });

        it('eager loads necessary relationships', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions->first()->relationLoaded('quranTeacher'))->toBeTrue()
                ->and($sessions->first()->relationLoaded('student'))->toBeTrue();
        });

        it('only includes scheduled status sessions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->addDays(2),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(3),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toHaveCount(1)
                ->and($sessions->first()->status)->toBe(SessionStatus::SCHEDULED);
        });

        it('aggregates sessions from multiple children', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student1->id,
                'parent_id' => $this->parent->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student2->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student1->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student2->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(3),
            ]);

            $sessions = $this->service->getUpcomingSessionsForAllChildren($this->parent);

            expect($sessions)->toHaveCount(2);
        });
    });

    describe('getRecentActivity()', function () {
        it('returns array of activities', function () {
            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toBeArray();
        });

        it('returns empty array when no activity exists', function () {
            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toBeEmpty();
        });

        it('includes completed quran sessions in activities', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(2),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(1)
                ->and($activities[0]['type'])->toBe('session_completed')
                ->and($activities[0])->toHaveKeys(['type', 'message', 'timestamp', 'icon', 'color']);
        });

        it('includes completed academic sessions in activities', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(1)
                ->and($activities[0]['type'])->toBe('session_completed');
        });

        it('includes certificates in activities', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'issued_at' => now()->subDays(1),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(1)
                ->and($activities[0]['type'])->toBe('certificate_issued');
        });

        it('includes completed payments in activities', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'completed',
                'amount' => 150.00,
                'currency' => 'SAR',
                'created_at' => now()->subDays(1),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(1)
                ->and($activities[0]['type'])->toBe('payment_completed');
        });

        it('sorts activities by timestamp descending', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(5),
            ]);

            Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'issued_at' => now()->subDays(1),
            ]);

            Payment::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'status' => 'completed',
                'amount' => 100.00,
                'created_at' => now()->subDays(3),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(3)
                ->and($activities[0]['type'])->toBe('certificate_issued')
                ->and($activities[1]['type'])->toBe('payment_completed')
                ->and($activities[2]['type'])->toBe('session_completed');
        });

        it('respects custom limit parameter', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
            ]);

            Certificate::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'issued_at' => now()->subDays(2),
            ]);

            $activities = $this->service->getRecentActivity($this->parent, 5);

            expect($activities)->toHaveCount(5);
        });

        it('defaults to limit of 10 activities', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(10);
        });

        it('aggregates activities from multiple children', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student1->id,
                'parent_id' => $this->parent->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student2->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student1->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
            ]);

            Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student2->id,
                'issued_at' => now()->subDays(2),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(2);
        });

        it('only includes completed sessions not pending or scheduled', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->subDays(1),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(2),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities)->toHaveCount(1)
                ->and($activities[0]['type'])->toBe('session_completed');
        });

        it('includes student name in activity messages', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create([
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $this->parent->id,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
            ]);

            $activities = $this->service->getRecentActivity($this->parent);

            expect($activities[0]['message'])->toContain('أحمد محمد');
        });
    });
});
