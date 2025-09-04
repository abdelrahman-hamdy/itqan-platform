<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'quran_teacher_id',
        'quran_subscription_id',
        'circle_id',
        'session_id',
        'progress_code',
        'progress_date',
        'progress_type',
        'current_surah',
        'current_verse',
        'target_surah',
        'target_verse',
        'verses_memorized',
        'verses_reviewed',
        'verses_perfect',
        'verses_need_work',
        'total_verses_memorized',
        'total_pages_memorized',
        'total_surahs_completed',
        'memorization_percentage',
        'recitation_quality',
        'tajweed_accuracy',
        'fluency_level',
        'confidence_level',
        'retention_rate',
        'common_mistakes',
        'improvement_areas',
        'strengths',
        'weekly_goal',
        'monthly_goal',
        'goal_progress',
        'difficulty_level',
        'study_hours_this_week',
        'average_daily_study',
        'last_review_date',
        'next_review_date',
        'repetition_count',
        'mastery_level',
        'certificate_eligible',
        'milestones_achieved',
        'performance_trends',
        'learning_pace',
        'consistency_score',
        'attendance_impact',
        'homework_completion_rate',
        'quiz_average_score',
        'parent_involvement_level',
        'motivation_level',
        'challenges_faced',
        'support_needed',
        'recommendations',
        'next_steps',

        'parent_notes',
        'student_feedback',
        'assessment_date',
        'overall_rating',
        'progress_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'progress_date' => 'date',
        'assessment_date' => 'date',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'current_surah' => 'integer',
        'current_verse' => 'integer',
        'target_surah' => 'integer',
        'target_verse' => 'integer',
        'verses_memorized' => 'integer',
        'verses_reviewed' => 'integer',
        'verses_perfect' => 'integer',
        'verses_need_work' => 'integer',
        'total_verses_memorized' => 'integer',
        'total_pages_memorized' => 'integer',
        'total_surahs_completed' => 'integer',
        'repetition_count' => 'integer',
        'study_hours_this_week' => 'decimal:2',
        'average_daily_study' => 'decimal:2',
        'memorization_percentage' => 'decimal:2',
        'recitation_quality' => 'decimal:1',
        'tajweed_accuracy' => 'decimal:1',
        'fluency_level' => 'decimal:1',
        'confidence_level' => 'decimal:1',
        'retention_rate' => 'decimal:1',
        'goal_progress' => 'decimal:2',
        'consistency_score' => 'decimal:1',
        'homework_completion_rate' => 'decimal:1',
        'quiz_average_score' => 'decimal:1',
        'motivation_level' => 'decimal:1',
        'overall_rating' => 'integer',
        'certificate_eligible' => 'boolean',
        'common_mistakes' => 'array',
        'improvement_areas' => 'array',
        'strengths' => 'array',
        'milestones_achieved' => 'array',
        'performance_trends' => 'array',
        'challenges_faced' => 'array',
        'support_needed' => 'array',
        'recommendations' => 'array',
        'next_steps' => 'array',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'quran_subscription_id');
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
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeBySubscription($query, $subscriptionId)
    {
        return $query->where('quran_subscription_id', $subscriptionId);
    }

    public function scopeByCircle($query, $circleId)
    {
        return $query->where('circle_id', $circleId);
    }

    public function scopeByProgressType($query, $type)
    {
        return $query->where('progress_type', $type);
    }

    public function scopeMemorization($query)
    {
        return $query->where('progress_type', 'memorization');
    }

    public function scopeRecitation($query)
    {
        return $query->where('progress_type', 'recitation');
    }

    public function scopeReview($query)
    {
        return $query->where('progress_type', 'review');
    }

    public function scopeAssessment($query)
    {
        return $query->where('progress_type', 'assessment');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('progress_date', '>=', now()->subDays($days));
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('progress_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('progress_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeHighPerformance($query, $minRating = 8.0)
    {
        return $query->where('recitation_quality', '>=', $minRating)
            ->where('tajweed_accuracy', '>=', $minRating);
    }

    public function scopeNeedsImprovement($query, $maxRating = 6.0)
    {
        return $query->where(function ($q) use ($maxRating) {
            $q->where('recitation_quality', '<=', $maxRating)
                ->orWhere('tajweed_accuracy', '<=', $maxRating);
        });
    }

    public function scopeCertificateEligible($query)
    {
        return $query->where('certificate_eligible', true);
    }

    public function scopeByMasteryLevel($query, $level)
    {
        return $query->where('mastery_level', $level);
    }

    public function scopeByProgressStatus($query, $status)
    {
        return $query->where('progress_status', $status);
    }

    // Accessors
    public function getProgressTypeTextAttribute(): string
    {
        $types = [
            'memorization' => 'حفظ جديد',
            'recitation' => 'تلاوة وتجويد',
            'review' => 'مراجعة',
            'assessment' => 'تقييم',
            'test' => 'اختبار',
            'milestone' => 'إنجاز مهم',
        ];

        return $types[$this->progress_type] ?? $this->progress_type;
    }

    public function getProgressStatusTextAttribute(): string
    {
        $statuses = [
            'on_track' => 'على المسار الصحيح',
            'ahead' => 'متقدم عن الخطة',
            'behind' => 'متأخر عن الخطة',
            'needs_attention' => 'يحتاج اهتمام',
            'excellent' => 'ممتاز',
            'struggling' => 'يواجه صعوبات',
        ];

        return $statuses[$this->progress_status] ?? $this->progress_status;
    }

    public function getMasteryLevelTextAttribute(): string
    {
        $levels = [
            'beginner' => 'مبتدئ',
            'developing' => 'في طور التطوير',
            'proficient' => 'متقن',
            'advanced' => 'متقدم',
            'expert' => 'خبير',
            'master' => 'متمكن',
        ];

        return $levels[$this->mastery_level] ?? $this->mastery_level;
    }

    public function getDifficultyLevelTextAttribute(): string
    {
        $levels = [
            'very_easy' => 'سهل جداً',
            'easy' => 'سهل',
            'moderate' => 'متوسط',
            'challenging' => 'صعب',
            'very_challenging' => 'صعب جداً',
        ];

        return $levels[$this->difficulty_level] ?? $this->difficulty_level;
    }

    public function getLearningPaceTextAttribute(): string
    {
        $paces = [
            'very_slow' => 'بطيء جداً',
            'slow' => 'بطيء',
            'normal' => 'طبيعي',
            'fast' => 'سريع',
            'very_fast' => 'سريع جداً',
        ];

        return $paces[$this->learning_pace] ?? $this->learning_pace;
    }

    public function getCurrentSurahNameAttribute(): string
    {
        return $this->getSurahName($this->current_surah);
    }

    public function getTargetSurahNameAttribute(): string
    {
        return $this->getSurahName($this->target_surah);
    }

    public function getProgressSummaryAttribute(): string
    {
        if (! $this->current_surah || ! $this->current_verse) {
            return 'لم يتم تحديد التقدم';
        }

        $surahName = $this->current_surah_name;
        $versesCount = $this->verses_memorized ?? 0;

        return "سورة {$surahName} - آية {$this->current_verse} ({$versesCount} آيات محفوظة)";
    }

    public function getPerformanceGradeAttribute(): string
    {
        $averageScore = ($this->recitation_quality + $this->tajweed_accuracy) / 2;

        if ($averageScore >= 9.0) {
            return 'ممتاز';
        }
        if ($averageScore >= 8.0) {
            return 'جيد جداً';
        }
        if ($averageScore >= 7.0) {
            return 'جيد';
        }
        if ($averageScore >= 6.0) {
            return 'مقبول';
        }

        return 'يحتاج تحسين';
    }

    public function getWeeklyGoalProgressAttribute(): float
    {
        if (! $this->weekly_goal) {
            return 0;
        }

        // Calculate current week progress
        $weeklyProgress = self::where('student_id', $this->student_id)
            ->whereBetween('progress_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('verses_memorized');

        return min(100, ($weeklyProgress / $this->weekly_goal) * 100);
    }

    public function getMonthlyGoalProgressAttribute(): float
    {
        if (! $this->monthly_goal) {
            return 0;
        }

        // Calculate current month progress
        $monthlyProgress = self::where('student_id', $this->student_id)
            ->whereBetween('progress_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('verses_memorized');

        return min(100, ($monthlyProgress / $this->monthly_goal) * 100);
    }

    public function getConsistencyLevelAttribute(): string
    {
        $score = $this->consistency_score ?? 0;

        if ($score >= 9.0) {
            return 'ممتاز';
        }
        if ($score >= 8.0) {
            return 'جيد جداً';
        }
        if ($score >= 7.0) {
            return 'جيد';
        }
        if ($score >= 6.0) {
            return 'مقبول';
        }

        return 'يحتاج تحسين';
    }

    public function getMotivationLevelTextAttribute(): string
    {
        $level = $this->motivation_level ?? 0;

        if ($level >= 9.0) {
            return 'متحمس جداً';
        }
        if ($level >= 8.0) {
            return 'متحمس';
        }
        if ($level >= 7.0) {
            return 'جيد';
        }
        if ($level >= 6.0) {
            return 'متوسط';
        }

        return 'يحتاج تحفيز';
    }

    public function getStudyHabitQualityAttribute(): string
    {
        $dailyAverage = $this->average_daily_study ?? 0;

        if ($dailyAverage >= 2.0) {
            return 'ممتاز';
        }
        if ($dailyAverage >= 1.5) {
            return 'جيد جداً';
        }
        if ($dailyAverage >= 1.0) {
            return 'جيد';
        }
        if ($dailyAverage >= 0.5) {
            return 'مقبول';
        }

        return 'يحتاج تحسين';
    }

    public function getRetentionQualityAttribute(): string
    {
        $rate = $this->retention_rate ?? 0;

        if ($rate >= 95) {
            return 'ممتاز';
        }
        if ($rate >= 85) {
            return 'جيد جداً';
        }
        if ($rate >= 75) {
            return 'جيد';
        }
        if ($rate >= 65) {
            return 'مقبول';
        }

        return 'يحتاج تحسين';
    }

    // Methods
    public function updateProgress(array $progressData): self
    {
        $this->update($progressData);

        // Recalculate totals and percentages
        $this->recalculateStats();

        return $this;
    }

    public function recalculateStats(): self
    {
        // Calculate total progress across all records for this student
        $studentProgress = self::where('student_id', $this->student_id)
            ->where('academy_id', $this->academy_id);

        $totalVersesMemorized = $studentProgress->sum('verses_memorized');
        $averageQuality = $studentProgress->avg('recitation_quality');
        $averageAccuracy = $studentProgress->avg('tajweed_accuracy');
        $totalSessions = $studentProgress->distinct('session_id')->count();

        // Update subscription progress if available
        if ($this->subscription) {
            $this->subscription->updateProgress(
                $this->current_verse,
                $totalVersesMemorized,
                $this->memorization_percentage
            );
        }

        // Update this record with calculated stats
        $this->update([
            'total_verses_memorized' => $totalVersesMemorized,
            'total_pages_memorized' => intval($totalVersesMemorized / 15), // Approximate verses per page
            'recitation_quality' => $averageQuality,
            'tajweed_accuracy' => $averageAccuracy,
        ]);

        return $this;
    }

    public function addMilestone(string $milestone, array $details = []): self
    {
        $milestones = $this->milestones_achieved ?? [];
        $milestones[] = [
            'milestone' => $milestone,
            'achieved_at' => now(),
            'details' => $details,
        ];

        $this->update(['milestones_achieved' => $milestones]);

        return $this;
    }

    public function setGoals(?int $weeklyGoal = null, ?int $monthlyGoal = null): self
    {
        $updateData = [];

        if ($weeklyGoal !== null) {
            $updateData['weekly_goal'] = $weeklyGoal;
        }

        if ($monthlyGoal !== null) {
            $updateData['monthly_goal'] = $monthlyGoal;
        }

        if (! empty($updateData)) {
            $this->update($updateData);
        }

        return $this;
    }

    public function recordStudySession(float $hours, array $details = []): self
    {
        $this->update([
            'study_hours_this_week' => $this->study_hours_this_week + $hours,
            'average_daily_study' => $this->calculateDailyAverage(),
            'last_review_date' => now(),
        ]);

        return $this;
    }

    public function assessPerformance(): array
    {
        $assessment = [
            'overall_grade' => $this->performance_grade,
            'consistency' => $this->consistency_level,
            'motivation' => $this->motivation_level_text,
            'study_habits' => $this->study_habit_quality,
            'retention' => $this->retention_quality,
            'areas_of_strength' => $this->strengths ?? [],
            'areas_for_improvement' => $this->improvement_areas ?? [],
            'recommendations' => $this->generateRecommendations(),
        ];

        return $assessment;
    }

    public function generateRecommendations(): array
    {
        $recommendations = [];

        // Based on performance scores
        if ($this->recitation_quality < 7.0) {
            $recommendations[] = 'التركيز على تحسين جودة التلاوة من خلال الممارسة اليومية';
        }

        if ($this->tajweed_accuracy < 7.0) {
            $recommendations[] = 'مراجعة قواعد التجويد مع المعلم والممارسة المكثفة';
        }

        if ($this->consistency_score < 7.0) {
            $recommendations[] = 'وضع جدول ثابت للمراجعة اليومية والالتزام به';
        }

        if ($this->retention_rate < 80) {
            $recommendations[] = 'زيادة عدد المراجعات وتكرار الآيات المحفوظة';
        }

        if ($this->average_daily_study < 1.0) {
            $recommendations[] = 'زيادة وقت الدراسة اليومي تدريجياً';
        }

        if ($this->motivation_level < 7.0) {
            $recommendations[] = 'البحث عن طرق تحفيزية جديدة ووضع أهداف قصيرة المدى';
        }

        return $recommendations;
    }

    private function calculateDailyAverage(): float
    {
        $daysInWeek = 7;

        return $this->study_hours_this_week / $daysInWeek;
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
    public static function createProgress(array $data): self
    {
        return self::create(array_merge($data, [
            'progress_code' => self::generateProgressCode($data['academy_id']),
            'progress_date' => $data['progress_date'] ?? now(),
            'certificate_eligible' => false,
        ]));
    }

    private static function generateProgressCode(int $academyId): string
    {
        $count = self::where('academy_id', $academyId)->count() + 1;

        return 'QP-'.$academyId.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public static function getStudentSummary(int $studentId, int $academyId): array
    {
        $progress = self::where('student_id', $studentId)
            ->where('academy_id', $academyId)
            ->orderBy('progress_date', 'desc')
            ->first();

        if (! $progress) {
            return [
                'total_verses' => 0,
                'total_pages' => 0,
                'total_surahs' => 0,
                'current_surah' => null,
                'memorization_percentage' => 0,
                'performance_grade' => 'غير متوفر',
                'last_update' => null,
            ];
        }

        return [
            'total_verses' => $progress->total_verses_memorized,
            'total_pages' => $progress->total_pages_memorized,
            'total_surahs' => $progress->total_surahs_completed,
            'current_surah' => $progress->current_surah_name,
            'current_verse' => $progress->current_verse,
            'memorization_percentage' => $progress->memorization_percentage,
            'performance_grade' => $progress->performance_grade,
            'recitation_quality' => $progress->recitation_quality,
            'tajweed_accuracy' => $progress->tajweed_accuracy,
            'consistency_score' => $progress->consistency_score,
            'last_update' => $progress->progress_date,
        ];
    }

    public static function getTeacherStudentsProgress(int $teacherId, int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('quran_teacher_id', $teacherId)
            ->where('academy_id', $academyId)
            ->with(['student', 'subscription'])
            ->orderBy('progress_date', 'desc')
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentProgress) {
                return $studentProgress->first(); // Get latest progress for each student
            })
            ->values();
    }

    public static function getProgressTrends(int $studentId, int $academyId, int $days = 30): array
    {
        $progressRecords = self::where('student_id', $studentId)
            ->where('academy_id', $academyId)
            ->where('progress_date', '>=', now()->subDays($days))
            ->orderBy('progress_date', 'asc')
            ->get();

        $trends = [
            'verses_trend' => [],
            'quality_trend' => [],
            'accuracy_trend' => [],
            'consistency_trend' => [],
        ];

        foreach ($progressRecords as $progress) {
            $date = $progress->progress_date->format('Y-m-d');
            $trends['verses_trend'][$date] = $progress->verses_memorized;
            $trends['quality_trend'][$date] = $progress->recitation_quality;
            $trends['accuracy_trend'][$date] = $progress->tajweed_accuracy;
            $trends['consistency_trend'][$date] = $progress->consistency_score;
        }

        return $trends;
    }
}
