<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AttendanceEventService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('AttendanceEventService', function () {
    beforeEach(function () {
        $this->service = new AttendanceEventService();
        $this->academy = Academy::factory()->create();
    });

    describe('recordJoin()', function () {
        it('creates new MeetingAttendance record on first join', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $result = $this->service->recordJoin($session, $teacher, $eventData);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $teacher->id)
                ->first();

            expect($attendance)->not->toBeNull()
                ->and($attendance->user_type)->toBe('teacher')
                ->and($attendance->join_count)->toBe(1)
                ->and($attendance->first_join_time)->not->toBeNull()
                ->and($attendance->is_calculated)->toBeFalse()
                ->and($attendance->join_leave_cycles)->toHaveCount(1)
                ->and($attendance->join_leave_cycles[0]['type'])->toBe('join')
                ->and($attendance->join_leave_cycles[0]['participant_sid'])->toBe('PA_abc123');
        });

        it('updates existing MeetingAttendance record on subsequent join', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $firstEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_first',
                'participant_sid' => 'PA_first',
            ];

            $this->service->recordJoin($session, $student, $firstEventData);

            $secondEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_second',
                'participant_sid' => 'PA_second',
            ];

            $result = $this->service->recordJoin($session, $student, $secondEventData);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->join_count)->toBe(2)
                ->and($attendance->join_leave_cycles)->toHaveCount(2);
        });

        it('sets first_join_time only on first join', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $firstJoinTime = Carbon::now()->subMinutes(10);
            $firstEventData = [
                'timestamp' => $firstJoinTime,
                'event_id' => 'evt_first',
                'participant_sid' => 'PA_first',
            ];

            $this->service->recordJoin($session, $student, $firstEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            $originalFirstJoinTime = $attendance->first_join_time;

            $secondEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_second',
                'participant_sid' => 'PA_second',
            ];

            $this->service->recordJoin($session, $student, $secondEventData);

            $attendance->refresh();

            expect($attendance->first_join_time->equalTo($originalFirstJoinTime))->toBeTrue();
        });

        it('clears attendance cache on join', function () {
            Cache::shouldReceive('forget')
                ->with('attendance_status_1_1')
                ->once();

            Cache::shouldReceive('forget')
                ->with('meeting_attendance_1_1')
                ->once();

            $student = User::factory()->student()->create(['id' => 1]);
            $session = QuranSession::factory()->create([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $eventData);
        });

        it('sets is_calculated to false on join', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->is_calculated)->toBeFalse();
        });

        it('returns false on exception', function () {
            Log::shouldReceive('error')
                ->once();

            $student = User::factory()->student()->create();

            $invalidSession = new class {
                public $id = null;
            };

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $result = $this->service->recordJoin($invalidSession, $student, $eventData);

            expect($result)->toBeFalse();
        });

        it('correctly identifies teacher user type for Quran teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $teacher, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $teacher->id)
                ->first();

            expect($attendance->user_type)->toBe('teacher');
        });

        it('correctly identifies student user type', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->user_type)->toBe('student');
        });

        it('correctly identifies supervisor user type', function () {
            $supervisor = User::factory()->supervisor()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $supervisor, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $supervisor->id)
                ->first();

            expect($attendance->user_type)->toBe('supervisor');
        });

        it('correctly identifies session type for QuranSession', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->session_type)->toBe('individual');
        });

        it('correctly identifies session type for AcademicSession', function () {
            $student = User::factory()->student()->create();
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $eventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->session_type)->toBe('academic');
        });
    });

    describe('recordLeave()', function () {
        it('returns false when no attendance record exists', function () {
            Log::shouldReceive('warning')
                ->once();

            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $result = $this->service->recordLeave($session, $student, $eventData);

            expect($result)->toBeFalse();
        });

        it('matches leave event to join event by participant_sid', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime = Carbon::now()->subMinutes(10);
            $joinEventData = [
                'timestamp' => $joinTime,
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveTime = Carbon::now();
            $leaveEventData = [
                'timestamp' => $leaveTime,
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $result = $this->service->recordLeave($session, $student, $leaveEventData);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            $cycles = $attendance->join_leave_cycles;
            expect($cycles)->toHaveCount(2)
                ->and($cycles[0]['type'])->toBe('join')
                ->and($cycles[1]['type'])->toBe('leave')
                ->and($cycles[1]['participant_sid'])->toBe('PA_abc123')
                ->and($cycles[1]['duration_minutes'])->toBeGreaterThan(0);
        });

        it('calculates duration between join and leave', function () {
            $student = User::factory()->student()->create();
            $sessionStart = Carbon::now();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => $sessionStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime = $sessionStart->copy()->addMinutes(5);
            $joinEventData = [
                'timestamp' => $joinTime,
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveTime = $joinTime->copy()->addMinutes(20);
            $leaveEventData = [
                'timestamp' => $leaveTime,
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->join_leave_cycles[1]['duration_minutes'])->toBe(20);
        });

        it('updates leave count', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->leave_count)->toBe(1);
        });

        it('updates last_leave_time', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveTime = Carbon::now();
            $leaveEventData = [
                'timestamp' => $leaveTime,
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->last_leave_time)->not->toBeNull();
        });

        it('updates total_duration_minutes', function () {
            $student = User::factory()->student()->create();
            $sessionStart = Carbon::now();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => $sessionStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime = $sessionStart->copy()->addMinutes(5);
            $joinEventData = [
                'timestamp' => $joinTime,
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveTime = $joinTime->copy()->addMinutes(15);
            $leaveEventData = [
                'timestamp' => $leaveTime,
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->total_duration_minutes)->toBe(15);
        });

        it('sets is_calculated to false on leave', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->is_calculated)->toBeFalse();
        });

        it('clears attendance cache on leave', function () {
            $student = User::factory()->student()->create(['id' => 1]);
            $session = QuranSession::factory()->create([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            Cache::shouldReceive('forget')
                ->with('attendance_status_1_1')
                ->once();

            Cache::shouldReceive('forget')
                ->with('meeting_attendance_1_1')
                ->once();

            $leaveEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordLeave($session, $student, $leaveEventData);
        });

        it('adds unmatched leave event when participant_sid does not match', function () {
            Log::shouldReceive('warning')
                ->once();

            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_first',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $leaveEventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_leave',
                'participant_sid' => 'PA_different',
            ];

            $result = $this->service->recordLeave($session, $student, $leaveEventData);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->join_leave_cycles)->toHaveCount(2)
                ->and($attendance->join_leave_cycles[1]['type'])->toBe('leave');
        });

        it('handles multiple join-leave cycles', function () {
            $student = User::factory()->student()->create();
            $sessionStart = Carbon::now();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => $sessionStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime1 = $sessionStart->copy()->addMinutes(5);
            $this->service->recordJoin($session, $student, [
                'timestamp' => $joinTime1,
                'event_id' => 'evt_join1',
                'participant_sid' => 'PA_1',
            ]);

            $leaveTime1 = $joinTime1->copy()->addMinutes(10);
            $this->service->recordLeave($session, $student, [
                'timestamp' => $leaveTime1,
                'event_id' => 'evt_leave1',
                'participant_sid' => 'PA_1',
            ]);

            $joinTime2 = $leaveTime1->copy()->addMinutes(5);
            $this->service->recordJoin($session, $student, [
                'timestamp' => $joinTime2,
                'event_id' => 'evt_join2',
                'participant_sid' => 'PA_2',
            ]);

            $leaveTime2 = $joinTime2->copy()->addMinutes(15);
            $this->service->recordLeave($session, $student, [
                'timestamp' => $leaveTime2,
                'event_id' => 'evt_leave2',
                'participant_sid' => 'PA_2',
            ]);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->join_count)->toBe(2)
                ->and($attendance->leave_count)->toBe(2)
                ->and($attendance->total_duration_minutes)->toBe(25);
        });

        it('returns false on exception', function () {
            Log::shouldReceive('error')
                ->once();

            $student = User::factory()->student()->create();

            $invalidSession = new class {
                public $id = null;
            };

            $eventData = [
                'timestamp' => Carbon::now(),
                'event_id' => 'evt_123',
                'participant_sid' => 'PA_abc123',
            ];

            $result = $this->service->recordLeave($invalidSession, $student, $eventData);

            expect($result)->toBeFalse();
        });
    });

    describe('duration calculation', function () {
        it('calculates total duration from multiple cycles', function () {
            $student = User::factory()->student()->create();
            $sessionStart = Carbon::now();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => $sessionStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime1 = $sessionStart->copy()->addMinutes(5);
            $this->service->recordJoin($session, $student, [
                'timestamp' => $joinTime1,
                'participant_sid' => 'PA_1',
            ]);

            $leaveTime1 = $joinTime1->copy()->addMinutes(10);
            $this->service->recordLeave($session, $student, [
                'timestamp' => $leaveTime1,
                'participant_sid' => 'PA_1',
            ]);

            $joinTime2 = $leaveTime1->copy()->addMinutes(5);
            $this->service->recordJoin($session, $student, [
                'timestamp' => $joinTime2,
                'participant_sid' => 'PA_2',
            ]);

            $leaveTime2 = $joinTime2->copy()->addMinutes(20);
            $this->service->recordLeave($session, $student, [
                'timestamp' => $leaveTime2,
                'participant_sid' => 'PA_2',
            ]);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->total_duration_minutes)->toBe(30);
        });

        it('handles incomplete cycles without calculating duration', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinEventData = [
                'timestamp' => Carbon::now()->subMinutes(10),
                'event_id' => 'evt_join',
                'participant_sid' => 'PA_abc123',
            ];

            $this->service->recordJoin($session, $student, $joinEventData);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->total_duration_minutes)->toBe(0);
        });

        it('handles string timestamps in duration calculation', function () {
            $student = User::factory()->student()->create();
            $sessionStart = Carbon::now();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => $sessionStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $joinTime = $sessionStart->copy()->addMinutes(5);
            $this->service->recordJoin($session, $student, [
                'timestamp' => $joinTime->toISOString(),
                'participant_sid' => 'PA_1',
            ]);

            $leaveTime = $joinTime->copy()->addMinutes(15);
            $this->service->recordLeave($session, $student, [
                'timestamp' => $leaveTime->toISOString(),
                'participant_sid' => 'PA_1',
            ]);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $student->id)
                ->first();

            expect($attendance->total_duration_minutes)->toBe(15);
        });
    });
});
