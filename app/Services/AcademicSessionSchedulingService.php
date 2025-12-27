<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AcademicSessionSchedulingService
{
    /**
     * Schedule an individual academic session
     */
    public function scheduleIndividualSession(
        AcademicSubscription $subscription,
        Carbon $scheduledAt,
        ?int $durationMinutes = null,
        ?string $title = null,
        ?string $description = null,
        ?array $additionalData = null
    ): AcademicSession {
        // Validate the scheduled time is in the future
        if ($scheduledAt->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Cannot schedule session in the past'],
            ]);
        }

        // Get duration from subscription if not provided
        if ($durationMinutes === null) {
            $durationMinutes = $subscription->session_duration_minutes ?? 60;
        }

        // Check for teacher conflicts
        if ($this->hasTeacherConflict($subscription->teacher_id, $scheduledAt, $durationMinutes)) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Teacher has a conflicting session at this time'],
            ]);
        }

        // Auto-populate title if not provided
        if (! $title) {
            $sessionCount = AcademicSession::where('academic_subscription_id', $subscription->id)->count() + 1;
            $title = "جلسة أكاديمية - {$subscription->student->name} - المادة: {$subscription->subject->name} (جلسة {$sessionCount})";
        }

        return AcademicSession::create([
            'academy_id' => $subscription->academy_id,
            'academic_teacher_id' => $subscription->teacher_id,
            'academic_subscription_id' => $subscription->id,
            'student_id' => $subscription->student_id,
            'session_type' => 'individual',
            'status' => SessionStatus::SCHEDULED,
            'is_scheduled' => true,
            'title' => $title,
            'description' => $description,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'location_type' => 'online',
            'meeting_auto_generated' => true,
            'attendance_status' => 'scheduled',
            'is_auto_generated' => false,
            'teacher_scheduled_at' => now(),
            'scheduled_by' => Auth::id(),
            ...$additionalData ?? [],
        ]);
    }

    /**
     * Check if teacher has conflicting sessions at given time
     */
    public function hasTeacherConflict(
        int $teacherId,
        Carbon $scheduledAt,
        int $durationMinutes,
        ?int $excludeSessionId = null
    ): bool {
        $sessionStart = $scheduledAt;
        $sessionEnd = $scheduledAt->copy()->addMinutes($durationMinutes);

        // Check academic sessions
        $academicConflicts = AcademicSession::where('academic_teacher_id', $teacherId)
            ->where('status', '!=', SessionStatus::CANCELLED)
            ->when($excludeSessionId, fn ($query) => $query->where('id', '!=', $excludeSessionId))
            ->where(function ($query) use ($sessionStart, $sessionEnd) {
                $query->whereBetween('scheduled_at', [$sessionStart, $sessionEnd])
                    ->orWhere(function ($q) use ($sessionStart) {
                        $q->where('scheduled_at', '<=', $sessionStart)
                            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$sessionStart]);
                    });
            })
            ->exists();

        return $academicConflicts;
    }

    /**
     * Get sessions for calendar display
     */
    public function getCalendarEvents(int $teacherId, Carbon $start, Carbon $end): array
    {
        $sessions = AcademicSession::where('academic_teacher_id', $teacherId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->with(['student', 'academicSubscription.subject'])
            ->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'title' => $session->title,
                'start' => $session->scheduled_at->toISOString(),
                'end' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->toISOString(),
                'backgroundColor' => $this->getSessionColor($session->status),
                'borderColor' => $this->getSessionColor($session->status),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'academic_session',
                    'status' => $session->status,
                    'student_name' => $session->student?->name,
                    'subject' => $session->academicSubscription?->subject?->name,
                    'session_code' => $session->session_code,
                    'meeting_link' => $session->meeting_link,
                ],
            ];
        })->toArray();
    }

    /**
     * Get color for session based on status
     */
    private function getSessionColor(string $status): string
    {
        return match ($status) {
            SessionStatus::SCHEDULED->value => '#3B82F6', // Blue
            SessionStatus::ONGOING->value => '#10B981',   // Green
            SessionStatus::COMPLETED->value => '#6B7280', // Gray
            SessionStatus::CANCELLED->value => '#EF4444', // Red
            'rescheduled' => '#F59E0B', // Amber
            default => '#6B7280',
        };
    }
}
