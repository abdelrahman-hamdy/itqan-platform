<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Report Model
 *
 * Extends BaseSessionReport with Academic-specific fields:
 * - homework_degree: Homework quality score (0-10)
 * - notes: Teacher notes (inherited from base)
 *
 * Simplified to match Interactive Session Reports structure.
 *
 * @property float|null $homework_degree
 */
class AcademicSessionReport extends BaseSessionReport
{
    /**
     * Academic-specific fillable fields (includes base fields + Academic-specific)
     */
    protected $fillable = [
        // Base fields from BaseSessionReport
        'session_id',
        'student_id',
        'teacher_id',
        'academy_id',
        'notes',
        'meeting_enter_time',
        'meeting_leave_time',
        'actual_attendance_minutes',
        'is_late',
        'late_minutes',
        'attendance_status',
        'attendance_percentage',
        'meeting_events',
        'evaluated_at',
        'is_calculated',
        'manually_evaluated',
        'override_reason',

        // Homework tracking fields (unified homework system)
        'homework_submitted_at',
        'homework_submission_id',

        // Academic-specific field (unified with Interactive)
        'homework_degree',                  // Homework quality (0-10)
    ];

    /**
     * Academic-specific casts (includes base casts + Academic-specific)
     */
    protected $casts = [
        // Base casts inherited
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'actual_attendance_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_calculated' => 'boolean',
        'manually_evaluated' => 'boolean',

        // Homework tracking casts
        'homework_submitted_at' => 'datetime',

        // Academic-specific cast (0-10 scale, 1 decimal)
        'homework_degree' => 'decimal:1',
    ];

    // ========================================
    // Implementation of Abstract Methods
    // ========================================

    /**
     * Get the academic session this report belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Get Academic-specific performance data
     * Returns homework_degree as the performance metric (0-10 scale)
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        return $this->homework_degree;
    }

    /**
     * Academic sessions use grace period from academy settings
     */
    protected function getGracePeriodMinutes(): int
    {
        return $this->session?->academy?->settings?->default_late_tolerance_minutes ?? 15;
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Record homework grade
     */
    public function recordHomeworkGrade(float $grade, ?string $notes = null): void
    {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Homework grade must be between 0 and 10');
        }

        $data = [
            'homework_degree' => $grade,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $this->update($data);
    }

    /**
     * Record teacher evaluation (unified method)
     */
    public function recordTeacherEvaluation(
        ?float $homeworkDegree = null,
        ?string $notes = null
    ): void {
        $data = array_filter([
            'homework_degree' => $homeworkDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ], fn($value) => $value !== null);

        $this->update($data);
    }

    // Note: homeworkSubmission() relationship is inherited from BaseSessionReport
    // It uses homework_submission_id foreign key for direct linking

    // ========================================
    // Additional Scopes
    // ========================================

    public function scopeGraded($query)
    {
        return $query->whereNotNull('homework_degree');
    }

    // ========================================
    // Statistics Methods
    // ========================================

    /**
     * Get attendance statistics for a student across sessions
     */
    public static function getAttendanceStatistics(int $studentId, ?int $academicSubscriptionId = null): array
    {
        $query = static::where('student_id', $studentId);

        if ($academicSubscriptionId) {
            $query->whereHas('session', function ($q) use ($academicSubscriptionId) {
                $q->where('academic_subscription_id', $academicSubscriptionId);
            });
        }

        $reports = $query->get();
        $totalSessions = $reports->count();

        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'attended' => 0,
                'absent' => 0,
                'late' => 0,
                'attendance_rate' => 0,
            ];
        }

        $attended = $reports->whereIn('attendance_status', [AttendanceStatus::ATTENDED->value, AttendanceStatus::LEFT->value])->count();
        $absent = $reports->where('attendance_status', AttendanceStatus::ABSENT->value)->count();
        $late = $reports->where('attendance_status', AttendanceStatus::LATE->value)->count();

        return [
            'total_sessions' => $totalSessions,
            'attended' => $attended + $late, // Late counts as attended
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => round((($attended + $late) / $totalSessions) * 100, 1),
        ];
    }

    /**
     * Get performance statistics for a student
     */
    public static function getPerformanceStatistics(int $studentId, ?int $academicSubscriptionId = null): array
    {
        $query = static::where('student_id', $studentId)
            ->whereNotNull('homework_degree');

        if ($academicSubscriptionId) {
            $query->whereHas('session', function ($q) use ($academicSubscriptionId) {
                $q->where('academic_subscription_id', $academicSubscriptionId);
            });
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return [
                'average_homework_degree' => 0,
                'average_overall_performance' => 0,
                'total_evaluated' => 0,
            ];
        }

        return [
            'average_homework_degree' => round($reports->avg('homework_degree') ?? 0, 1),
            'average_overall_performance' => round($reports->avg('homework_degree') ?? 0, 1),
            'total_evaluated' => $reports->count(),
        ];
    }

    /**
     * Get progress statistics for a student
     */
    public static function getProgressStatistics(int $studentId, ?int $academicSubscriptionId = null): array
    {
        $query = static::where('student_id', $studentId);

        if ($academicSubscriptionId) {
            $query->whereHas('session', function ($q) use ($academicSubscriptionId) {
                $q->where('academic_subscription_id', $academicSubscriptionId);
            });
        }

        $reports = $query->with('session')->orderBy('created_at')->get();

        $completedSessions = $reports->filter(function ($report) {
            if (!$report->session) return false;
            $statusValue = $report->session->status instanceof SessionStatus
                ? $report->session->status->value
                : $report->session->status;
            return $statusValue === SessionStatus::COMPLETED->value;
        })->count();

        $totalSessions = $reports->count();

        // Calculate grade improvement (compare first 3 sessions to last 3 sessions)
        $gradeImprovement = 0;
        $gradedReports = $reports->filter(fn($r) => $r->homework_degree !== null);

        if ($gradedReports->count() >= 6) {
            $firstThree = $gradedReports->take(3)->avg('homework_degree');
            $lastThree = $gradedReports->slice(-3)->avg('homework_degree');
            $gradeImprovement = round($lastThree - $firstThree, 1);
        }

        return [
            'sessions_completed' => $completedSessions,
            'total_sessions' => $totalSessions,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'average_grade' => round($gradedReports->avg('homework_degree') ?? 0, 1),
            'grade_improvement' => $gradeImprovement,
        ];
    }

    /**
     * Get comprehensive report data for a student
     */
    public static function getComprehensiveReport(int $studentId, ?int $academicSubscriptionId = null): array
    {
        return [
            'attendance' => static::getAttendanceStatistics($studentId, $academicSubscriptionId),
            'performance' => static::getPerformanceStatistics($studentId, $academicSubscriptionId),
            'progress' => static::getProgressStatistics($studentId, $academicSubscriptionId),
        ];
    }
}
