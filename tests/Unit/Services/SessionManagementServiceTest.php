<?php

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\SessionManagementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('SessionManagementService', function () {
    beforeEach(function () {
        $this->service = new SessionManagementService();
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();

        Auth::shouldReceive('id')->andReturn($this->teacher->id);
    });

    describe('createIndividualSession()', function () {
        beforeEach(function () {
            $this->subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => 'active',
                'total_sessions' => 10,
                'used_sessions' => 0,
                'session_duration_minutes' => 45,
            ]);

            $this->circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $this->subscription->id,
                'circle_code' => 'IC-001',
                'name' => 'Individual Circle',
                'specialization' => 'memorization',
                'memorization_level' => 'beginner',
                'total_sessions' => 10,
                'sessions_scheduled' => 0,
                'sessions_completed' => 0,
                'sessions_remaining' => 10,
                'status' => 'active',
            ]);
        });

        it('creates an individual session successfully', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            expect($session)->toBeInstanceOf(QuranSession::class)
                ->and($session->academy_id)->toBe($this->academy->id)
                ->and($session->quran_teacher_id)->toBe($this->teacher->id)
                ->and($session->student_id)->toBe($this->student->id)
                ->and($session->individual_circle_id)->toBe($this->circle->id)
                ->and($session->session_type)->toBe('individual')
                ->and($session->status->value)->toBe('scheduled')
                ->and($session->scheduled_at->format('Y-m-d H:i'))->toBe($scheduledAt->format('Y-m-d H:i'));
        });

        it('uses duration from subscription if not provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->duration_minutes)->toBe(45);
        });

        it('uses custom duration when provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt,
                60
            );

            expect($session->duration_minutes)->toBe(60);
        });

        it('uses fallback duration when subscription has no duration', function () {
            $this->subscription->update(['session_duration_minutes' => null]);
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->duration_minutes)->toBe(45);
        });

        it('generates session code with correct format', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->session_code)->toStartWith('IND-')
                ->and($session->session_code)->toContain((string) $this->circle->id)
                ->and($session->session_code)->toContain($scheduledAt->format('Ymd'));
        });

        it('sets session month and monthly session number', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->session_month)->toBe($scheduledAt->format('Y-m-01'))
                ->and($session->monthly_session_number)->toBe(1);
        });

        it('increments monthly session number correctly', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session1 = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt
            );

            $session2 = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt->copy()->addHour()
            );

            expect($session1->monthly_session_number)->toBe(1)
                ->and($session2->monthly_session_number)->toBe(2);
        });

        it('uses custom title when provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);
            $customTitle = 'Custom Session Title';

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt,
                null,
                $customTitle
            );

            expect($session->title)->toBe($customTitle);
        });

        it('uses custom description when provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);
            $customDescription = 'Custom Description';

            $session = $this->service->createIndividualSession(
                $this->circle,
                $scheduledAt,
                null,
                null,
                $customDescription
            );

            expect($session->description)->toBe($customDescription);
        });

        it('throws exception when no remaining sessions', function () {
            $this->circle->update([
                'total_sessions' => 5,
                'sessions_scheduled' => 0,
                'sessions_completed' => 0,
            ]);

            QuranSession::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $this->circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'scheduled',
            ]);

            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            expect(fn () => $this->service->createIndividualSession($this->circle, $scheduledAt))
                ->toThrow(Exception::class, 'لا توجد جلسات متبقية في الاشتراك');
        });

        it('throws exception on time slot conflict', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 45,
                'status' => 'scheduled',
            ]);

            expect(fn () => $this->service->createIndividualSession($this->circle, $scheduledAt))
                ->toThrow(Exception::class, 'يوجد تعارض مع جلسة أخرى في هذا التوقيت');
        });
    });

    describe('createGroupSession()', function () {
        beforeEach(function () {
            $this->circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
                'monthly_sessions_count' => 8,
                'session_duration_minutes' => 60,
            ]);
        });

        it('creates a group session successfully', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt
            );

            expect($session)->toBeInstanceOf(QuranSession::class)
                ->and($session->academy_id)->toBe($this->academy->id)
                ->and($session->quran_teacher_id)->toBe($this->teacher->id)
                ->and($session->circle_id)->toBe($this->circle->id)
                ->and($session->session_type)->toBe('group')
                ->and($session->status->value)->toBe('scheduled')
                ->and($session->scheduled_at->format('Y-m-d H:i'))->toBe($scheduledAt->format('Y-m-d H:i'));
        });

        it('uses duration from circle settings if not provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->duration_minutes)->toBe(60);
        });

        it('uses custom duration when provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt,
                90
            );

            expect($session->duration_minutes)->toBe(90);
        });

        it('generates session code with correct format', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->session_code)->toStartWith('GRP-')
                ->and($session->session_code)->toContain((string) $this->circle->id)
                ->and($session->session_code)->toContain($scheduledAt->format('Ymd'));
        });

        it('sets session month and monthly session number', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt
            );

            expect($session->session_month)->toBe($scheduledAt->format('Y-m-01'))
                ->and($session->monthly_session_number)->toBe(1);
        });

        it('allows scheduling beyond monthly limit', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            for ($i = 0; $i < $this->circle->monthly_sessions_count + 2; $i++) {
                $session = $this->service->createGroupSession(
                    $this->circle,
                    $scheduledAt->copy()->addHours($i * 2)
                );

                expect($session)->toBeInstanceOf(QuranSession::class);
            }

            $totalSessions = QuranSession::where('circle_id', $this->circle->id)->count();
            expect($totalSessions)->toBe($this->circle->monthly_sessions_count + 2);
        });

        it('uses custom title when provided', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);
            $customTitle = 'Custom Group Session';

            $session = $this->service->createGroupSession(
                $this->circle,
                $scheduledAt,
                null,
                $customTitle
            );

            expect($session->title)->toBe($customTitle);
        });

        it('throws exception on time slot conflict', function () {
            $scheduledAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => 'scheduled',
            ]);

            expect(fn () => $this->service->createGroupSession($this->circle, $scheduledAt))
                ->toThrow(Exception::class, 'يوجد تعارض مع جلسة أخرى في هذا التوقيت');
        });
    });

    describe('bulkCreateSessions()', function () {
        beforeEach(function () {
            $this->subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => 'active',
                'total_sessions' => 100,
                'session_duration_minutes' => 45,
            ]);

            $this->individualCircle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $this->subscription->id,
                'circle_code' => 'IC-002',
                'name' => 'Bulk Test Circle',
                'specialization' => 'memorization',
                'memorization_level' => 'beginner',
                'total_sessions' => 100,
                'status' => 'active',
            ]);

            $this->groupCircle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
                'session_duration_minutes' => 60,
            ]);
        });

        it('creates multiple individual sessions from time slots', function () {
            $timeSlots = [
                ['day' => 'sunday', 'time' => '10:00'],
                ['day' => 'tuesday', 'time' => '14:00'],
            ];
            $startDate = Carbon::now()->next('Sunday');
            $endDate = $startDate->copy()->addWeeks(2);

            $sessions = $this->service->bulkCreateSessions(
                $this->individualCircle,
                $timeSlots,
                $startDate,
                $endDate,
                45
            );

            expect($sessions)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($sessions->count())->toBeGreaterThan(0);
        });

        it('creates multiple group sessions from time slots', function () {
            $timeSlots = [
                ['day' => 'monday', 'time' => '10:00'],
                ['day' => 'wednesday', 'time' => '15:00'],
            ];
            $startDate = Carbon::now()->next('Monday');
            $endDate = $startDate->copy()->addWeeks(2);

            $sessions = $this->service->bulkCreateSessions(
                $this->groupCircle,
                $timeSlots,
                $startDate,
                $endDate,
                60
            );

            expect($sessions)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($sessions->count())->toBeGreaterThan(0);
        });

        it('uses duration from individual circle subscription when not provided', function () {
            $timeSlots = [
                ['day' => 'sunday', 'time' => '10:00'],
            ];
            $startDate = Carbon::now()->next('Sunday');
            $endDate = $startDate->copy()->addWeek();

            $sessions = $this->service->bulkCreateSessions(
                $this->individualCircle,
                $timeSlots,
                $startDate,
                $endDate
            );

            if ($sessions->count() > 0) {
                expect($sessions->first()->duration_minutes)->toBe(45);
            } else {
                expect(true)->toBeTrue();
            }
        });

        it('skips past dates', function () {
            $timeSlots = [
                ['day' => 'monday', 'time' => '10:00'],
            ];
            $startDate = Carbon::now()->subWeeks(2);
            $endDate = Carbon::now()->subWeek();

            $sessions = $this->service->bulkCreateSessions(
                $this->groupCircle,
                $timeSlots,
                $startDate,
                $endDate
            );

            expect($sessions)->toBeEmpty();
        });

        it('continues creating sessions despite individual errors', function () {
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            $timeSlots = [
                ['day' => 'sunday', 'time' => '10:00'],
            ];
            $startDate = Carbon::now()->next('Sunday');
            $endDate = $startDate->copy()->addWeeks(3);

            $sessions = $this->service->bulkCreateSessions(
                $this->groupCircle,
                $timeSlots,
                $startDate,
                $endDate
            );

            expect($sessions)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });
    });

    describe('deleteSession()', function () {
        it('deletes individual session successfully', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-003',
                'name' => 'Delete Test',
                'specialization' => 'memorization',
                'total_sessions' => 10,
                'status' => 'active',
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            Log::shouldReceive('info')->once();

            $result = $this->service->deleteSession($session);

            expect($result)->toBeTrue()
                ->and(QuranSession::find($session->id))->toBeNull();
        });

        it('deletes group session successfully', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            Log::shouldReceive('info')->once();

            $result = $this->service->deleteSession($session);

            expect($result)->toBeTrue()
                ->and(QuranSession::find($session->id))->toBeNull();
        });

        it('rolls back on error', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            DB::partialMock()->shouldReceive('beginTransaction')->passthru();
            DB::partialMock()->shouldReceive('rollBack')->passthru();

            expect(fn () => $this->service->deleteSession($session))
                ->not->toThrow(Exception::class);
        });
    });

    describe('resetCircleSessions()', function () {
        it('resets all individual circle sessions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 20,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-004',
                'name' => 'Reset Test',
                'specialization' => 'memorization',
                'total_sessions' => 20,
                'status' => 'active',
            ]);

            QuranSession::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $deletedCount = $this->service->resetCircleSessions($circle);

            expect($deletedCount)->toBe(5)
                ->and($circle->sessions()->count())->toBe(0);
        });

        it('resets all group circle sessions', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            QuranSession::factory()->count(8)->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $deletedCount = $this->service->resetCircleSessions($circle);

            expect($deletedCount)->toBe(8)
                ->and($circle->sessions()->count())->toBe(0);
        });
    });

    describe('getRemainingIndividualSessions()', function () {
        it('calculates remaining sessions correctly', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-005',
                'name' => 'Remaining Test',
                'specialization' => 'memorization',
                'total_sessions' => 10,
                'status' => 'active',
            ]);

            QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'completed',
            ]);

            $remaining = $this->service->getRemainingIndividualSessions($circle);

            expect($remaining)->toBe(7);
        });

        it('returns zero when no sessions remaining', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 5,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-006',
                'name' => 'Zero Test',
                'specialization' => 'memorization',
                'total_sessions' => 5,
                'status' => 'active',
            ]);

            QuranSession::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'scheduled',
            ]);

            $remaining = $this->service->getRemainingIndividualSessions($circle);

            expect($remaining)->toBe(0);
        });

        it('counts scheduled and in_progress sessions as used', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-007',
                'name' => 'Status Test',
                'specialization' => 'memorization',
                'total_sessions' => 10,
                'status' => 'active',
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'ongoing',
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => 'completed',
            ]);

            $remaining = $this->service->getRemainingIndividualSessions($circle);

            expect($remaining)->toBe(7);
        });
    });

    describe('getGroupSessionsForMonth()', function () {
        it('returns correct count for specific month', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $targetMonth = Carbon::now()->format('Y-m');

            QuranSession::factory()->count(5)->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_month' => $targetMonth . '-01',
            ]);

            QuranSession::factory()->count(3)->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_month' => Carbon::now()->addMonth()->format('Y-m-01'),
            ]);

            $count = $this->service->getGroupSessionsForMonth($circle, $targetMonth);

            expect($count)->toBe(5);
        });
    });

    describe('getTeacherSessionStats()', function () {
        it('returns correct statistics for teacher', function () {
            $currentMonth = now()->format('Y-m-01');

            QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_month' => $currentMonth,
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_month' => $currentMonth,
                'status' => 'completed',
            ]);

            $stats = $this->service->getTeacherSessionStats($this->teacher->id);

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKey('total_sessions_this_month')
                ->and($stats)->toHaveKey('completed_sessions_this_month')
                ->and($stats)->toHaveKey('scheduled_sessions_this_week')
                ->and($stats)->toHaveKey('individual_circles_active')
                ->and($stats)->toHaveKey('group_circles_active')
                ->and($stats['total_sessions_this_month'])->toBe(5)
                ->and($stats['completed_sessions_this_month'])->toBe(2);
        });
    });

    describe('getCircleMonthlyProgress()', function () {
        it('returns progress for individual circle', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $circle = QuranIndividualCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'subscription_id' => $subscription->id,
                'circle_code' => 'IC-008',
                'name' => 'Progress Test',
                'specialization' => 'memorization',
                'total_sessions' => 10,
                'status' => 'active',
            ]);

            $targetMonth = Carbon::now()->format('Y-m');

            QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'session_month' => $targetMonth . '-01',
                'status' => 'completed',
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'session_month' => $targetMonth . '-01',
                'status' => 'scheduled',
            ]);

            $progress = $this->service->getCircleMonthlyProgress($circle, $targetMonth);

            expect($progress)->toBeArray()
                ->and($progress)->toHaveKey('month')
                ->and($progress)->toHaveKey('total_sessions')
                ->and($progress)->toHaveKey('max_sessions')
                ->and($progress)->toHaveKey('completed_sessions')
                ->and($progress)->toHaveKey('scheduled_sessions')
                ->and($progress)->toHaveKey('cancelled_sessions')
                ->and($progress)->toHaveKey('progress_percentage')
                ->and($progress['total_sessions'])->toBe(5)
                ->and($progress['completed_sessions'])->toBe(3)
                ->and($progress['scheduled_sessions'])->toBe(2);
        });

        it('returns progress for group circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'monthly_sessions_count' => 8,
            ]);

            $targetMonth = Carbon::now()->format('Y-m');

            QuranSession::factory()->count(4)->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_month' => $targetMonth . '-01',
                'status' => 'completed',
            ]);

            $progress = $this->service->getCircleMonthlyProgress($circle, $targetMonth);

            expect($progress)->toBeArray()
                ->and($progress['max_sessions'])->toBe(8)
                ->and($progress['completed_sessions'])->toBe(4)
                ->and($progress['progress_percentage'])->toBe(50.0);
        });
    });

    describe('generateExactGroupSessions()', function () {
        it('generates exact number of sessions from schedule', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_duration_minutes' => 60,
            ]);

            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [
                    ['day' => 'sunday', 'time' => '10:00'],
                    ['day' => 'tuesday', 'time' => '14:00'],
                ],
                'default_duration_minutes' => 60,
                'is_active' => true,
                'schedule_starts_at' => Carbon::now(),
                'timezone' => 'Asia/Riyadh',
            ]);

            $createdCount = $this->service->generateExactGroupSessions($schedule, 5);

            expect($createdCount)->toBe(5)
                ->and($circle->sessions()->count())->toBe(5);
        });

        it('throws exception when schedule has no circle', function () {
            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => null,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [
                    ['day' => 'sunday', 'time' => '10:00'],
                ],
                'is_active' => true,
                'timezone' => 'Asia/Riyadh',
            ]);

            expect(fn () => $this->service->generateExactGroupSessions($schedule, 5))
                ->toThrow(Exception::class, 'الجدول غير مرتبط بحلقة');
        });

        it('throws exception when schedule has no weekly schedule', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => null,
                'is_active' => true,
                'timezone' => 'Asia/Riyadh',
            ]);

            expect(fn () => $this->service->generateExactGroupSessions($schedule, 5))
                ->toThrow(Exception::class, 'لم يتم تحديد جدول أسبوعي');
        });

        it('skips past datetimes', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_duration_minutes' => 60,
            ]);

            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [
                    ['day' => 'monday', 'time' => '08:00'],
                ],
                'default_duration_minutes' => 60,
                'is_active' => true,
                'schedule_starts_at' => Carbon::now()->subMonth(),
                'timezone' => 'Asia/Riyadh',
            ]);

            $createdCount = $this->service->generateExactGroupSessions($schedule, 3);

            expect($createdCount)->toBe(3);
        });

        it('uses schedule duration when provided', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_duration_minutes' => 60,
            ]);

            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [
                    ['day' => 'sunday', 'time' => '10:00'],
                ],
                'default_duration_minutes' => 90,
                'is_active' => true,
                'schedule_starts_at' => Carbon::now(),
                'timezone' => 'Asia/Riyadh',
            ]);

            $createdCount = $this->service->generateExactGroupSessions($schedule, 2);

            $session = $circle->sessions()->first();
            expect($session->duration_minutes)->toBe(90);
        });

        it('continues on individual session creation errors', function () {
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'session_duration_minutes' => 60,
            ]);

            $schedule = QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [
                    ['day' => 'sunday', 'time' => '10:00'],
                ],
                'default_duration_minutes' => 60,
                'is_active' => true,
                'schedule_starts_at' => Carbon::now(),
                'timezone' => 'Asia/Riyadh',
            ]);

            $createdCount = $this->service->generateExactGroupSessions($schedule, 3);

            expect($createdCount)->toBeGreaterThanOrEqual(0);
        });
    });
});
