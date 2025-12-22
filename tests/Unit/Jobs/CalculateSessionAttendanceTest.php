<?php

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionAttendance;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('CalculateSessionAttendance', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('job configuration', function () {
        it('has correct retry configuration', function () {
            $job = new CalculateSessionAttendance();

            expect($job->tries)->toBe(3)
                ->and($job->backoff)->toBe(60);
        });

        it('implements ShouldQueue interface', function () {
            $job = new CalculateSessionAttendance();

            expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
        });
    });

    describe('job dispatch', function () {
        it('can be dispatched to queue', function () {
            Queue::fake();

            CalculateSessionAttendance::dispatch();

            Queue::assertPushed(CalculateSessionAttendance::class);
        });
    });

    describe('handle() - session selection', function () {
        it('processes completed quran sessions after grace period', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(55),
                'total_duration_minutes' => 55,
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(55)->toISOString(),
                        'duration_minutes' => 55,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->is_calculated)->toBeTrue()
                ->and($attendance->attendance_status)->not->toBeNull();
        });

        it('processes academic sessions after grace period', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'academic',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->is_calculated)->toBeTrue();
        });

        it('skips sessions within grace period', function () {
            $scheduledAt = now()->subMinutes(2);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => false,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $count = MeetingAttendance::where('is_calculated', true)->count();
            expect($count)->toBe(0);
        });

        it('skips sessions older than 7 days', function () {
            $scheduledAt = now()->subDays(8);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => false,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $count = MeetingAttendance::where('session_id', $session->id)
                ->where('is_calculated', true)
                ->count();
            expect($count)->toBe(0);
        });

        it('processes ongoing sessions that should be completed', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance = MeetingAttendance::where('session_id', $session->id)->first();
            expect($attendance->is_calculated)->toBeTrue();
        });
    });

    describe('handle() - attendance calculation', function () {
        it('marks attendance as attended when user attended full session', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(60),
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(60)->toISOString(),
                        'duration_minutes' => 60,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->attendance_status)->toBe(AttendanceStatus::ATTENDED->value)
                ->and($attendance->attendance_percentage)->toBeGreaterThanOrEqual(90);
        });

        it('marks attendance as late when user joined after tolerance', function () {
            $scheduledAt = now()->subMinutes(70);
            $lateJoinTime = $scheduledAt->copy()->addMinutes(20); // 20 min late (tolerance is 15)

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $lateJoinTime,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(60),
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $lateJoinTime->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(60)->toISOString(),
                        'duration_minutes' => 40,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->attendance_status)->toBe(AttendanceStatus::LATE->value)
                ->and($attendance->attendance_percentage)->toBeGreaterThanOrEqual(50);
        });

        it('marks attendance as leaved when user left early', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(20), // Left after 20 min (33%)
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(20)->toISOString(),
                        'duration_minutes' => 20,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->attendance_status)->toBe(AttendanceStatus::LEAVED->value)
                ->and($attendance->attendance_percentage)->toBeLessThan(50);
        });

        it('marks attendance as absent when user never joined', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => null,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->attendance_status)->toBe(AttendanceStatus::ABSENT->value)
                ->and($attendance->attendance_percentage)->toBe(0.0);
        });
    });

    describe('handle() - duration calculation with cycles', function () {
        it('calculates total duration from multiple join/leave cycles', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Multiple join/leave cycles totaling ~50 minutes
            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(20)->toISOString(),
                        'duration_minutes' => 20,
                    ],
                    [
                        'joined_at' => $scheduledAt->copy()->addMinutes(25)->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(55)->toISOString(),
                        'duration_minutes' => 30,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            expect($attendance->total_duration_minutes)->toBeGreaterThanOrEqual(45)
                ->and($attendance->attendance_percentage)->toBeGreaterThanOrEqual(75);
        });

        it('excludes preparation time before session start', function () {
            $scheduledAt = now()->subMinutes(70);
            $earlyJoinTime = $scheduledAt->copy()->subMinutes(10); // Joined 10 min early

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $earlyJoinTime,
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $earlyJoinTime->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(60)->toISOString(),
                        'duration_minutes' => 70,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            // Should count only 60 minutes (from scheduled_at to end), not 70
            expect($attendance->total_duration_minutes)->toBeLessThanOrEqual(60)
                ->and($attendance->attendance_percentage)->toBeLessThanOrEqual(100);
        });

        it('excludes buffer time after session end', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $attendance = MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(75), // Stayed 15 min extra
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(75)->toISOString(),
                        'duration_minutes' => 75,
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $attendance->refresh();
            // Should cap at 60 minutes (session duration), not count extra 15
            expect($attendance->total_duration_minutes)->toBeLessThanOrEqual(60)
                ->and($attendance->attendance_percentage)->toBeLessThanOrEqual(100);
        });
    });

    describe('handle() - report syncing', function () {
        it('syncs attendance to quran session report', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'last_leave_time' => $scheduledAt->copy()->addMinutes(55),
                'is_calculated' => false,
                'join_leave_cycles' => [
                    [
                        'joined_at' => $scheduledAt->toISOString(),
                        'left_at' => $scheduledAt->copy()->addMinutes(55)->toISOString(),
                    ],
                ],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $report = StudentSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();

            expect($report)->not->toBeNull()
                ->and($report->attendance_status)->not->toBeNull()
                ->and($report->is_calculated)->toBeTrue();
        });

        it('syncs attendance to academic session report', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'academic',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            $report = AcademicSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();

            expect($report)->not->toBeNull();
        });
    });

    describe('handle() - error handling', function () {
        it('continues processing after individual attendance calculation error', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user1->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => false,
                'join_leave_cycles' => null, // Invalid data
            ]);

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user2->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to calculate attendance');
                });

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();

            // Second attendance should still be processed
            $attendance2 = MeetingAttendance::where('user_id', $user2->id)->first();
            expect($attendance2->is_calculated)->toBeTrue();
        });

        it('logs completion statistics', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'first_join_time' => $scheduledAt,
                'is_calculated' => false,
                'join_leave_cycles' => [],
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->times(2);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Post-meeting attendance calculation completed')
                        && isset($context['processed'])
                        && isset($context['skipped'])
                        && isset($context['failed']);
                });

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $job = new CalculateSessionAttendance();
            $job->handle();
        });
    });

    describe('handle() - already calculated attendance', function () {
        it('skips already calculated attendance records', function () {
            $scheduledAt = now()->subMinutes(70);
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => true, // Already calculated
                'attendance_status' => AttendanceStatus::ATTENDED->value,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->once();

            $job = new CalculateSessionAttendance();
            $job->handle();

            // Should show 0 processed (skipped)
        });
    });
});
