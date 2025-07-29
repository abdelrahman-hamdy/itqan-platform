<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AcademicProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'subscription_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'progress_code',
        'start_date',
        'last_session_date',
        'next_session_date',
        'total_sessions_planned',
        'total_sessions_completed',
        'total_sessions_missed',
        'total_sessions_cancelled',
        'attendance_rate',
        'overall_grade',
        'participation_score',
        'homework_completion_rate',
        'total_assignments_given',
        'total_assignments_completed',
        'total_quizzes_taken',
        'average_quiz_score',
        'learning_objectives',
        'completed_topics',
        'current_topics',
        'upcoming_topics',
        'curriculum_notes',
        'strengths',
        'weaknesses',
        'improvement_areas',
        'learning_style_notes',
        'teacher_feedback',
        'student_feedback',
        'parent_feedback',
        'monthly_reports',
        'last_report_generated',
        'consecutive_attended_sessions',
        'consecutive_missed_sessions',
        'last_attendance_update',
        'pending_assignments',
        'overdue_assignments',
        'last_assignment_submitted',
        'next_assignment_due',
        'last_teacher_contact',
        'last_student_contact',
        'last_parent_contact',
        'communication_notes',
        'engagement_level',
        'motivation_level',
        'behavioral_notes',
        'special_needs_notes',
        'short_term_goals',
        'long_term_goals',
        'achieved_milestones',
        'upcoming_milestones',
        'teacher_recommendations',
        'recommended_resources',
        'intervention_strategies',
        'needs_additional_support',
        'progress_status',
        'is_active',
        'admin_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'last_session_date' => 'date',
        'next_session_date' => 'date',
        'attendance_rate' => 'decimal:2',
        'overall_grade' => 'decimal:2',
        'participation_score' => 'decimal:2',
        'homework_completion_rate' => 'decimal:2',
        'average_quiz_score' => 'decimal:2',
        'learning_objectives' => 'array',
        'completed_topics' => 'array',
        'current_topics' => 'array',
        'upcoming_topics' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'improvement_areas' => 'array',
        'monthly_reports' => 'array',
        'last_report_generated' => 'datetime',
        'last_attendance_update' => 'datetime',
        'last_assignment_submitted' => 'date',
        'next_assignment_due' => 'date',
        'last_teacher_contact' => 'datetime',
        'last_student_contact' => 'datetime',
        'last_parent_contact' => 'datetime',
        'short_term_goals' => 'array',
        'long_term_goals' => 'array',
        'achieved_milestones' => 'array',
        'upcoming_milestones' => 'array',
        'recommended_resources' => 'array',
        'intervention_strategies' => 'array',
        'needs_additional_support' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'total_sessions_planned' => 0,
        'total_sessions_completed' => 0,
        'total_sessions_missed' => 0,
        'total_sessions_cancelled' => 0,
        'attendance_rate' => 0,
        'participation_score' => 0,
        'homework_completion_rate' => 0,
        'total_assignments_given' => 0,
        'total_assignments_completed' => 0,
        'total_quizzes_taken' => 0,
        'average_quiz_score' => 0,
        'consecutive_attended_sessions' => 0,
        'consecutive_missed_sessions' => 0,
        'pending_assignments' => 0,
        'overdue_assignments' => 0,
        'needs_additional_support' => false,
        'progress_status' => 'satisfactory',
        'is_active' => true,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->progress_code)) {
                $model->progress_code = $model->generateProgressCode();
            }
        });

        static::updating(function ($model) {
            // Auto-calculate attendance rate when sessions are updated
            if ($model->isDirty(['total_sessions_completed', 'total_sessions_missed', 'total_sessions_planned'])) {
                $model->calculateAttendanceRate();
            }

            // Auto-calculate homework completion rate
            if ($model->isDirty(['total_assignments_completed', 'total_assignments_given'])) {
                $model->calculateHomeworkCompletionRate();
            }
        });
    }

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * العلاقة مع الاشتراك الأكاديمي
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class);
    }

    /**
     * العلاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * العلاقة مع المعلم الأكاديمي
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacher::class, 'teacher_id');
    }

    /**
     * العلاقة مع المادة الدراسية
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * العلاقة مع منشئ السجل
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع محدث السجل
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * نطاق السجلات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق السجلات حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * نطاق السجلات حسب حالة التقدم
     */
    public function scopeByProgressStatus($query, $status)
    {
        return $query->where('progress_status', $status);
    }

    /**
     * نطاق السجلات التي تحتاج دعم إضافي
     */
    public function scopeNeedingSupport($query)
    {
        return $query->where('needs_additional_support', true);
    }

    /**
     * نطاق السجلات مع معدل حضور منخفض
     */
    public function scopeLowAttendance($query, $threshold = 70)
    {
        return $query->where('attendance_rate', '<', $threshold);
    }

    /**
     * توليد رمز التقدم
     */
    private function generateProgressCode(): string
    {
        $academyId = $this->academy_id;
        $count = static::where('academy_id', $academyId)->count() + 1;
        return 'PROG-' . $academyId . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * حساب معدل الحضور
     */
    private function calculateAttendanceRate(): void
    {
        $totalSessionsAttended = $this->total_sessions_completed + $this->total_sessions_missed;
        if ($totalSessionsAttended > 0) {
            $this->attendance_rate = ($this->total_sessions_completed / $totalSessionsAttended) * 100;
        }
    }

    /**
     * حساب معدل إنجاز الواجبات
     */
    private function calculateHomeworkCompletionRate(): void
    {
        if ($this->total_assignments_given > 0) {
            $this->homework_completion_rate = ($this->total_assignments_completed / $this->total_assignments_given) * 100;
        }
    }

    /**
     * الحصول على حالة التقدم بالعربية
     */
    public function getProgressStatusInArabicAttribute(): string
    {
        return match ($this->progress_status) {
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'satisfactory' => 'مقبول',
            'needs_improvement' => 'يحتاج تحسين',
            'concerning' => 'مثير للقلق',
            default => $this->progress_status,
        };
    }

    /**
     * الحصول على مستوى المشاركة بالعربية
     */
    public function getEngagementLevelInArabicAttribute(): string
    {
        return match ($this->engagement_level) {
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'average' => 'متوسط',
            'below_average' => 'دون المتوسط',
            'poor' => 'ضعيف',
            default => $this->engagement_level ?? 'غير محدد',
        };
    }

    /**
     * الحصول على مستوى التحفيز بالعربية
     */
    public function getMotivationLevelInArabicAttribute(): string
    {
        return match ($this->motivation_level) {
            'very_high' => 'عالي جداً',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
            'very_low' => 'منخفض جداً',
            default => $this->motivation_level ?? 'غير محدد',
        };
    }

    /**
     * تسجيل جلسة مكتملة
     */
    public function recordCompletedSession(?Carbon $sessionDate = null): void
    {
        $this->increment('total_sessions_completed');
        $this->increment('consecutive_attended_sessions');
        $this->update([
            'consecutive_missed_sessions' => 0,
            'last_session_date' => $sessionDate ?? Carbon::now(),
            'last_attendance_update' => Carbon::now(),
        ]);
    }

    /**
     * تسجيل جلسة متغيبة
     */
    public function recordMissedSession(?Carbon $sessionDate = null): void
    {
        $this->increment('total_sessions_missed');
        $this->increment('consecutive_missed_sessions');
        $this->update([
            'consecutive_attended_sessions' => 0,
            'last_attendance_update' => Carbon::now(),
        ]);
    }

    /**
     * تسجيل واجب جديد
     */
    public function assignHomework(?Carbon $dueDate = null): void
    {
        $this->increment('total_assignments_given');
        $this->increment('pending_assignments');
        
        if ($dueDate) {
            $this->update(['next_assignment_due' => $dueDate]);
        }
    }

    /**
     * تسجيل تسليم واجب
     */
    public function submitHomework(?Carbon $submissionDate = null): void
    {
        $this->increment('total_assignments_completed');
        $this->decrement('pending_assignments');
        $this->update([
            'last_assignment_submitted' => $submissionDate ?? Carbon::now(),
        ]);
    }

    /**
     * تسجيل اختبار جديد
     */
    public function recordQuiz(float $score): void
    {
        $this->increment('total_quizzes_taken');
        
        // Calculate new average
        $totalScore = ($this->average_quiz_score * ($this->total_quizzes_taken - 1)) + $score;
        $this->average_quiz_score = $totalScore / $this->total_quizzes_taken;
        $this->save();
    }

    /**
     * تحديث الدرجة الإجمالية
     */
    public function updateOverallGrade(float $grade): void
    {
        $this->update(['overall_grade' => $grade]);
        
        // Auto-update progress status based on grade
        $this->updateProgressStatusFromGrade();
    }

    /**
     * تحديث حالة التقدم بناءً على الدرجة
     */
    private function updateProgressStatusFromGrade(): void
    {
        if (!$this->overall_grade) return;
        
        $status = match (true) {
            $this->overall_grade >= 90 => 'excellent',
            $this->overall_grade >= 80 => 'good',
            $this->overall_grade >= 70 => 'satisfactory',
            $this->overall_grade >= 60 => 'needs_improvement',
            default => 'concerning',
        };
        
        $this->update(['progress_status' => $status]);
    }

    /**
     * إضافة تعليق من المعلم
     */
    public function addTeacherFeedback(string $feedback): void
    {
        $this->update([
            'teacher_feedback' => $feedback,
            'last_teacher_contact' => Carbon::now(),
        ]);
    }

    /**
     * إضافة تعليق من الطالب
     */
    public function addStudentFeedback(string $feedback): void
    {
        $this->update([
            'student_feedback' => $feedback,
            'last_student_contact' => Carbon::now(),
        ]);
    }

    /**
     * إضافة تعليق من ولي الأمر
     */
    public function addParentFeedback(string $feedback): void
    {
        $this->update([
            'parent_feedback' => $feedback,
            'last_parent_contact' => Carbon::now(),
        ]);
    }

    /**
     * إضافة موضوع مكتمل
     */
    public function addCompletedTopic(string $topic): void
    {
        $completed = $this->completed_topics ?? [];
        if (!in_array($topic, $completed)) {
            $completed[] = $topic;
            $this->update(['completed_topics' => $completed]);
        }
    }

    /**
     * تحديث المواضيع الحالية
     */
    public function updateCurrentTopics(array $topics): void
    {
        $this->update(['current_topics' => $topics]);
    }

    /**
     * إضافة هدف قصير المدى
     */
    public function addShortTermGoal(string $goal): void
    {
        $goals = $this->short_term_goals ?? [];
        $goals[] = [
            'goal' => $goal,
            'created_at' => Carbon::now()->toDateString(),
            'status' => 'active',
        ];
        $this->update(['short_term_goals' => $goals]);
    }

    /**
     * تسجيل إنجاز معلم
     */
    public function achieveMilestone(string $milestone): void
    {
        $achieved = $this->achieved_milestones ?? [];
        $achieved[] = [
            'milestone' => $milestone,
            'achieved_at' => Carbon::now()->toDateString(),
        ];
        $this->update(['achieved_milestones' => $achieved]);
    }

    /**
     * إنشاء تقرير شهري
     */
    public function generateMonthlyReport(): array
    {
        $report = [
            'month' => Carbon::now()->format('Y-m'),
            'generated_at' => Carbon::now()->toDateTimeString(),
            'attendance_rate' => $this->attendance_rate,
            'sessions_completed' => $this->total_sessions_completed,
            'sessions_missed' => $this->total_sessions_missed,
            'overall_grade' => $this->overall_grade,
            'homework_completion_rate' => $this->homework_completion_rate,
            'average_quiz_score' => $this->average_quiz_score,
            'progress_status' => $this->progress_status_in_arabic,
            'engagement_level' => $this->engagement_level_in_arabic,
            'teacher_feedback' => $this->teacher_feedback,
            'strengths' => $this->strengths,
            'areas_for_improvement' => $this->improvement_areas,
            'recommendations' => $this->teacher_recommendations,
        ];

        // Save report to monthly_reports array
        $reports = $this->monthly_reports ?? [];
        $reports[] = $report;
        
        $this->update([
            'monthly_reports' => $reports,
            'last_report_generated' => Carbon::now(),
        ]);

        return $report;
    }

    /**
     * الحصول على ملخص التقدم
     */
    public function getProgressSummaryAttribute(): array
    {
        return [
            'progress_code' => $this->progress_code,
            'student_name' => $this->student->name,
            'teacher_name' => $this->teacher->user->name,
            'subject_name' => $this->subject->name,
            'overall_grade' => $this->overall_grade,
            'attendance_rate' => $this->attendance_rate,
            'homework_completion_rate' => $this->homework_completion_rate,
            'total_sessions' => $this->total_sessions_completed,
            'progress_status' => $this->progress_status_in_arabic,
            'engagement_level' => $this->engagement_level_in_arabic,
            'needs_support' => $this->needs_additional_support,
            'last_session' => $this->last_session_date,
            'next_session' => $this->next_session_date,
        ];
    }
}
