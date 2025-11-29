<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Models\MeetingAttendance;
use App\Models\User;

/**
 * Academic Report Service
 *
 * Handles attendance synchronization for academic sessions.
 * Extends BaseReportSyncService with Academic-specific logic.
 */
class AcademicReportService extends BaseReportSyncService
{
    /**
     * Get the report model class for Academic sessions
     */
    protected function getReportClass(): string
    {
        return AcademicSessionReport::class;
    }

    /**
     * Get the foreign key for Academic session reports
     */
    protected function getSessionReportForeignKey(): string
    {
        return 'session_id'; // AcademicSessionReport uses 'session_id'
    }

    /**
     * Get the teacher for an Academic session
     */
    protected function getSessionTeacher($session): ?User
    {
        return $session->academicTeacher;
    }

    /**
     * Determine attendance status for Academic sessions
     * Academic sessions: Fixed 15 min grace, 80% attendance threshold
     * (From AcademicAttendanceService:380-408)
     */
    protected function determineAttendanceStatus(
        MeetingAttendance $meetingAttendance,
        $session,
        int $actualMinutes,
        float $attendancePercentage
    ): string {
        // Academic sessions typically require 80% attendance to be considered "present"
        $requiredPercentage = 80;

        // Fixed 15 minutes grace period for academic sessions
        $graceTimeMinutes = 15;

        // Check if student was late (joined after session start)
        $sessionStart = $session->scheduled_at;
        $firstJoin = $meetingAttendance->first_join_time;
        $isLate = false;

        if ($sessionStart && $firstJoin) {
            $lateMinutes = $sessionStart->diffInMinutes($firstJoin, false);
            $isLate = $lateMinutes > $graceTimeMinutes;
        }

        // Determine status based on attendance percentage
        if ($attendancePercentage >= $requiredPercentage) {
            return $isLate ? AttendanceStatus::LATE->value : AttendanceStatus::ATTENDED->value;
        } elseif ($attendancePercentage > 0) {
            return AttendanceStatus::LEAVED->value;
        } else {
            return AttendanceStatus::ABSENT->value;
        }
    }

    /**
     * Get the performance field name for Academic sessions
     */
    protected function getPerformanceFieldName(): string
    {
        return 'homework_degree'; // Academic performance metric (0-10)
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Record homework grade for academic session
     */
    public function recordHomeworkGrade(
        AcademicSessionReport $report,
        float $grade,
        ?string $notes = null
    ): AcademicSessionReport {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Homework grade must be between 0 and 10');
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
     * Get students for an Academic session
     */
    protected function getSessionStudents(AcademicSession $session): \Illuminate\Support\Collection
    {
        if ($session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        if ($session->academicSubscription) {
            return $session->academicSubscription->students ?? collect();
        }

        return collect();
    }

    /**
     * Create reports for all students in an academic session
     *
     * @param AcademicSession $session
     * @return \Illuminate\Support\Collection Collection of created reports
     */
    public function createReportsForSession(AcademicSession $session): \Illuminate\Support\Collection
    {
        $students = $this->getSessionStudents($session);
        $reports = collect();

        foreach ($students as $student) {
            // Check if report already exists
            $existingReport = AcademicSessionReport::where('session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existingReport) {
                $reports->push($existingReport);
                continue;
            }

            // Create new report
            $report = AcademicSessionReport::create([
                'session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => $this->getSessionTeacher($session)?->id,
                'academy_id' => $session->academy_id,
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'is_calculated' => false,
                'manually_evaluated' => false,
            ]);

            $reports->push($report);
        }

        return $reports;
    }

    /**
     * Record complete academic evaluation for a report
     */
    public function recordFullEvaluation(
        AcademicSessionReport $report,
        array $data
    ): AcademicSessionReport {
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
                throw new \InvalidArgumentException('Homework degree must be between 0 and 10');
            }
            $updateData['homework_degree'] = $grade;
        }

        // Notes
        if (isset($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }

        if (!empty($updateData)) {
            $updateData['evaluated_at'] = now();
            $report->update($updateData);
        }

        return $report->fresh();
    }

    // ========================================
    // Subscription Report Calculation Methods
    // ========================================

    /**
     * Calculate performance metrics for an academic subscription
     */
    public function calculatePerformance(AcademicSubscription $subscription): array
    {
        $sessions = $subscription->sessions()->with('studentReports')->get();
        $reports = $sessions->flatMap(function ($session) use ($subscription) {
            return $session->studentReports->where('student_id', $subscription->student_id);
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
     * Calculate attendance metrics for an academic subscription
     */
    public function calculateAttendance(AcademicSubscription $subscription): array
    {
        $sessions = $subscription->sessions()->with('studentReports')->get();
        $completedSessions = $sessions->filter(function ($session) {
            return $session->status?->value === 'completed' || $session->status === 'completed';
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
            $report = $session->studentReports->where('student_id', $subscription->student_id)->first();

            if (!$report) {
                $absent++;
                continue;
            }

            $status = $report->attendance_status;
            if (is_object($status)) {
                $status = $status->value;
            }

            if (in_array($status, ['attended', 'leaved'])) {
                $attended++;
            } elseif ($status === 'late') {
                $late++;
                $attended++; // Late counts as attended
            } else {
                $absent++;
            }
        }

        $attendanceRate = $totalCompleted > 0 ? round(($attended / $totalCompleted) * 100) : 0;

        return [
            'attendance_rate' => $attendanceRate,
            'attended' => $attended,
            'absent' => $absent,
            'late' => $late,
            'total_sessions' => $totalCompleted,
        ];
    }

    /**
     * Calculate progress metrics for an academic subscription
     */
    public function calculateProgress(AcademicSubscription $subscription): array
    {
        $sessions = $subscription->sessions()->with('studentReports')->get();
        $totalSessions = $sessions->count();

        $completedSessions = $sessions->filter(function ($session) {
            return $session->status?->value === 'completed' || $session->status === 'completed';
        })->count();

        // Calculate homework metrics
        $homeworkAssigned = $sessions->filter(function ($session) {
            return !empty($session->homework_description);
        })->count();

        $homeworkSubmitted = $sessions->flatMap(function ($session) use ($subscription) {
            return $session->studentReports
                ->where('student_id', $subscription->student_id)
                ->whereNotNull('homework_submitted_at');
        })->count();

        $homeworkCompletionRate = $homeworkAssigned > 0
            ? round(($homeworkSubmitted / $homeworkAssigned) * 100)
            : 0;

        // Grade improvement (compare first half vs second half of sessions)
        $gradeImprovement = 0;
        if ($completedSessions >= 4) {
            $orderedSessions = $sessions->sortBy('scheduled_at')->values();
            $halfPoint = (int) floor($orderedSessions->count() / 2);

            $firstHalf = $orderedSessions->take($halfPoint);
            $secondHalf = $orderedSessions->skip($halfPoint);

            $firstHalfGrades = $firstHalf->flatMap(function ($s) use ($subscription) {
                return $s->studentReports->where('student_id', $subscription->student_id)
                    ->whereNotNull('homework_degree')
                    ->pluck('homework_degree');
            });

            $secondHalfGrades = $secondHalf->flatMap(function ($s) use ($subscription) {
                return $s->studentReports->where('student_id', $subscription->student_id)
                    ->whereNotNull('homework_degree')
                    ->pluck('homework_degree');
            });

            if ($firstHalfGrades->count() > 0 && $secondHalfGrades->count() > 0) {
                $firstAvg = $firstHalfGrades->avg();
                $secondAvg = $secondHalfGrades->avg();
                $gradeImprovement = round($secondAvg - $firstAvg, 1);
            }
        }

        return [
            'sessions_completed' => $completedSessions,
            'total_sessions' => $totalSessions,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'grade_improvement' => $gradeImprovement,
            'homework_assigned' => $homeworkAssigned,
            'homework_submitted' => $homeworkSubmitted,
            'homework_completion_rate' => $homeworkCompletionRate,
        ];
    }
}
