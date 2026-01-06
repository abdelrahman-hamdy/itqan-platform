<?php

namespace Tests\Helpers;

use App\Contracts\LiveKitServiceInterface;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\Mocks\LiveKitMock;

/**
 * Test Helper Functions
 *
 * Provides utility functions for creating test data and simulating
 * common scenarios in feature tests.
 */
class TestHelpers
{
    /**
     * Create a session with meeting data already set up
     *
     * @param  string  $type  'quran', 'academic', or 'interactive'
     * @param  string  $status  Session status
     * @param  array  $overrides  Additional attributes to override
     */
    public static function createSessionWithMeeting(
        string $type = 'quran',
        string $status = 'scheduled',
        array $overrides = []
    ): BaseSession {
        $academy = $overrides['academy'] ?? \createAcademy();
        unset($overrides['academy']);

        // Create the appropriate session type
        $session = match ($type) {
            'quran' => static::createQuranSessionWithMeeting($academy, $status, $overrides),
            'academic' => static::createAcademicSessionWithMeeting($academy, $status, $overrides),
            'interactive' => static::createInteractiveSessionWithMeeting($academy, $status, $overrides),
            default => throw new \InvalidArgumentException("Unknown session type: {$type}"),
        };

        return $session;
    }

    /**
     * Create a Quran session with meeting data
     */
    protected static function createQuranSessionWithMeeting(
        Academy $academy,
        string $status,
        array $overrides
    ): QuranSession {
        // Create teacher and student if not provided
        $teacher = $overrides['teacher'] ?? \createQuranTeacher($academy);
        $student = $overrides['student'] ?? \createStudent($academy);

        // Create subscription if needed
        $subscription = QuranSubscription::factory()->create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // Create individual circle
        $circle = QuranIndividualCircle::factory()->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'quran_subscription_id' => $subscription->id,
        ]);

        // Build session data
        $sessionData = array_merge([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'quran_individual_circle_id' => $circle->id,
            'subscription_id' => $subscription->id,
            'session_type' => 'individual',
            'status' => static::mapStatus($status),
            'scheduled_at' => static::getScheduledAtForStatus($status),
            'duration_minutes' => 45,
        ], $overrides);

        // Add meeting data
        $roomName = 'QS-'.$academy->subdomain.'-'.Str::random(8);
        $sessionData = array_merge($sessionData, static::getMeetingData($roomName));

        return QuranSession::factory()->create($sessionData);
    }

    /**
     * Create an Academic session with meeting data
     */
    protected static function createAcademicSessionWithMeeting(
        Academy $academy,
        string $status,
        array $overrides
    ): AcademicSession {
        $teacher = $overrides['teacher'] ?? \createAcademicTeacher($academy);
        $student = $overrides['student'] ?? \createStudent($academy);

        // academic_teacher_id references academic_teacher_profiles.id, not users.id
        $teacherProfileId = $teacher->academicTeacherProfile->id;

        $sessionData = array_merge([
            'academy_id' => $academy->id,
            'academic_teacher_id' => $teacherProfileId,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'status' => static::mapStatus($status),
            'scheduled_at' => static::getScheduledAtForStatus($status),
            'duration_minutes' => 60,
        ], $overrides);

        $roomName = 'AS-'.$academy->subdomain.'-'.Str::random(8);
        $sessionData = array_merge($sessionData, static::getMeetingData($roomName));

        return AcademicSession::factory()->create($sessionData);
    }

    /**
     * Create an Interactive Course session with meeting data
     */
    protected static function createInteractiveSessionWithMeeting(
        Academy $academy,
        string $status,
        array $overrides
    ): InteractiveCourseSession {
        // Interactive sessions need a course first
        $course = $overrides['course'] ?? \App\Models\InteractiveCourse::factory()->create([
            'academy_id' => $academy->id,
        ]);

        $sessionData = array_merge([
            'interactive_course_id' => $course->id,
            'status' => static::mapStatus($status),
            'scheduled_date' => static::getScheduledAtForStatus($status)->toDateString(),
            'scheduled_time' => static::getScheduledAtForStatus($status)->format('H:i:s'),
            'duration_minutes' => 90,
        ], $overrides);

        $roomName = 'IC-'.$academy->subdomain.'-'.Str::random(8);
        $sessionData = array_merge($sessionData, static::getMeetingData($roomName));

        return InteractiveCourseSession::factory()->create($sessionData);
    }

    /**
     * Get meeting data fields for a session
     */
    protected static function getMeetingData(string $roomName): array
    {
        return [
            'meeting_room_name' => $roomName,
            'meeting_link' => "https://meet.test/{$roomName}",
            'meeting_id' => 'MT_'.Str::random(16),
            'meeting_platform' => 'livekit',
            'meeting_source' => 'livekit',
            'meeting_auto_generated' => true,
            'meeting_expires_at' => now()->addHours(4),
            'meeting_created_at' => now(),
            'meeting_data' => [
                'room_name' => $roomName,
                'server_url' => 'wss://mock-livekit.test',
            ],
        ];
    }

    /**
     * Map string status to SessionStatus enum
     */
    protected static function mapStatus(string $status): SessionStatus
    {
        return match (strtolower($status)) {
            'scheduled' => SessionStatus::SCHEDULED,
            'ready' => SessionStatus::READY,
            'ongoing' => SessionStatus::ONGOING,
            'completed' => SessionStatus::COMPLETED,
            'cancelled' => SessionStatus::CANCELLED,
            'absent' => SessionStatus::ABSENT,
            default => SessionStatus::SCHEDULED,
        };
    }

    /**
     * Get appropriate scheduled_at time based on status
     */
    protected static function getScheduledAtForStatus(string $status): Carbon
    {
        return match (strtolower($status)) {
            'scheduled' => now()->addHours(2),
            'ready' => now()->addMinutes(5),
            'ongoing' => now()->subMinutes(10),
            'completed' => now()->subHours(2),
            'cancelled' => now()->subDay(),
            'absent' => now()->subDay(),
            default => now()->addHours(2),
        };
    }

    /**
     * Simulate a user joining a meeting via webhook
     */
    public static function simulateMeetingJoin(BaseSession $session, User $user): MeetingAttendance
    {
        $attendance = MeetingAttendance::create([
            'session_type' => get_class($session),
            'session_id' => $session->id,
            'user_id' => $user->id,
            'attended_at' => now(),
            'status' => 'present',
        ]);

        return $attendance;
    }

    /**
     * Simulate a user leaving a meeting via webhook
     */
    public static function simulateMeetingLeave(BaseSession $session, User $user): ?MeetingAttendance
    {
        $attendance = MeetingAttendance::where('session_type', get_class($session))
            ->where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attendance) {
            $attendance->update([
                'left_at' => now(),
                'duration_minutes' => $attendance->attended_at
                    ? now()->diffInMinutes($attendance->attended_at)
                    : 0,
            ]);
        }

        return $attendance?->fresh();
    }

    /**
     * Advance a session to a specific status with appropriate timestamps
     */
    public static function advanceSessionToStatus(BaseSession $session, string $targetStatus): BaseSession
    {
        $status = static::mapStatus($targetStatus);

        $updateData = ['status' => $status];

        switch ($targetStatus) {
            case 'ready':
                // Session is ready to start (within 5 minutes of scheduled time)
                break;

            case 'ongoing':
                $updateData['started_at'] = now();
                break;

            case 'completed':
                $updateData['started_at'] = $session->started_at ?? now()->subHour();
                $updateData['ended_at'] = now();
                $updateData['actual_duration_minutes'] = $session->started_at
                    ? now()->diffInMinutes($session->started_at)
                    : $session->duration_minutes;
                break;

            case 'cancelled':
                $updateData['cancelled_at'] = now();
                $updateData['cancellation_reason'] = 'Test cancellation';
                break;

            case 'absent':
                $updateData['started_at'] = $session->scheduled_at;
                $updateData['ended_at'] = $session->scheduled_at->addMinutes($session->duration_minutes);
                break;
        }

        $session->update($updateData);

        return $session->fresh();
    }

    /**
     * Create a student with an active subscription of specified type
     */
    public static function createStudentWithSubscription(
        string $type = 'quran',
        ?Academy $academy = null,
        int $sessionsRemaining = 10
    ): array {
        $academy = $academy ?? \createAcademy();
        $student = \createStudent($academy);

        $subscription = match ($type) {
            'quran' => QuranSubscription::factory()->create([
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'remaining_sessions' => $sessionsRemaining,
                'status' => 'active',
                'start_date' => now()->subWeek(),
                'end_date' => now()->addMonth(),
            ]),
            'academic' => AcademicSubscription::factory()->create([
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'remaining_sessions' => $sessionsRemaining,
                'status' => 'active',
                'start_date' => now()->subWeek(),
                'end_date' => now()->addMonth(),
            ]),
            default => throw new \InvalidArgumentException("Unknown subscription type: {$type}"),
        };

        return [
            'student' => $student,
            'subscription' => $subscription,
            'academy' => $academy,
        ];
    }

    /**
     * Bind the LiveKit mock to the container
     */
    public static function useLiveKitMock(): LiveKitMock
    {
        $mock = new LiveKitMock;

        app()->instance(LiveKitServiceInterface::class, $mock);
        app()->instance(\App\Services\LiveKitService::class, $mock);

        return $mock;
    }

    /**
     * Freeze time to a specific datetime
     */
    public static function freezeTime(Carbon|string $datetime): void
    {
        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime);
        }

        Carbon::setTestNow($datetime);
    }

    /**
     * Unfreeze time
     */
    public static function unfreezeTime(): void
    {
        Carbon::setTestNow();
    }
}
