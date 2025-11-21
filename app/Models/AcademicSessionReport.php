<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academic Session Report Model
 *
 * Extends BaseSessionReport with Academic-specific fields:
 * - academic_grade: Overall grade (0-10) - manually set or auto-calculated
 * - lesson_understanding_degree: Understanding/mastery score (0-10)
 * - homework_completion_degree: Homework quality score (0-10)
 * - homework_description: Homework assignment text
 * - homework_file: Homework file path
 * - homework_submitted_at: Student submission timestamp
 * - homework_feedback: Teacher feedback on homework
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

        // Academic-specific grading fields (matching migration column names)
        'academic_grade',                   // Overall academic grade (0-10)
        'lesson_understanding_degree',      // Understanding/mastery (0-10)
        'homework_completion_degree',       // Homework quality (0-10)

        // Homework management fields
        'homework_description',             // Homework assignment text
        'homework_file',                    // Homework file path
        'homework_submitted_at',            // When student submitted homework
        'homework_feedback',                // Teacher feedback on homework

        // Connection quality
        'connection_quality_score',         // 1-5 score
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

        // Academic-specific grade casts (0-10 scale, 1 decimal)
        'academic_grade' => 'decimal:1',
        'lesson_understanding_degree' => 'decimal:1',
        'homework_completion_degree' => 'decimal:1',

        // Homework casts
        'homework_submitted_at' => 'datetime',

        // Connection quality
        'connection_quality_score' => 'integer',
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
     * Get Academic-specific performance data (calculated from multiple grades)
     *
     * Calculation weights:
     * - Participation: 30%
     * - Lesson Understanding: 40%
     * - Homework Completion: 30%
     *
     * If academic_grade is set manually, it takes precedence.
     */
    protected function getSessionSpecificPerformanceData(): ?float
    {
        // If manually set academic grade exists, use it
        if ($this->academic_grade !== null) {
            return (float) $this->academic_grade;
        }

        // Calculate from component grades
        $grades = array_filter([
            'understanding' => $this->lesson_understanding_degree,
            'homework' => $this->homework_completion_degree,
        ], fn($grade) => $grade !== null);

        if (empty($grades)) {
            return null;
        }

        // Weighted average calculation
        $weights = [
            'understanding' => 0.50,  // 50%
            'homework' => 0.50,       // 50%
        ];

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($grades as $key => $value) {
            $weightedSum += $value * $weights[$key];
            $totalWeight += $weights[$key];
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null;
    }

    /**
     * Academic sessions use 15 minutes grace period (default)
     */
    protected function getGracePeriodMinutes(): int
    {
        return 15; // Default 15 minutes grace period for academic sessions
    }

    // ========================================
    // Academic-Specific Methods
    // ========================================

    /**
     * Check if homework has been submitted by student
     */
    public function hasSubmittedHomework(): bool
    {
        return $this->homework_submitted_at !== null;
    }

    /**
     * Check if homework has been assigned by teacher
     */
    public function hasHomeworkAssigned(): bool
    {
        return ! empty($this->homework_description) || ! empty($this->homework_file);
    }

    /**
     * Record homework assignment from teacher
     */
    public function assignHomework(string $description, ?string $filePath = null): void
    {
        $this->update([
            'homework_description' => $description,
            'homework_file' => $filePath,
        ]);
    }

    /**
     * Record student homework submission
     */
    public function submitHomework(?string $filePath = null): void
    {
        $this->update([
            'homework_file' => $filePath,
            'homework_submitted_at' => now(),
        ]);
    }

    /**
     * Record teacher feedback on homework
     */
    public function recordHomeworkFeedback(float $grade, ?string $feedback = null): void
    {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Homework grade must be between 0 and 10');
        }

        $this->update([
            'homework_completion_degree' => $grade,
            'homework_feedback' => $feedback,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Record complete academic performance evaluation
     */
    public function recordPerformanceEvaluation(
        ?float $understandingDegree = null,
        ?float $homeworkDegree = null,
        ?string $notes = null
    ): void {
        $data = array_filter([
            'lesson_understanding_degree' => $understandingDegree,
            'homework_completion_degree' => $homeworkDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_evaluated' => true,
        ], fn($value) => $value !== null);

        $this->update($data);
    }

    /**
     * Manually set overall academic grade (overrides calculated grade)
     */
    public function setAcademicGrade(float $grade, ?string $reason = null): void
    {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Academic grade must be between 0 and 10');
        }

        $this->update([
            'academic_grade' => $grade,
            'manually_evaluated' => true,
            'override_reason' => $reason,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Get the overall performance (alias for compatibility)
     */
    public function getOverallPerformanceAttribute(): ?float
    {
        return $this->getSessionSpecificPerformanceData();
    }

    /**
     * Get homework submission from polymorphic relationship
     */
    public function homeworkSubmission()
    {
        return $this->hasOne(HomeworkSubmission::class, 'submitable_id')
            ->where('submitable_type', AcademicSession::class)
            ->where('student_id', $this->student_id);
    }

    // ========================================
    // Additional Scopes
    // ========================================

    public function scopeWithHomework($query)
    {
        return $query->whereNotNull('homework_description');
    }

    public function scopeHomeworkSubmitted($query)
    {
        return $query->whereNotNull('homework_submitted_at');
    }

    public function scopeGraded($query)
    {
        return $query->whereNotNull('academic_grade')
            ->orWhereNotNull('lesson_understanding_degree')
            ->orWhereNotNull('homework_completion_degree');
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

        $attended = $reports->where('attendance_status', 'present')->count();
        $absent = $reports->where('attendance_status', 'absent')->count();
        $late = $reports->where('attendance_status', 'late')->count();

        return [
            'total_sessions' => $totalSessions,
            'attended' => $attended,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => round(($attended / $totalSessions) * 100, 1),
        ];
    }

    /**
     * Get performance statistics for a student
     */
    public static function getPerformanceStatistics(int $studentId, ?int $academicSubscriptionId = null): array
    {
        $query = static::where('student_id', $studentId)
            ->whereNotNull('lesson_understanding_degree');

        if ($academicSubscriptionId) {
            $query->whereHas('session', function ($q) use ($academicSubscriptionId) {
                $q->where('academic_subscription_id', $academicSubscriptionId);
            });
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return [
                'average_understanding_degree' => 0,
                'average_homework_degree' => 0,
                'average_overall_performance' => 0,
            ];
        }

        return [
            'average_understanding_degree' => round($reports->avg('lesson_understanding_degree') ?? 0, 1),
            'average_homework_degree' => round($reports->avg('homework_completion_degree') ?? 0, 1),
            'average_overall_performance' => round($reports->map(fn($r) => $r->overall_performance)->filter()->avg() ?? 0, 1),
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
            return $report->session && $report->session->status === 'completed';
        })->count();

        $totalSessions = $reports->count();

        // Calculate homework statistics
        $homeworkAssigned = $reports->filter(fn($r) => $r->hasHomeworkAssigned())->count();
        $homeworkSubmitted = $reports->filter(fn($r) => $r->hasSubmittedHomework())->count();
        $homeworkCompletionRate = $homeworkAssigned > 0
            ? round(($homeworkSubmitted / $homeworkAssigned) * 100, 1)
            : 0;

        // Calculate grade improvement (compare first 3 sessions to last 3 sessions)
        $gradeImprovement = 0;
        $gradedReports = $reports->filter(fn($r) => $r->overall_performance !== null);

        if ($gradedReports->count() >= 6) {
            $firstThree = $gradedReports->take(3)->avg('overall_performance');
            $lastThree = $gradedReports->slice(-3)->avg('overall_performance');
            $gradeImprovement = round($lastThree - $firstThree, 1);
        }

        return [
            'sessions_completed' => $completedSessions,
            'total_sessions' => $totalSessions,
            'homework_assigned' => $homeworkAssigned,
            'homework_submitted' => $homeworkSubmitted,
            'homework_completion_rate' => $homeworkCompletionRate,
            'average_grade' => round($gradedReports->avg('overall_performance') ?? 0, 1),
            'grade_improvement' => $gradeImprovement,
            'topics_covered' => 0, // TODO: Track topics when implemented
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
