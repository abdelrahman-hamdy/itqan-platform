<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractiveCourseProgress extends Model
{
    use HasFactory;

    protected $table = 'interactive_course_progress';

    protected $fillable = [
        'academy_id',
        'course_id',
        'student_id',
        'total_sessions',
        'sessions_attended',
        'sessions_completed',
        'attendance_percentage',
        'homework_assigned',
        'homework_submitted',
        'homework_graded',
        'average_homework_score',
        'overall_score',
        'progress_status',
        'completion_percentage',
        'started_at',
        'completed_at',
        'last_activity_at',
        'days_since_last_activity',
        'is_at_risk',
    ];

    protected $casts = [
        'attendance_percentage' => 'decimal:2',
        'average_homework_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_at_risk' => 'boolean',
        'total_sessions' => 'integer',
        'sessions_attended' => 'integer',
        'sessions_completed' => 'integer',
        'homework_assigned' => 'integer',
        'homework_submitted' => 'integer',
        'homework_graded' => 'integer',
        'days_since_last_activity' => 'integer',
    ];

    protected $attributes = [
        'progress_status' => 'not_started',
        'total_sessions' => 0,
        'sessions_attended' => 0,
        'sessions_completed' => 0,
        'attendance_percentage' => 0.00,
        'homework_assigned' => 0,
        'homework_submitted' => 0,
        'homework_graded' => 0,
        'completion_percentage' => 0.00,
        'days_since_last_activity' => 0,
        'is_at_risk' => false,
    ];

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scopes
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('progress_status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('progress_status', 'completed');
    }

    public function scopeAtRisk($query)
    {
        return $query->where('is_at_risk', true);
    }

    /**
     * Calculate and update all progress metrics
     */
    public function recalculate(): void
    {
        $course = $this->course;
        $student = $this->student;

        // Get enrollment
        $enrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$enrollment || $enrollment->enrollment_status !== 'enrolled') {
            return;
        }

        // Calculate session attendance
        $totalSessions = $course->sessions()->count();
        $attendanceRecords = InteractiveSessionAttendance::whereHas('session', function ($query) use ($course) {
            $query->where('course_id', $course->id);
        })->where('student_id', $student->id)->get();

        $sessionsAttended = $attendanceRecords->whereIn('attendance_status', ['present', 'late'])->count();
        $sessionsCompleted = $attendanceRecords->where('attendance_status', 'present')->count();

        // Calculate homework metrics
        $homeworkRecords = InteractiveCourseHomework::whereHas('session', function ($query) use ($course) {
            $query->where('course_id', $course->id);
        })->where('student_id', $student->id)->get();

        $homeworkAssigned = $homeworkRecords->count();
        $homeworkSubmitted = $homeworkRecords->whereIn('submission_status', ['submitted', 'late', 'graded', 'returned'])->count();
        $homeworkGraded = $homeworkRecords->whereIn('submission_status', ['graded', 'returned'])->count();

        $gradedHomework = $homeworkRecords->whereNotNull('score');
        $averageScore = $gradedHomework->isNotEmpty()
            ? round($gradedHomework->avg('score'), 2)
            : null;

        // Calculate percentages
        $attendancePercentage = $totalSessions > 0
            ? round(($sessionsAttended / $totalSessions) * 100, 2)
            : 0.00;

        $completionPercentage = $totalSessions > 0
            ? round((($sessionsCompleted + $homeworkSubmitted) / ($totalSessions * 2)) * 100, 2)
            : 0.00;

        // Determine progress status
        $progressStatus = 'not_started';
        if ($sessionsAttended > 0 || $homeworkSubmitted > 0) {
            $progressStatus = 'in_progress';
        }
        if ($completionPercentage >= 100) {
            $progressStatus = 'completed';
        }

        // Check if at risk (no activity in 7 days and attendance < 50%)
        $daysSinceLastActivity = $this->last_activity_at
            ? $this->last_activity_at->diffInDays(now())
            : 0;

        $isAtRisk = $daysSinceLastActivity >= 7 && $attendancePercentage < 50;

        // Update all metrics
        $this->update([
            'total_sessions' => $totalSessions,
            'sessions_attended' => $sessionsAttended,
            'sessions_completed' => $sessionsCompleted,
            'attendance_percentage' => $attendancePercentage,
            'homework_assigned' => $homeworkAssigned,
            'homework_submitted' => $homeworkSubmitted,
            'homework_graded' => $homeworkGraded,
            'average_homework_score' => $averageScore,
            'completion_percentage' => $completionPercentage,
            'progress_status' => $progressStatus,
            'days_since_last_activity' => $daysSinceLastActivity,
            'is_at_risk' => $isAtRisk,
            'completed_at' => $progressStatus === 'completed' ? now() : null,
        ]);
    }

    /**
     * Mark activity (student did something)
     */
    public function recordActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
            'days_since_last_activity' => 0,
        ]);

        if ($this->progress_status === 'not_started') {
            $this->update([
                'started_at' => now(),
                'progress_status' => 'in_progress',
            ]);
        }
    }

    /**
     * Get progress status in Arabic
     */
    public function getProgressStatusInArabicAttribute(): string
    {
        return match($this->progress_status) {
            'not_started' => 'لم يبدأ',
            'in_progress' => 'قيد التقدم',
            'completed' => 'مكتمل',
            'dropped' => 'متوقف',
            default => 'غير معروف',
        };
    }

    /**
     * Get completion badge color
     */
    public function getCompletionBadgeColorAttribute(): string
    {
        return match(true) {
            $this->completion_percentage >= 75 => 'green',
            $this->completion_percentage >= 50 => 'blue',
            $this->completion_percentage >= 25 => 'yellow',
            default => 'red',
        };
    }

    /**
     * Get attendance badge color
     */
    public function getAttendanceBadgeColorAttribute(): string
    {
        return match(true) {
            $this->attendance_percentage >= 80 => 'green',
            $this->attendance_percentage >= 60 => 'yellow',
            default => 'red',
        };
    }

    /**
     * Check if student is performing well
     */
    public function isPerformingWell(): bool
    {
        return $this->attendance_percentage >= 80 &&
               ($this->average_homework_score >= 70 || $this->average_homework_score === null);
    }

    /**
     * Get next milestone
     */
    public function getNextMilestoneAttribute(): ?array
    {
        if ($this->progress_status === 'completed') {
            return null;
        }

        if ($this->sessions_attended < $this->total_sessions) {
            return [
                'type' => 'session',
                'description' => 'حضور الجلسة التالية',
                'progress' => ($this->sessions_attended / $this->total_sessions) * 100,
            ];
        }

        if ($this->homework_submitted < $this->homework_assigned) {
            return [
                'type' => 'homework',
                'description' => 'تسليم الواجب المنزلي',
                'progress' => ($this->homework_submitted / $this->homework_assigned) * 100,
            ];
        }

        return null;
    }

    /**
     * Static method to get or create progress for a student
     */
    public static function getOrCreateForStudent(InteractiveCourse $course, User $student): self
    {
        return static::firstOrCreate(
            [
                'course_id' => $course->id,
                'student_id' => $student->id,
            ],
            [
                'academy_id' => $course->academy_id,
                'total_sessions' => $course->sessions()->count(),
            ]
        );
    }
}
