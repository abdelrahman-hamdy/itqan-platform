<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use Exception;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    use ApiResponses;

    /**
     * Get weekly schedule.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get date range
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfWeek();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : $startDate->copy()->endOfWeek();

        $sessions = $this->getSessionsForRange($user, $startDate, $endDate);

        // Group by date
        $byDate = [];
        foreach ($sessions as $session) {
            $date = Carbon::parse($session['scheduled_at'])->toDateString();
            if (! isset($byDate[$date])) {
                $byDate[$date] = [];
            }
            $byDate[$date][] = $session;
        }

        // Sort sessions within each date
        foreach ($byDate as $date => $dateSessions) {
            usort($dateSessions, function ($a, $b) {
                return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
            });
            $byDate[$date] = $dateSessions;
        }

        return $this->success([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'schedule' => $byDate,
            'total_sessions' => count($sessions),
        ], __('Schedule retrieved successfully'));
    }

    /**
     * Get schedule for a specific day.
     */
    public function day(Request $request, string $date): JsonResponse
    {
        $user = $request->user();

        try {
            $targetDate = Carbon::parse($date);
        } catch (Exception $e) {
            return $this->error(__('Invalid date format.'), 400, 'INVALID_DATE');
        }

        $sessions = $this->getSessionsForRange($user, $targetDate, $targetDate);

        // Sort by scheduled time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $this->success([
            'date' => $targetDate->toDateString(),
            'day_name' => $targetDate->translatedFormat('l'),
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Day schedule retrieved successfully'));
    }

    /**
     * Get sessions for date range.
     */
    protected function getSessionsForRange($user, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = [];

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->whereBetween('scheduled_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['student', 'individualCircle', 'circle'])
                    ->get();

                foreach ($quranSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'quran',
                        'title' => $session->title ?? 'جلسة قرآنية',
                        'student_name' => $session->student?->name ?? $session->student?->full_name,
                        'circle_name' => $session->individualCircle?->name ?? $session->circle?->name,
                        'circle_type' => $session->circle_id ? 'group' : 'individual',
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'duration_minutes' => $session->duration_minutes ?? 60,
                        'status' => $session->status->value ?? $session->status,
                        'meeting_url' => $session->meeting_link,
                    ];
                }
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                // Academic sessions
                $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->whereBetween('scheduled_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['student', 'academicSubscription'])
                    ->get();

                foreach ($academicSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'academic',
                        'title' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
                        'student_name' => $session->student?->name ?? 'طالب',
                        'subject' => $session->academicSubscription?->subject?->name ?? $session->academicSubscription?->subject_name,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'duration_minutes' => $session->duration_minutes ?? 60,
                        'status' => $session->status->value ?? $session->status,
                        'meeting_url' => $session->meeting_link,
                    ];
                }

                // Interactive course sessions
                $profile = $user->academicTeacherProfile;
                $courseIds = $profile ? $profile->assignedCourses()->pluck('id') : collect();

                $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $courseIds)
                    ->whereBetween('scheduled_at', [$startDate, $endDate])
                    ->whereNotIn('status', [SessionStatus::CANCELLED->value])
                    ->with(['course'])
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $sessions[] = [
                        'id' => $session->id,
                        'type' => 'interactive',
                        'title' => $session->title ?? $session->course?->title,
                        'course_name' => $session->course?->title,
                        'session_number' => $session->session_number,
                        'scheduled_at' => $session->scheduled_at?->toISOString(),
                        'duration_minutes' => $session->duration_minutes ?? 60,
                        'status' => $session->status->value ?? $session->status,
                        'meeting_url' => $session->meeting_link,
                    ];
                }
            }
        }

        return $sessions;
    }
}
