<?php

namespace App\Services\Calendar;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class EventFormattingService
{
    /**
     * Format Quran sessions as calendar events
     */
    public function formatQuranSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            $perspective = $user->isQuranTeacher() ? 'teacher' : 'student';

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            return [
                'id' => 'quran_session_'.$session->id,
                'type' => 'session',
                'source' => 'quran_session',
                'title' => $this->getSessionTitle($session, $perspective),
                'description' => $this->getSessionDescription($session, $perspective),
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => $this->getSessionColor($session),
                'url' => $this->getSessionUrl($session),
                'teacher_name' => $session->quranTeacher ? ($session->quranTeacher->first_name . ' ' . $session->quranTeacher->last_name) : null,
                'teacher_data' => $session->quranTeacher ? [
                    'id' => $session->quranTeacher->id,
                    'name' => $session->quranTeacher->first_name . ' ' . $session->quranTeacher->last_name,
                    'gender' => $session->quranTeacher->gender ?? 'male',
                    'user' => [
                        'id' => $session->quranTeacher->id,
                        'name' => $session->quranTeacher->name,
                        'email' => $session->quranTeacher->email,
                    ],
                ] : null,
                'meeting_url' => $session->google_meet_url ?? $session->meeting_link,
                'can_reschedule' => $session->can_reschedule,
                'can_cancel' => $session->can_cancel,
                'participants' => $this->getSessionParticipants($session),
                'metadata' => [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type,
                    'teacher_id' => $session->quran_teacher_id,
                    'student_id' => $session->student_id,
                    'circle_id' => $session->circle_id,
                    'quran_subscription_id' => $session->quran_subscription_id,
                ],
            ];
        });
    }

    /**
     * Format course sessions as calendar events
     */
    public function formatCourseSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            $perspective = $user->isAcademicTeacher() ? 'teacher' : 'student';

            $courseTitle = $session->course?->title ?? 'دورة تعليمية';
            $sessionTitle = $session->title ?? 'جلسة';
            $participantsCount = $session->course?->enrollments?->count() ?? 0;

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            // Try to generate session URL safely (not course URL - we want session details, not course page)
            $sessionUrl = '#';
            try {
                if ($session->id && Route::has('interactive-sessions.show')) {
                    $subdomain = auth()->user()?->academy?->subdomain ?? 'itqan-academy';
                    $sessionUrl = route('interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
                }
            } catch (\Exception $e) {
                // Keep default '#' if route doesn't exist
            }

            return [
                'id' => 'course_session_'.$session->id,
                'type' => 'course',
                'source' => 'course_session',
                'title' => $courseTitle.' - '.$sessionTitle,
                'description' => $session->description ?? '',
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => '#3B82F6', // Blue for courses
                'url' => $sessionUrl,
                'teacher_name' => $session->course?->assignedTeacher ? ($session->course->assignedTeacher->first_name . ' ' . $session->course->assignedTeacher->last_name) : null,
                'teacher_data' => $session->course?->assignedTeacher ? [
                    'id' => $session->course->assignedTeacher->id,
                    'name' => $session->course->assignedTeacher->first_name . ' ' . $session->course->assignedTeacher->last_name,
                    'gender' => $session->course->assignedTeacher->user?->gender ?? 'male',
                    'user' => $session->course->assignedTeacher->user ? [
                        'id' => $session->course->assignedTeacher->user->id,
                        'name' => $session->course->assignedTeacher->user->name,
                        'email' => $session->course->assignedTeacher->user->email,
                    ] : null,
                ] : null,
                'meeting_url' => $session->meeting_link ?? null,
                'participants' => $participantsCount,
                'metadata' => [
                    'session_id' => $session->id,
                    'course_id' => $session->course_id,
                    'teacher_id' => $session->course?->assigned_teacher_id ?? null,
                ],
            ];
        });
    }

    /**
     * Format circle sessions as calendar events
     */
    public function formatCircleSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) {
            $circleName = $session->circle?->name_ar ?? 'حلقة جماعية';
            $circleDescription = $session->circle?->description_ar ?? '';
            $participantsCount = $session->circle?->students?->count() ?? 0;

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            // Try to generate session URL safely (not circle URL - we want session details, not circle page)
            $sessionUrl = '#';
            try {
                if ($session->id && Route::has('student.sessions.show')) {
                    $subdomain = auth()->user()?->academy?->subdomain ?? 'itqan-academy';
                    $sessionUrl = route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]);
                }
            } catch (\Exception $e) {
                // Keep default '#' if route doesn't exist or fails
            }

            return [
                'id' => 'circle_session_'.$session->id,
                'type' => 'circle',
                'source' => 'circle_session',
                'title' => $circleName,
                'description' => 'حلقة جماعية - '.$circleDescription,
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => '#10B981', // Green for circles
                'url' => $sessionUrl,
                'teacher_name' => $session->quranTeacher ? ($session->quranTeacher->first_name . ' ' . $session->quranTeacher->last_name) : null,
                'teacher_data' => $session->quranTeacher ? [
                    'id' => $session->quranTeacher->id,
                    'name' => $session->quranTeacher->first_name . ' ' . $session->quranTeacher->last_name,
                    'gender' => $session->quranTeacher->gender ?? 'male',
                    'user' => [
                        'id' => $session->quranTeacher->id,
                        'name' => $session->quranTeacher->name,
                        'email' => $session->quranTeacher->email,
                    ],
                ] : null,
                'meeting_url' => $session->google_meet_url ?? $session->meeting_link ?? null,
                'participants' => $participantsCount,
                'metadata' => [
                    'session_id' => $session->id,
                    'circle_id' => $session->circle_id,
                    'teacher_id' => $session->quran_teacher_id,
                ],
            ];
        });
    }

    /**
     * Get session title based on perspective
     */
    private function getSessionTitle($session, string $perspective): string
    {
        if ($session->session_type === 'group') {
            return $session->circle?->name_ar ?? 'حلقة جماعية';
        }

        if ($perspective === 'teacher') {
            return "جلسة مع " . ($session->student?->name ?? 'طالب غير محدد');
        } else {
            return "جلسة مع الأستاذ " . ($session->quranTeacher?->user?->name ?? 'معلم غير محدد');
        }
    }

    /**
     * Get session description based on perspective
     */
    private function getSessionDescription($session, string $perspective): string
    {
        $description = '';

        if ($session->session_type === 'individual') {
            if ($perspective === 'teacher') {
                $studentName = $session->student?->name ?? 'طالب غير محدد';
                $description = "جلسة فردية مع الطالب {$studentName}";
            } else {
                $teacherName = $session->quranTeacher?->user?->name ?? 'معلم غير محدد';
                $description = "جلسة فردية مع الأستاذ {$teacherName}";
            }
        } else {
            $circleName = $session->circle?->name_ar ?? 'حلقة جماعية';
            $description = "حلقة جماعية - {$circleName}";
        }

        if ($session->current_surah) {
            $description .= ' - سورة '.$this->getSurahName($session->current_surah);
        }

        return $description;
    }

    /**
     * Get session color based on status
     */
    private function getSessionColor($session): string
    {
        // Use enum hexColor() for consistent colors
        $status = $session->status;

        if ($status instanceof \App\Enums\SessionStatus) {
            return $status->hexColor();
        }

        $statusEnum = \App\Enums\SessionStatus::tryFrom($status);
        return $statusEnum?->hexColor() ?? '#6366F1';
    }

    /**
     * Get session URL
     */
    private function getSessionUrl($session): string
    {
        try {
            $subdomain = auth()->user()?->academy?->subdomain ?? 'itqan-academy';

            // Group circle sessions
            if ($session->session_type === 'group' && $session->circle_id) {
                if (Route::has('quran-circles.show')) {
                    return route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $session->circle_id]);
                }
                return '#';
            }

            // Individual Quran sessions
            if ($session->session_type === 'individual') {
                if (Route::has('student.sessions.show')) {
                    return route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]);
                }
                return '#';
            }
        } catch (\Exception $e) {
            return '#';
        }

        return '#';
    }

    /**
     * Get session participants
     */
    private function getSessionParticipants($session): array
    {
        $participants = [];

        if ($session->quranTeacher) {
            $participants[] = [
                'name' => $session->quranTeacher->name ?? 'معلم غير محدد',
                'role' => 'teacher',
                'email' => $session->quranTeacher->email ?? '',
            ];
        }

        if ($session->student) {
            $participants[] = [
                'name' => $session->student->name ?? 'طالب غير محدد',
                'role' => 'student',
                'email' => $session->student->email ?? '',
            ];
        }

        if ($session->circle && $session->circle->students) {
            foreach ($session->circle->students as $student) {
                $participants[] = [
                    'name' => $student->name ?? 'طالب غير محدد',
                    'role' => 'student',
                    'email' => $student->email ?? '',
                ];
            }
        }

        return $participants;
    }

    /**
     * Get Surah name by number
     */
    private function getSurahName(int $surahNumber): string
    {
        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            // Add more as needed
        ];

        return $surahNames[$surahNumber] ?? "سورة رقم {$surahNumber}";
    }
}
