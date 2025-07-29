<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranHomework extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'student_id',
        'subscription_id',
        'circle_id',
        'session_id',
        'homework_code',
        'title',
        'description',
        'homework_type',
        'priority',
        'difficulty_level',
        'estimated_duration_minutes',
        'instructions',
        'requirements',
        'learning_objectives',
        'surah_assignment',
        'verse_from',
        'verse_to',
        'total_verses',
        'memorization_required',
        'recitation_required',
        'tajweed_focus_areas',
        'pronunciation_notes',
        'repetition_count_required',
        'audio_submission_required',
        'video_submission_required',
        'written_submission_required',
        'practice_materials',
        'reference_materials',
        'assigned_at',
        'due_date',
        'reminder_sent_at',
        'submission_method',
        'submission_text',
        'submission_files',
        'audio_recording_url',
        'video_recording_url',
        'submission_notes',
        'submitted_at',
        'submission_status',
        'evaluation_criteria',
        'teacher_feedback',
        'grade',
        'quality_score',
        'accuracy_score',
        'effort_score',
        'improvement_areas',
        'strengths_noted',
        'next_steps',
        'follow_up_required',
        'follow_up_notes',
        'evaluated_at',
        'status',
        'completion_percentage',
        'time_spent_minutes',
        'attempts_count',
        'parent_reviewed',
        'parent_feedback',
        'parent_signature',
        'extension_requested',
        'extension_granted',
        'new_due_date',
        'extension_reason',
        'late_submission',
        'late_penalty_applied',
        'bonus_points',
        'total_score',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'new_due_date' => 'datetime',
        'verse_from' => 'integer',
        'verse_to' => 'integer',
        'total_verses' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'time_spent_minutes' => 'integer',
        'repetition_count_required' => 'integer',
        'attempts_count' => 'integer',
        'grade' => 'decimal:1',
        'quality_score' => 'decimal:1',
        'accuracy_score' => 'decimal:1',
        'effort_score' => 'decimal:1',
        'total_score' => 'decimal:1',
        'completion_percentage' => 'decimal:2',
        'memorization_required' => 'boolean',
        'recitation_required' => 'boolean',
        'audio_submission_required' => 'boolean',
        'video_submission_required' => 'boolean',
        'written_submission_required' => 'boolean',
        'follow_up_required' => 'boolean',
        'parent_reviewed' => 'boolean',
        'extension_requested' => 'boolean',
        'extension_granted' => 'boolean',
        'late_submission' => 'boolean',
        'late_penalty_applied' => 'boolean',
        'requirements' => 'array',
        'learning_objectives' => 'array',
        'tajweed_focus_areas' => 'array',
        'practice_materials' => 'array',
        'reference_materials' => 'array',
        'submission_files' => 'array',
        'evaluation_criteria' => 'array',
        'improvement_areas' => 'array',
        'strengths_noted' => 'array',
        'next_steps' => 'array'
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class);
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeEvaluated($query)
    {
        return $query->where('status', 'evaluated');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['submitted', 'evaluated', 'completed']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueSoon($query, $days = 3)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                    ->whereNotIn('status', ['submitted', 'evaluated', 'completed']);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeByHomeworkType($query, $type)
    {
        return $query->where('homework_type', $type);
    }

    public function scopeMemorization($query)
    {
        return $query->where('homework_type', 'memorization');
    }

    public function scopeRecitation($query)
    {
        return $query->where('homework_type', 'recitation');
    }

    public function scopeReview($query)
    {
        return $query->where('homework_type', 'review');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeNeedsEvaluation($query)
    {
        return $query->where('status', 'submitted')
                    ->whereNull('evaluated_at');
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('follow_up_required', true)
                    ->where('status', 'evaluated');
    }

    public function scopeParentReviewRequired($query)
    {
        return $query->where('parent_reviewed', false)
                    ->where('status', 'evaluated');
    }

    public function scopeHighScoring($query, $minScore = 8.0)
    {
        return $query->where('total_score', '>=', $minScore);
    }

    // Accessors
    public function getHomeworkTypeTextAttribute(): string
    {
        $types = [
            'memorization' => 'حفظ آيات جديدة',
            'recitation' => 'تلاوة وتجويد',
            'review' => 'مراجعة ما تم حفظه',
            'research' => 'بحث وتفسير',
            'writing' => 'كتابة وإملاء',
            'listening' => 'استماع وتدبر',
            'practice' => 'تطبيق عملي'
        ];

        return $types[$this->homework_type] ?? $this->homework_type;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'assigned' => 'مُكلف',
            'in_progress' => 'قيد التنفيذ',
            'submitted' => 'تم التسليم',
            'evaluated' => 'تم التقييم',
            'completed' => 'مكتمل',
            'overdue' => 'متأخر',
            'cancelled' => 'ملغي'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getSubmissionStatusTextAttribute(): string
    {
        $statuses = [
            'not_submitted' => 'لم يتم التسليم',
            'partial' => 'تسليم جزئي',
            'complete' => 'تسليم كامل',
            'late' => 'تسليم متأخر',
            'resubmission' => 'إعادة تسليم'
        ];

        return $statuses[$this->submission_status] ?? $this->submission_status;
    }

    public function getPriorityTextAttribute(): string
    {
        $priorities = [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'urgent' => 'عاجلة'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    public function getDifficultyLevelTextAttribute(): string
    {
        $levels = [
            'very_easy' => 'سهل جداً',
            'easy' => 'سهل',
            'medium' => 'متوسط',
            'hard' => 'صعب',
            'very_hard' => 'صعب جداً'
        ];

        return $levels[$this->difficulty_level] ?? $this->difficulty_level;
    }

    public function getSurahAssignmentNameAttribute(): string
    {
        return $this->getSurahName($this->surah_assignment);
    }

    public function getVersesRangeTextAttribute(): string
    {
        if (!$this->verse_from || !$this->verse_to) {
            return 'غير محدد';
        }

        if ($this->verse_from === $this->verse_to) {
            return "آية {$this->verse_from}";
        }

        return "من آية {$this->verse_from} إلى آية {$this->verse_to}";
    }

    public function getAssignmentSummaryAttribute(): string
    {
        $surahName = $this->surah_assignment_name;
        $versesRange = $this->verses_range_text;
        
        return "{$this->homework_type_text} - سورة {$surahName} ({$versesRange})";
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['submitted', 'evaluated', 'completed']);
    }

    public function getIsDueTodayAttribute(): bool
    {
        return $this->due_date && $this->due_date->isToday();
    }

    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->due_date) {
            return 999; // No deadline
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'evaluated', 'completed']);
    }

    public function getIsEvaluatedAttribute(): bool
    {
        return in_array($this->status, ['evaluated', 'completed']);
    }

    public function getCanSubmitAttribute(): bool
    {
        return $this->status === 'assigned' || $this->status === 'in_progress';
    }

    public function getCanEvaluateAttribute(): bool
    {
        return $this->status === 'submitted';
    }

    public function getNeedsAttentionAttribute(): bool
    {
        return $this->is_overdue || 
               ($this->status === 'submitted' && now()->diffInDays($this->submitted_at) > 2) ||
               $this->follow_up_required;
    }

    public function getSubmissionMethodTextAttribute(): string
    {
        $methods = [
            'audio' => 'تسجيل صوتي',
            'video' => 'تسجيل مرئي',
            'text' => 'نص مكتوب',
            'file' => 'ملف مرفق',
            'live' => 'مباشر مع المعلم',
            'mixed' => 'مختلط'
        ];

        return $methods[$this->submission_method] ?? $this->submission_method;
    }

    public function getGradeTextAttribute(): string
    {
        if (!$this->grade) {
            return 'غير مقيم';
        }

        if ($this->grade >= 9.0) return 'ممتاز';
        if ($this->grade >= 8.0) return 'جيد جداً';
        if ($this->grade >= 7.0) return 'جيد';
        if ($this->grade >= 6.0) return 'مقبول';
        return 'يحتاج تحسين';
    }

    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('Y-m-d H:i') : 'غير محدد';
    }

    public function getTimeSpentFormattedAttribute(): string
    {
        $minutes = $this->time_spent_minutes ?? 0;
        
        if ($minutes < 60) {
            return $minutes . ' دقيقة';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return $hours . ' ساعة';
        }
        
        return $hours . ' ساعة و ' . $remainingMinutes . ' دقيقة';
    }

    // Methods
    public function submit(array $submissionData): self
    {
        if (!$this->can_submit) {
            throw new \Exception('لا يمكن تسليم الواجب في الحالة الحالية');
        }

        $updateData = array_merge([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submission_status' => 'complete',
            'late_submission' => $this->is_overdue
        ], $submissionData);

        $this->update($updateData);

        return $this;
    }

    public function evaluate(array $evaluationData): self
    {
        if (!$this->can_evaluate) {
            throw new \Exception('لا يمكن تقييم الواجب في الحالة الحالية');
        }

        // Calculate total score from individual scores
        $totalScore = $this->calculateTotalScore(
            $evaluationData['quality_score'] ?? 0,
            $evaluationData['accuracy_score'] ?? 0,
            $evaluationData['effort_score'] ?? 0
        );

        $updateData = array_merge([
            'status' => 'evaluated',
            'evaluated_at' => now(),
            'total_score' => $totalScore,
            'grade' => $totalScore
        ], $evaluationData);

        $this->update($updateData);

        // Update student progress if this is memorization homework
        if ($this->homework_type === 'memorization' && $this->total_verses > 0) {
            $this->recordProgressFromHomework();
        }

        return $this;
    }

    public function requestExtension(string $reason, \Carbon\Carbon $requestedDate): self
    {
        $this->update([
            'extension_requested' => true,
            'extension_reason' => $reason,
            'new_due_date' => $requestedDate
        ]);

        return $this;
    }

    public function grantExtension(\Carbon\Carbon $newDueDate, string $approvalNote = null): self
    {
        $this->update([
            'extension_granted' => true,
            'due_date' => $newDueDate,
            'new_due_date' => null,
            'teacher_feedback' => $this->teacher_feedback . "\n\nتم منح تمديد حتى: " . $newDueDate->format('Y-m-d') . 
                                  ($approvalNote ? "\nملاحظة: " . $approvalNote : '')
        ]);

        return $this;
    }

    public function markAsInProgress(): self
    {
        if ($this->status === 'assigned') {
            $this->update(['status' => 'in_progress']);
        }

        return $this;
    }

    public function cancel(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'teacher_feedback' => $this->teacher_feedback . "\n\nتم إلغاء الواجب: " . ($reason ?? 'بدون سبب محدد')
        ]);

        return $this;
    }

    public function sendReminder(): self
    {
        $this->update(['reminder_sent_at' => now()]);
        
        // Here you would typically send actual notification/email
        // NotificationService::sendHomeworkReminder($this);

        return $this;
    }

    public function recordTimeSpent(int $minutes): self
    {
        $this->increment('time_spent_minutes', $minutes);
        
        return $this;
    }

    public function addAttempt(): self
    {
        $this->increment('attempts_count');
        
        return $this;
    }

    public function updateProgress(float $percentage): self
    {
        $this->update(['completion_percentage' => min(100, max(0, $percentage))]);
        
        return $this;
    }

    public function addParentFeedback(string $feedback, bool $parentSignature = false): self
    {
        $this->update([
            'parent_feedback' => $feedback,
            'parent_reviewed' => true,
            'parent_signature' => $parentSignature
        ]);

        return $this;
    }

    private function calculateTotalScore(float $qualityScore, float $accuracyScore, float $effortScore): float
    {
        // Weighted average: Quality 40%, Accuracy 40%, Effort 20%
        $total = ($qualityScore * 0.4) + ($accuracyScore * 0.4) + ($effortScore * 0.2);
        
        // Apply late penalty if applicable
        if ($this->late_submission && $this->late_penalty_applied) {
            $total = max(0, $total - 1.0); // Deduct 1 point for late submission
        }

        // Add bonus points if any
        if ($this->bonus_points) {
            $total = min(10, $total + $this->bonus_points);
        }

        return round($total, 1);
    }

    private function recordProgressFromHomework(): void
    {
        QuranProgress::createProgress([
            'academy_id' => $this->academy_id,
            'student_id' => $this->student_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'quran_subscription_id' => $this->subscription_id,
            'circle_id' => $this->circle_id,
            'progress_type' => 'homework',
            'current_surah' => $this->surah_assignment,
            'current_verse' => $this->verse_to,
            'verses_memorized' => $this->total_verses,
            'recitation_quality' => $this->quality_score,
            'tajweed_accuracy' => $this->accuracy_score,
            'overall_rating' => intval($this->total_score),
            'teacher_notes' => "واجب مكتمل: {$this->title}",
            'progress_status' => $this->total_score >= 8.0 ? 'excellent' : 'on_track'
        ]);
    }

    private function getSurahName(int $surahNumber): string
    {
        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
            13 => 'الرعد', 14 => 'إبراهيم', 15 => 'الحجر', 16 => 'النحل',
            17 => 'الإسراء', 18 => 'الكهف', 19 => 'مريم', 20 => 'طه',
            // Add all 114 surahs as needed
        ];

        return $surahNames[$surahNumber] ?? "سورة رقم {$surahNumber}";
    }

    // Static methods
    public static function createHomework(array $data): self
    {
        return self::create(array_merge($data, [
            'homework_code' => self::generateHomeworkCode($data['academy_id']),
            'assigned_at' => now(),
            'status' => 'assigned',
            'attempts_count' => 0,
            'completion_percentage' => 0,
            'time_spent_minutes' => 0,
            'parent_reviewed' => false
        ]));
    }

    private static function generateHomeworkCode(int $academyId): string
    {
        $count = self::where('academy_id', $academyId)->count() + 1;
        return 'QH-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public static function getStudentHomework(int $studentId, int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('student_id', $studentId)
            ->where('academy_id', $academyId)
            ->with(['quranTeacher.user', 'session']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['homework_type'])) {
            $query->where('homework_type', $filters['homework_type']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderBy('due_date', 'asc')
                    ->orderBy('priority', 'desc')
                    ->get();
    }

    public static function getTeacherHomework(int $teacherId, int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('quran_teacher_id', $teacherId)
            ->where('academy_id', $academyId)
            ->with(['student', 'session']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['needs_evaluation']) && $filters['needs_evaluation']) {
            $query->needsEvaluation();
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        return $query->orderBy('due_date', 'asc')
                    ->orderBy('submitted_at', 'asc')
                    ->get();
    }

    public static function getOverdueHomework(int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->overdue()
            ->with(['student', 'quranTeacher.user'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public static function getUpcomingDeadlines(int $academyId, int $days = 3): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->dueSoon($days)
            ->with(['student', 'quranTeacher.user'])
            ->orderBy('due_date', 'asc')
            ->get();
    }
} 