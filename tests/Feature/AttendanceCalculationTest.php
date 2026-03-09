<?php

use App\Enums\AttendanceStatus;
use App\Enums\MeetingEventType;
use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionAttendance;
use App\Models\MeetingAttendance;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Services\AttendanceEventService;

/**
 * Test: Full Attendance Flow — verifies the complete cycle from join→leave→calculate
 *
 * These tests cover the bug where DevMeetingController::leave() updated MeetingAttendanceEvent
 * but did NOT update MeetingAttendance.join_leave_cycles, causing CalculateSessionAttendance
 * to see open cycles and compute 0 minutes.
 */
beforeEach(function () {
    // Create academy, teacher, student, and a completed session
    $this->academy = createAcademy();
    setTenantContext($this->academy);

    $this->teacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);

    // Default: session started 2 hours ago, 30 min duration, completed
    $this->sessionStart = now()->subHours(2);
    $this->sessionEnd = $this->sessionStart->copy()->addMinutes(30);
});

it('calculates attendance correctly when leave comes via AttendanceEventService (webhook path)', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    $eventService = app(AttendanceEventService::class);

    // Simulate join at session start
    $joinTime = $this->sessionStart->copy();
    $participantSid = 'PA_TEST_'.uniqid();
    $eventService->recordJoin($session, $this->student, [
        'timestamp' => $joinTime,
        'event_id' => 'JOIN_'.uniqid(),
        'participant_sid' => $participantSid,
    ]);

    // Simulate leave 25 minutes later
    $leaveTime = $joinTime->copy()->addMinutes(25);
    $eventService->recordLeave($session, $this->student, [
        'timestamp' => $leaveTime,
        'event_id' => 'LEAVE_'.uniqid(),
        'participant_sid' => $participantSid,
        'duration_minutes' => 25,
    ]);

    // Run CalculateSessionAttendance job
    (new CalculateSessionAttendance)->handle();

    // Assert
    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->total_duration_minutes)->toBeGreaterThan(0);
    expect($attendance->is_calculated)->toBeTrue();
    expect($attendance->attendance_percentage)->toBeGreaterThan(50);
});

it('calculates attendance correctly when leave comes via DevMeetingController API', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    $eventService = app(AttendanceEventService::class);

    // Simulate join via webhook (this writes to join_leave_cycles)
    $joinTime = $this->sessionStart->copy();
    $participantSid = 'PA_TEST_'.uniqid();
    $eventService->recordJoin($session, $this->student, [
        'timestamp' => $joinTime,
        'event_id' => 'JOIN_'.uniqid(),
        'participant_sid' => $participantSid,
    ]);

    // Create the MeetingAttendanceEvent that DevMeetingController::leave() would find
    $event = MeetingAttendanceEvent::create([
        'event_id' => 'JOIN_EVT_'.uniqid(),
        'event_type' => MeetingEventType::JOINED,
        'event_timestamp' => $joinTime,
        'session_id' => $session->id,
        'session_type' => get_class($session),
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
        'participant_sid' => $participantSid,
        'participant_identity' => 'user-'.$this->student->id,
        'participant_name' => $this->student->full_name ?? 'Test Student',
        'raw_webhook_data' => ['test' => true],
    ]);

    // Simulate leave via DevMeetingController API endpoint (the fixed path)
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson('/api/sessions/meeting/leave', [
            'session_id' => $session->id,
        ]);

    $response->assertJson(['success' => true]);

    // Run CalculateSessionAttendance job
    (new CalculateSessionAttendance)->handle();

    // Assert: this was the bug — before the fix, total_duration_minutes would be 0
    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->total_duration_minutes)->toBeGreaterThan(0);
    expect($attendance->is_calculated)->toBeTrue();
});

it('reconciles open cycles during calculation using MeetingAttendanceEvent data', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    $participantSid = 'PA_TEST_'.uniqid();
    $joinTime = $this->sessionStart->copy()->addMinutes(2);
    $leaveTime = $joinTime->copy()->addMinutes(25);

    // Create attendance record with an OPEN webhook cycle (join but no leave)
    $attendance = MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $this->student->id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'first_join_time' => $joinTime,
        'join_leave_cycles' => [
            ['type' => 'join', 'timestamp' => $joinTime->toISOString(), 'participant_sid' => $participantSid],
            // NO matching leave — this is the bug scenario
        ],
        'join_count' => 1,
        'leave_count' => 0,
        'total_duration_minutes' => 0,
        'is_calculated' => false,
    ]);

    // Create a closed MeetingAttendanceEvent (DevMeetingController wrote this but not the cycle)
    MeetingAttendanceEvent::create([
        'event_id' => 'JOIN_EVT_'.uniqid(),
        'event_type' => MeetingEventType::JOINED,
        'event_timestamp' => $joinTime,
        'session_id' => $session->id,
        'session_type' => get_class($session),
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
        'participant_sid' => $participantSid,
        'participant_identity' => 'user-'.$this->student->id,
        'participant_name' => 'Test Student',
        'left_at' => $leaveTime,
        'duration_minutes' => 25,
        'leave_event_id' => 'LEAVE_'.uniqid(),
        'raw_webhook_data' => ['test' => true],
    ]);

    // Run CalculateSessionAttendance — should reconcile the open cycle
    (new CalculateSessionAttendance)->handle();

    $attendance->refresh();

    expect($attendance->total_duration_minutes)->toBeGreaterThan(0);
    expect($attendance->is_calculated)->toBeTrue();

    // Verify the cycle was reconciled
    $cycles = $attendance->join_leave_cycles;
    $hasLeave = collect($cycles)->contains(fn ($c) => ($c['type'] ?? '') === 'leave');
    expect($hasLeave)->toBeTrue();
});

it('reconciles open cycles at session end time when no event data exists', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    $joinTime = $this->sessionStart->copy()->addMinutes(5);

    // Create attendance record with open cycle and NO MeetingAttendanceEvent
    MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $this->student->id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'first_join_time' => $joinTime,
        'join_leave_cycles' => [
            ['type' => 'join', 'timestamp' => $joinTime->toISOString(), 'participant_sid' => null],
        ],
        'join_count' => 1,
        'leave_count' => 0,
        'total_duration_minutes' => 0,
        'is_calculated' => false,
    ]);

    // Run calculation — should auto-close at session end time
    (new CalculateSessionAttendance)->handle();

    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    // Session is 30 min, joined at +5min, so ~25 min of attendance
    expect($attendance->total_duration_minutes)->toBeGreaterThanOrEqual(20);
    expect($attendance->total_duration_minutes)->toBeLessThanOrEqual(30);
    expect($attendance->is_calculated)->toBeTrue();
});

it('clips pre-session cycles to 0 and counts in-session cycles', function () {
    // Session starts at T+10, ends at T+40 (30 min duration)
    $baseTime = now()->subHours(3);
    $sessionStart = $baseTime->copy()->addMinutes(10);
    $sessionEnd = $sessionStart->copy()->addMinutes(30);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $sessionEnd,
    ]);

    // Cycle 1: T+0 to T+5 — entirely before session start → 0 minutes
    // Cycle 2: T+12 to T+35 — during session → 23 minutes
    MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $this->student->id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'first_join_time' => $baseTime,
        'join_leave_cycles' => [
            ['type' => 'join', 'timestamp' => $baseTime->toISOString(), 'participant_sid' => 'PA_1'],
            ['type' => 'leave', 'timestamp' => $baseTime->copy()->addMinutes(5)->toISOString(), 'participant_sid' => 'PA_1'],
            ['type' => 'join', 'timestamp' => $baseTime->copy()->addMinutes(12)->toISOString(), 'participant_sid' => 'PA_2'],
            ['type' => 'leave', 'timestamp' => $baseTime->copy()->addMinutes(35)->toISOString(), 'participant_sid' => 'PA_2'],
        ],
        'join_count' => 2,
        'leave_count' => 2,
        'total_duration_minutes' => 0,
        'is_calculated' => false,
    ]);

    (new CalculateSessionAttendance)->handle();

    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    // Only the in-session cycle should be counted: 23 minutes
    expect($attendance->total_duration_minutes)->toBe(23);
    expect($attendance->is_calculated)->toBeTrue();
});

it('accumulates multiple join/leave cycles correctly', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'individual',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    // 3 cycles within the session window
    $t0 = $this->sessionStart->copy();
    MeetingAttendance::create([
        'session_id' => $session->id,
        'user_id' => $this->student->id,
        'user_type' => 'student',
        'session_type' => 'individual',
        'first_join_time' => $t0,
        'join_leave_cycles' => [
            // Cycle 1: 0-8 min → 8 minutes
            ['type' => 'join', 'timestamp' => $t0->toISOString(), 'participant_sid' => 'PA_1'],
            ['type' => 'leave', 'timestamp' => $t0->copy()->addMinutes(8)->toISOString(), 'participant_sid' => 'PA_1'],
            // Cycle 2: 10-18 min → 8 minutes
            ['type' => 'join', 'timestamp' => $t0->copy()->addMinutes(10)->toISOString(), 'participant_sid' => 'PA_2'],
            ['type' => 'leave', 'timestamp' => $t0->copy()->addMinutes(18)->toISOString(), 'participant_sid' => 'PA_2'],
            // Cycle 3: 20-28 min → 8 minutes
            ['type' => 'join', 'timestamp' => $t0->copy()->addMinutes(20)->toISOString(), 'participant_sid' => 'PA_3'],
            ['type' => 'leave', 'timestamp' => $t0->copy()->addMinutes(28)->toISOString(), 'participant_sid' => 'PA_3'],
        ],
        'join_count' => 3,
        'leave_count' => 3,
        'total_duration_minutes' => 0,
        'is_calculated' => false,
    ]);

    (new CalculateSessionAttendance)->handle();

    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    // Total = 8 + 8 + 8 = 24 minutes
    expect($attendance->total_duration_minutes)->toBe(24);
    expect($attendance->is_calculated)->toBeTrue();
    // 24/30 = 80% → should be ATTENDED (>= 50% threshold)
    expect($attendance->attendance_status)->toBe(AttendanceStatus::ATTENDED);
});

it('handles trial session attendance end-to-end', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
        'session_type' => 'trial',
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => $this->sessionStart,
        'duration_minutes' => 30,
        'ended_at' => $this->sessionEnd,
    ]);

    $eventService = app(AttendanceEventService::class);

    // Student joins 2 minutes into session
    $joinTime = $this->sessionStart->copy()->addMinutes(2);
    $participantSid = 'PA_TRIAL_'.uniqid();

    $eventService->recordJoin($session, $this->student, [
        'timestamp' => $joinTime,
        'event_id' => 'JOIN_'.uniqid(),
        'participant_sid' => $participantSid,
    ]);

    // Student leaves at 27 minutes (25 min attendance)
    $leaveTime = $joinTime->copy()->addMinutes(25);
    $eventService->recordLeave($session, $this->student, [
        'timestamp' => $leaveTime,
        'event_id' => 'LEAVE_'.uniqid(),
        'participant_sid' => $participantSid,
        'duration_minutes' => 25,
    ]);

    // Run calculation
    (new CalculateSessionAttendance)->handle();

    $attendance = MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $this->student->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->total_duration_minutes)->toBe(25);
    expect($attendance->is_calculated)->toBeTrue();
    // 25/30 = ~83% → ATTENDED (>= 50%)
    expect($attendance->attendance_status)->toBe(AttendanceStatus::ATTENDED);
    expect((float) $attendance->attendance_percentage)->toBeGreaterThanOrEqual(80);
});
