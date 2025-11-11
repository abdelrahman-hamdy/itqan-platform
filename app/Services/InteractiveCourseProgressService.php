<?php

namespace App\Services;

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseProgress;
use Illuminate\Support\Facades\Cache;

class InteractiveCourseProgressService
{
    /**
     * Calculate complete progress data for a student in a course
     */
    public function calculateCourseProgress(int $courseId, int $studentId): array
    {
        $cacheKey = "interactive_progress_{$courseId}_{$studentId}";

        return Cache::remember($cacheKey, 3600, function () use ($courseId, $studentId) {
            $course = InteractiveCourse::with([
                'sessions.attendances' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
                'sessions.homework' => function ($q) use ($studentId) {
                    $q->with(['submissions' => function ($q2) use ($studentId) {
                        $q2->where('student_id', $studentId);
                    }]);
                }
            ])->findOrFail($courseId);

            $totalSessions = $course->sessions->count();
            $completedSessions = $course->sessions
                ->where('status', 'completed')
                ->count();

            $attendedSessions = $course->sessions
                ->filter(function ($session) {
                    return $session->attendances
                        ->whereIn('status', ['present', 'late'])
                        ->count() > 0;
                })
                ->count();

            // Count homework
            $totalHomework = 0;
            $submittedHomework = 0;
            $grades = [];

            foreach ($course->sessions as $session) {
                if ($session->homework && $session->homework->count() > 0) {
                    foreach ($session->homework as $hw) {
                        $totalHomework++;

                        $submission = $hw->submissions
                            ->where('student_id', $studentId)
                            ->first();

                        if ($submission) {
                            $submittedHomework++;

                            if ($submission->grade !== null) {
                                $grades[] = $submission->grade;
                            }
                        }
                    }
                }
            }

            $averageGrade = count($grades) > 0
                ? round(array_sum($grades) / count($grades), 1)
                : null;

            return [
                'completion_percentage' => $totalSessions > 0
                    ? round(($completedSessions / $totalSessions) * 100)
                    : 0,
                'sessions_attended' => $attendedSessions,
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'attendance_rate' => $totalSessions > 0
                    ? round(($attendedSessions / $totalSessions) * 100)
                    : 0,
                'homework_submitted' => $submittedHomework,
                'total_homework' => $totalHomework,
                'homework_completion_rate' => $totalHomework > 0
                    ? round(($submittedHomework / $totalHomework) * 100)
                    : 0,
                'average_grade' => $averageGrade,
                'graded_homework' => count($grades)
            ];
        });
    }

    /**
     * Update progress record in database
     */
    public function updateProgress(int $courseId, int $studentId): void
    {
        // Clear cache
        Cache::forget("interactive_progress_{$courseId}_{$studentId}");

        // Recalculate
        $progress = $this->calculateCourseProgress($courseId, $studentId);

        // Update or create progress record
        InteractiveCourseProgress::updateOrCreate(
            [
                'course_id' => $courseId,
                'student_id' => $studentId
            ],
            [
                'completion_percentage' => $progress['completion_percentage'],
                'sessions_attended' => $progress['sessions_attended'],
                'homework_submitted' => $progress['homework_submitted'],
                'average_grade' => $progress['average_grade'],
                'last_updated' => now()
            ]
        );
    }

    /**
     * Clear progress cache for a student
     */
    public function clearCache(int $courseId, int $studentId): void
    {
        Cache::forget("interactive_progress_{$courseId}_{$studentId}");
    }

    /**
     * Get progress status color based on completion percentage
     */
    public function getProgressColor(int $percentage): string
    {
        if ($percentage >= 80) {
            return 'green';
        } elseif ($percentage >= 50) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /**
     * Get attendance status color
     */
    public function getAttendanceColor(int $attendanceRate): string
    {
        if ($attendanceRate >= 90) {
            return 'green';
        } elseif ($attendanceRate >= 75) {
            return 'yellow';
        } else {
            return 'red';
        }
    }
}
