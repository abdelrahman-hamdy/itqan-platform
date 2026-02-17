<?php

namespace App\Services\Attendance;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use App\Models\InteractiveCourse;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\User;

/**
 * Interactive Report Service
 *
 * Handles attendance synchronization for interactive course sessions.
 * Extends BaseReportSyncService with Interactive-specific logic.
 */
class InteractiveReportService extends BaseReportSyncService
{
    /**
     * Get the report model class for Interactive sessions
     */
    protected function getReportClass(): string
    {
        return InteractiveSessionReport::class;
    }

    /**
     * Get the foreign key for Interactive session reports
     */
    protected function getSessionReportForeignKey(): string
    {
        return 'session_id';
    }

    /**
     * Get the teacher for an Interactive session
     */
    protected function getSessionTeacher($session): ?User
    {
        return $session->course?->teacher ?? null;
    }

    /**
     * Get the attendance threshold percentage for Interactive sessions.
     * Accesses academy settings via course relationship, falls back to config default.
     */
    protected function getAttendanceThreshold($session): float
    {
        return $session->course?->academy?->settings?->default_attendance_threshold_percentage
            ?? config('business.attendance.threshold_percent', 80);
    }

    /**
     * Get the grace period for Interactive sessions.
     * Accesses academy settings via course relationship.
     */
    protected function getGracePeriod($session): int
    {
        return $session->course?->academy?->settings?->default_late_tolerance_minutes
            ?? config('business.attendance.grace_period_minutes', 15);
    }

    /**
     * Get the performance field name for Interactive sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'homework_degree'; // Interactive performance metric (0-10)
    }

    // ========================================
    // Interactive-Specific Methods
    // ========================================

    /**
     * Record homework grade for interactive session
     */
    public function recordHomeworkGrade(
        InteractiveSessionReport $report,
        float $grade,
        ?string $notes = null
    ): InteractiveSessionReport {
        if ($grade < 0 || $grade > 10) {
            throw new InvalidArgumentException('Homework grade must be between 0 and 10');
        }

        $report->update([
            'homework_degree' => $grade,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ]);

        return $report->fresh();
    }

    /**
     * Get students for an Interactive session
     */
    protected function getSessionStudents(InteractiveCourseSession $session): Collection
    {
        if ($session->course) {
            return $session->course->enrollments->pluck('student')->filter();
        }

        return collect();
    }

    /**
     * Create reports for all students enrolled in an interactive course session
     *
     * @return Collection Collection of created reports
     */
    public function createReportsForSession(InteractiveCourseSession $session): Collection
    {
        $students = $this->getSessionStudents($session);
        $reports = collect();

        foreach ($students as $student) {
            // Check if report already exists
            $existingReport = InteractiveSessionReport::where('session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existingReport) {
                $reports->push($existingReport);

                continue;
            }

            // Create new report
            $report = InteractiveSessionReport::create([
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $this->getSessionTeacher($session)?->id,
                'academy_id' => $session->course?->academy_id,
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'is_calculated' => false,
                'manually_evaluated' => false,
            ]);

            $reports->push($report);
        }

        return $reports;
    }

    // ========================================
    // Course Report Calculation Methods
    // ========================================

    /**
     * Calculate performance metrics for an interactive course
     */
    public function calculatePerformance(InteractiveCourse $course, ?int $studentId = null): array
    {
        $sessions = $course->sessions()->with('studentReports')->get();

        // Filter reports by student if specified
        $reports = $sessions->flatMap(function ($session) use ($studentId) {
            $studentReports = $session->studentReports;
            if ($studentId) {
                $studentReports = $studentReports->where('student_id', $studentId);
            }

            return $studentReports;
        });

        $totalReports = $reports->count();

        if ($totalReports === 0) {
            return [
                'average_overall_performance' => 0,
                'average_homework_degree' => 0,
                'total_evaluated' => 0,
            ];
        }

        // Calculate averages
        $homeworkSum = $reports->sum('homework_degree');
        $homeworkCount = $reports->whereNotNull('homework_degree')->count();
        $avgHomework = $homeworkCount > 0 ? $homeworkSum / $homeworkCount : 0;

        return [
            'average_overall_performance' => round($avgHomework, 1),
            'average_homework_degree' => round($avgHomework, 1),
            'total_evaluated' => $homeworkCount,
        ];
    }

    /**
     * Calculate attendance metrics for an interactive course
     */
    public function calculateAttendance(InteractiveCourse $course, ?int $studentId = null): array
    {
        $sessions = $course->sessions()->with('studentReports')->get();
        $completedSessions = $sessions->filter(function ($session) {
            return $session->status === SessionStatus::COMPLETED;
        });

        $totalCompleted = $completedSessions->count();

        if ($totalCompleted === 0) {
            return [
                'attendance_rate' => 0,
                'attended' => 0,
                'absent' => 0,
                'late' => 0,
                'total_sessions' => $totalCompleted,
            ];
        }

        $attended = 0;
        $absent = 0;
        $late = 0;

        foreach ($completedSessions as $session) {
            $reports = $session->studentReports;
            if ($studentId) {
                $reports = $reports->where('student_id', $studentId);
            }

            foreach ($reports->unique('student_id') as $report) {
                $status = $report->attendance_status;
                if (is_object($status)) {
                    $status = $status->value;
                }

                if (in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LEFT->value])) {
                    $attended++;
                } elseif ($status === AttendanceStatus::LATE->value) {
                    $late++;
                    $attended++; // Late counts as attended
                } else {
                    $absent++;
                }
            }
        }

        // For course-wide stats (no specific student), calculate per-session averages
        $totalStudents = $course->enrollments->count() ?: 1;
        if (! $studentId) {
            $attendanceRate = $totalCompleted > 0 && $totalStudents > 0
                ? round(($attended / ($totalCompleted * $totalStudents)) * 100)
                : 0;
        } else {
            $attendanceRate = $totalCompleted > 0 ? round(($attended / $totalCompleted) * 100) : 0;
        }

        return [
            'attendance_rate' => min(100, $attendanceRate),
            'attended' => $attended,
            'absent' => $absent,
            'late' => $late,
            'total_sessions' => $totalCompleted,
        ];
    }

    /**
     * Calculate progress metrics for an interactive course
     */
    public function calculateProgress(InteractiveCourse $course): array
    {
        $sessions = $course->sessions()->with('studentReports')->get();
        $totalSessions = $sessions->count();

        $completedSessions = $sessions->filter(function ($session) {
            return $session->status === SessionStatus::COMPLETED;
        })->count();

        // Calculate homework metrics
        $homeworkAssigned = $sessions->filter(function ($session) {
            return ! empty($session->homework_description);
        })->count();

        $homeworkSubmitted = $sessions->flatMap(function ($session) {
            return $session->studentReports->whereNotNull('homework_submitted_at');
        })->count();

        $homeworkCompletionRate = $homeworkAssigned > 0
            ? round(($homeworkSubmitted / $homeworkAssigned) * 100)
            : 0;

        // Calculate average grade improvement (first half vs second half)
        $gradeImprovement = 0;
        if ($completedSessions >= 4) {
            $orderedSessions = $sessions->sortBy('scheduled_at')->values();
            $halfPoint = (int) floor($orderedSessions->count() / 2);

            $firstHalf = $orderedSessions->take($halfPoint);
            $secondHalf = $orderedSessions->skip($halfPoint);

            $firstHalfScores = $firstHalf->flatMap(function ($s) {
                return $s->studentReports->whereNotNull('homework_degree')->pluck('homework_degree');
            });

            $secondHalfScores = $secondHalf->flatMap(function ($s) {
                return $s->studentReports->whereNotNull('homework_degree')->pluck('homework_degree');
            });

            if ($firstHalfScores->count() > 0 && $secondHalfScores->count() > 0) {
                $firstAvg = $firstHalfScores->avg();
                $secondAvg = $secondHalfScores->avg();
                $gradeImprovement = round($secondAvg - $firstAvg, 1);
            }
        }

        return [
            'sessions_completed' => $completedSessions,
            'total_sessions' => $totalSessions,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'grade_improvement' => $gradeImprovement,
            'enrolled_students' => $course->enrollments->count(),
            'homework_assigned' => $homeworkAssigned,
            'homework_submitted' => $homeworkSubmitted,
            'homework_completion_rate' => $homeworkCompletionRate,
        ];
    }

    /**
     * Record complete interactive evaluation for a report
     */
    public function recordFullEvaluation(
        InteractiveSessionReport $report,
        array $data
    ): InteractiveSessionReport {
        $updateData = [];

        // Attendance status
        if (isset($data['attendance_status'])) {
            $updateData['attendance_status'] = $data['attendance_status'];
            $updateData['manually_evaluated'] = true;
        }

        // Homework degree (0-10)
        if (isset($data['homework_degree'])) {
            $grade = (float) $data['homework_degree'];
            if ($grade < 0 || $grade > 10) {
                throw new InvalidArgumentException('Homework degree must be between 0 and 10');
            }
            $updateData['homework_degree'] = $grade;
        }

        // Notes
        if (isset($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }

        if (! empty($updateData)) {
            $updateData['evaluated_at'] = now();
            $report->update($updateData);
        }

        return $report->fresh();
    }
}
