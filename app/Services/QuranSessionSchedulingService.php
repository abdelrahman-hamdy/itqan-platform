<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\AcademyContextService;

class QuranSessionSchedulingService
{
    /**
     * Schedule a template session for an individual circle
     */
    public function scheduleIndividualSession(
        QuranSession $templateSession,
        Carbon $scheduledAt,
        ?array $additionalData = null
    ): QuranSession {
        // Validate that this is a template session
        if (! $templateSession->is_template || $templateSession->is_scheduled) {
            throw new \InvalidArgumentException('Session is not a schedulable template');
        }

        // Validate the scheduled time is in the future
        if ($scheduledAt->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Cannot schedule session in the past'],
            ]);
        }

        // Check for teacher conflicts
        if ($this->hasTeacherConflict($templateSession->quran_teacher_id, $scheduledAt, $templateSession->duration_minutes)) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Teacher has a conflicting session at this time'],
            ]);
        }

        // Update the template session to be scheduled
        $templateSession->update([
            'scheduled_at' => $scheduledAt,
            'status' => SessionStatus::SCHEDULED,
            'is_scheduled' => true,
            'teacher_scheduled_at' => now(),
            'scheduled_by' => Auth::id(),
            ...$additionalData ?? [],
        ]);

        // Update the individual circle counts
        $templateSession->individualCircle->updateSessionCounts();

        return $templateSession->fresh();
    }

    /**
     * Create and activate a schedule for a group circle
     */
    public function createGroupCircleSchedule(
        QuranCircle $circle,
        array $weeklySchedule,
        Carbon $startsAt,
        ?Carbon $endsAt = null,
        array $options = []
    ): QuranCircleSchedule {
        // Validate that circle doesn't already have an active schedule
        if ($circle->schedule && $circle->schedule->is_active) {
            throw ValidationException::withMessages([
                'circle_id' => ['Circle already has an active schedule'],
            ]);
        }

        // Validate weekly schedule format
        $this->validateWeeklySchedule($weeklySchedule);

        // Create the schedule
        $schedule = QuranCircleSchedule::create([
            'academy_id' => $circle->academy_id,
            'circle_id' => $circle->id,
            'quran_teacher_id' => Auth::id(),
            'weekly_schedule' => $weeklySchedule,
            'timezone' => $options['timezone'] ?? AcademyContextService::getTimezone(),
            'default_duration_minutes' => $options['duration'] ?? 60,
            'schedule_starts_at' => $startsAt,
            'schedule_ends_at' => $endsAt,
            'generate_ahead_days' => $options['generate_ahead_days'] ?? 30,
            'generate_before_hours' => $options['generate_before_hours'] ?? 1,
            'session_title_template' => $options['title_template'] ?? null,
            'session_description_template' => $options['description_template'] ?? null,
            'default_lesson_objectives' => $options['lesson_objectives'] ?? null,
            'meeting_link' => $options['meeting_link'] ?? null,
            'meeting_id' => $options['meeting_id'] ?? null,
            'recording_enabled' => $options['recording_enabled'] ?? false,
            'created_by' => Auth::id(),
        ]);

        // Activate the schedule
        $schedule->activateSchedule();

        return $schedule;
    }

    /**
     * Check if teacher has a conflict at the given time
     */
    public function hasTeacherConflict(int $teacherId, Carbon $scheduledAt, int $durationMinutes): bool
    {
        $sessionEnd = $scheduledAt->copy()->addMinutes($durationMinutes);

        // Get all scheduled sessions for this teacher
        $existingSessions = QuranSession::where('quran_teacher_id', $teacherId)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->get(['scheduled_at', 'duration_minutes']);

        // Check for conflicts in PHP (more portable and maintainable)
        foreach ($existingSessions as $session) {
            $existingStart = Carbon::parse($session->scheduled_at);
            $existingEnd = $existingStart->copy()->addMinutes($session->duration_minutes);

            // Check if sessions overlap
            if ($scheduledAt->lt($existingEnd) && $sessionEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate weekly schedule format
     */
    private function validateWeeklySchedule(array $weeklySchedule): void
    {
        $validDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($weeklySchedule as $schedule) {
            if (! isset($schedule['day']) || ! in_array($schedule['day'], $validDays)) {
                throw ValidationException::withMessages([
                    'weekly_schedule' => ['Invalid day in weekly schedule'],
                ]);
            }

            if (! isset($schedule['time']) || ! preg_match('/^\d{2}:\d{2}$/', $schedule['time'])) {
                throw ValidationException::withMessages([
                    'weekly_schedule' => ['Invalid time format in weekly schedule (use HH:MM)'],
                ]);
            }
        }
    }

    /**
     * Bulk schedule multiple individual sessions
     */
    public function bulkScheduleIndividualSessions(
        QuranIndividualCircle $circle,
        array $sessionsData
    ): \Illuminate\Support\Collection {
        $scheduledSessions = collect();

        foreach ($sessionsData as $sessionData) {
            $templateSession = QuranSession::findOrFail($sessionData['template_session_id']);

            // Verify template belongs to this circle
            if ($templateSession->individual_circle_id !== $circle->id) {
                throw new \InvalidArgumentException('Template session does not belong to this circle');
            }

            $scheduledSession = $this->scheduleIndividualSession(
                $templateSession,
                Carbon::parse($sessionData['scheduled_at']),
                array_filter([
                    'title' => $sessionData['title'] ?? null,
                    'description' => $sessionData['description'] ?? null,
                ])
            );

            $scheduledSessions->push($scheduledSession);
        }

        return $scheduledSessions;
    }

    /**
     * Get available time slots for a teacher
     */
    public function getAvailableTimeSlots(
        int $teacherId,
        Carbon $date,
        int $duration = 60
    ): array {
        // Get teacher's existing sessions for the date
        $existingSessions = QuranSession::where('quran_teacher_id', $teacherId)
            ->whereDate('scheduled_at', $date)
            ->where('status', '!=', SessionStatus::CANCELLED->value)
            ->get(['scheduled_at', 'duration_minutes']);

        // Define working hours (can be made configurable per teacher)
        $workStart = $date->copy()->setTime(8, 0);
        $workEnd = $date->copy()->setTime(22, 0);

        $availableSlots = [];
        $current = $workStart->copy();

        while ($current->copy()->addMinutes($duration)->lte($workEnd)) {
            $slotEnd = $current->copy()->addMinutes($duration);

            // Check if this slot conflicts with existing sessions
            $hasConflict = $existingSessions->contains(function ($session) use ($current, $slotEnd) {
                $sessionStart = Carbon::parse($session->scheduled_at);
                $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes);

                return $current->lt($sessionEnd) && $slotEnd->gt($sessionStart);
            });

            if (! $hasConflict) {
                $availableSlots[] = [
                    'time' => $current->format('H:i'),
                    'datetime' => $current->toISOString(),
                    'available' => true,
                ];
            }

            $current->addMinutes(30); // 30-minute intervals
        }

        return $availableSlots;
    }

    /**
     * Update an existing group circle schedule
     */
    public function updateGroupCircleSchedule(
        QuranCircleSchedule $schedule,
        array $data
    ): QuranCircleSchedule {
        $schedule->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $schedule->fresh();
    }
}
