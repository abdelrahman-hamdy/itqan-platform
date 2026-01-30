<?php

namespace App\Models;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\WeekDays;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * QuranCircle Model
 *
 * Represents a Quran memorization circle (group study session).
 *
 * DECOUPLED ARCHITECTURE:
 * - This model exists independently from subscriptions
 * - Students are enrolled via QuranCircleEnrollment (enrollments relationship)
 * - Each enrollment can optionally link to a QuranSubscription
 * - Subscriptions link to circles via polymorphic education_unit relationship
 * - Deleting a subscription does NOT delete the circle or unenroll students
 *
 * @property int $id
 * @property int $academy_id
 * @property int|null $quran_teacher_id
 * @property string $circle_code
 * @property string|null $name
 * @property string|null $description
 * @property string|null $specialization
 * @property string|null $memorization_level
 * @property string|null $age_group
 * @property string|null $gender_type
 * @property int $max_students
 * @property int $enrolled_students
 * @property int|null $min_students_to_start
 * @property int|null $monthly_sessions_count
 * @property float|null $monthly_fee
 * @property int|null $sessions_completed
 * @property bool $status
 * @property string|null $enrollment_status
 * @property array|null $learning_objectives
 * @property \Carbon\Carbon|null $last_session_at
 * @property \Carbon\Carbon|null $next_session_at
 * @property bool $recording_enabled
 * @property bool $attendance_required
 * @property bool $makeup_sessions_allowed
 * @property bool $certificates_enabled
 * @property string|null $schedule_time
 * @property array|null $schedule_days
 * @property string|null $supervisor_notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class QuranCircle extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'circle_code',
        'name',
        'description',
        'specialization',
        'memorization_level',
        'age_group',
        'gender_type',
        'max_students',
        'enrolled_students',
        'min_students_to_start',
        'monthly_sessions_count',
        'monthly_fee',
        'sessions_completed',
        // Homework-based progress tracking (lifetime totals for the circle)
        'total_memorized_pages',
        'total_reviewed_pages',
        'total_reviewed_surahs',
        'status',
        'enrollment_status',
        'learning_objectives',
        'last_session_at',
        'next_session_at',
        'recording_enabled',
        'attendance_required',
        'makeup_sessions_allowed',
        'certificates_enabled',
        'schedule_time',
        'schedule_days',
        'supervisor_notes',
        'admin_notes',
    ];

    protected $casts = [
        'learning_objectives' => 'array',
        'enrollment_status' => CircleEnrollmentStatus::class,
        'max_students' => 'integer',
        'enrolled_students' => 'integer',
        'min_students_to_start' => 'integer',
        'sessions_completed' => 'integer',
        // Homework-based progress tracking
        'total_memorized_pages' => 'integer',
        'total_reviewed_pages' => 'integer',
        'total_reviewed_surahs' => 'integer',
        'monthly_fee' => 'decimal:2',
        'status' => 'boolean',
        'recording_enabled' => 'boolean',
        'attendance_required' => 'boolean',
        'makeup_sessions_allowed' => 'boolean',
        'certificates_enabled' => 'boolean',
        'last_session_at' => 'datetime',
        'next_session_at' => 'datetime',
        'schedule_days' => 'array',
    ];

    // Constants - Standardized across individual and group circles
    const SPECIALIZATIONS = [
        'memorization' => 'حفظ',
        'recitation' => 'تلاوة',
        'interpretation' => 'تفسير',
        'tajweed' => 'تجويد',
        'complete' => 'شامل',
    ];

    const MEMORIZATION_LEVELS = [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    // Note: Teacher monthly salary is now calculated from QuranTeacherProfile
    // session_price_group and session_price_individual fields, not stored per circle

    const AGE_GROUPS = [
        'children' => 'أطفال',
        'youth' => 'شباب',
        'adults' => 'كبار',
        'all_ages' => 'كل الفئات',
    ];

    const GENDER_TYPES = [
        'male' => 'رجال',
        'female' => 'نساء',
        'mixed' => 'مختلط',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the teacher user for this circle
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    /**
     * Get the Quran teacher profile for this circle
     * Uses user_id as the foreign key match since quran_teacher_id stores user IDs
     */
    public function quranTeacherProfile(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class, 'quran_teacher_id', 'user_id');
    }

    /**
     * Alias for teacher relationship for consistency with other models
     */
    public function quranTeacher(): BelongsTo
    {
        return $this->teacher();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'quran_circle_students', 'circle_id', 'student_id')
            ->withPivot([
                'enrolled_at',
                'status',
                'attendance_count',
                'missed_sessions',
                'makeup_sessions_used',
                'current_level',
                'progress_notes',
                'parent_rating',
                'student_rating',
                'completion_date',
                'certificate_issued',
            ])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'circle_id');
    }

    public function schedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuranCircleSchedule::class, 'circle_id');
    }

    // Note: homework() relationship removed - Quran homework is now tracked through
    // QuranSession model fields and graded through student session reports
    // See migration: 2025_11_17_190605_drop_quran_homework_tables.php

    // Note: progress() relationship removed - Progress is now calculated
    // dynamically from session reports using the QuranReportService
    // See migration: 2025_11_23_drop_progress_tables.php

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'circle_id');
    }

    public function quizAssignments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    /**
     * Get all Quran subscriptions for this circle (group subscriptions)
     *
     * @deprecated Use linkedSubscriptions() for new polymorphic relationship
     */
    public function quranSubscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'quran_circle_id');
    }

    /**
     * Get all subscriptions that link to this circle via polymorphic relationship
     * (New decoupled architecture)
     */
    public function linkedSubscriptions(): MorphMany
    {
        return $this->morphMany(QuranSubscription::class, 'education_unit');
    }

    /**
     * Get the active subscription for this circle (if any)
     * Checks the polymorphic linked subscriptions first, then falls back to legacy
     *
     * Note: For group circles, there may be multiple active subscriptions (one per student)
     * This method returns the first active one found.
     */
    public function getActiveSubscriptionAttribute(): ?QuranSubscription
    {
        // Check new polymorphic linked subscriptions
        $activeLinked = $this->linkedSubscriptions()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->first();

        if ($activeLinked) {
            return $activeLinked;
        }

        // Fallback to legacy direct relationship
        return $this->quranSubscriptions()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->first();
    }

    /**
     * Get all active subscriptions for this circle
     * (For group circles, there can be multiple - one per enrolled student)
     */
    public function getActiveSubscriptionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        // Get from polymorphic relationship
        $linkedActive = $this->linkedSubscriptions()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->get();

        if ($linkedActive->isNotEmpty()) {
            return $linkedActive;
        }

        // Fallback to legacy
        return $this->quranSubscriptions()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->get();
    }

    /**
     * Check if this circle has any active subscriptions
     */
    public function hasActiveSubscriptions(): bool
    {
        return $this->linkedSubscriptions()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->exists()
            || $this->quranSubscriptions()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->exists();
    }

    /**
     * Get enrollments for this circle (new model-based approach)
     * This provides better subscription tracking than the pivot table
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(QuranCircleEnrollment::class, 'circle_id');
    }

    /**
     * Get active enrollments (students currently enrolled)
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', QuranCircleEnrollment::STATUS_ENROLLED);
    }

    /**
     * Get enrollments with active subscriptions
     */
    public function paidEnrollments(): HasMany
    {
        return $this->enrollments()
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->whereHas('subscription', function ($q) {
                $q->where('status', SessionSubscriptionStatus::ACTIVE);
            });
    }

    /**
     * Get independent enrollments (no subscription linked)
     */
    public function independentEnrollments(): HasMany
    {
        return $this->enrollments()
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->whereNull('subscription_id');
    }

    /**
     * Check if a student is enrolled in this circle
     */
    public function hasStudent(User $student): bool
    {
        return $this->enrollments()
            ->where('student_id', $student->id)
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->exists();
    }

    /**
     * Get a student's enrollment in this circle
     */
    public function getEnrollmentFor(User $student): ?QuranCircleEnrollment
    {
        return $this->enrollments()
            ->where('student_id', $student->id)
            ->first();
    }

    /**
     * Get active enrollment count using the new model
     */
    public function getActiveEnrollmentCountAttribute(): int
    {
        return $this->activeEnrollments()->count();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true)
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', true);
    }

    public function scopeOpenForEnrollment($query)
    {
        return $query->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->where('status', true)
            ->where(function ($q) {
                $q->whereColumn('max_students', '>', function ($subQuery) {
                    $subQuery->selectRaw('COUNT(*)')
                        ->from('quran_circle_students')
                        ->whereColumn('quran_circle_students.circle_id', 'quran_circles.id');
                });
            });
    }

    public function scopeStartingSoon($query, $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('start_date', [now(), now()->addDays($days)]);
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeByGradeLevel($query, $grade)
    {
        return $query->whereJsonContains('grade_levels', $grade);
    }

    public function scopeByAgeRange($query, $age)
    {
        return $query->where('age_range_min', '<=', $age)
            ->where('age_range_max', '>=', $age);
    }

    public function scopeByDay($query, $day)
    {
        return $query->whereJsonContains('schedule_days', $day);
    }

    public function scopeWithAvailableSpots($query)
    {
        return $query->where(function ($q) {
            $q->whereColumn('max_students', '>', function ($subQuery) {
                $subQuery->selectRaw('COUNT(*)')
                    ->from('quran_circle_students')
                    ->whereColumn('quran_circle_students.circle_id', 'quran_circles.id');
            });
        });
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('avg_rating', '>=', $minRating);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        if (is_bool($this->status) || is_numeric($this->status)) {
            return $this->status
                ? __('enums.circle_active_status.active')
                : __('enums.circle_active_status.inactive');
        }

        // For non-boolean status values, use circle_status translations
        return __('enums.circle_status.'.$this->status)
            ?? ($this->status ? __('enums.circle_active_status.active') : __('enums.circle_active_status.inactive'));
    }

    public function getEnrollmentStatusTextAttribute(): string
    {
        if ($this->enrollment_status instanceof CircleEnrollmentStatus) {
            return $this->enrollment_status->label();
        }

        // Fallback for legacy string values
        return __('enums.circle_enrollment_status.'.$this->enrollment_status)
            ?? (string) $this->enrollment_status;
    }

    public function getSpecializationTextAttribute(): string
    {
        $specializations = [
            'memorization' => 'حفظ القرآن',
            'recitation' => 'تلاوة وتجويد',
            'interpretation' => 'تفسير',
            'arabic_language' => 'اللغة العربية',
            'complete' => 'شامل',
        ];

        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getMemorizationLevelTextAttribute(): string
    {
        try {
            $level = DifficultyLevel::from($this->memorization_level);

            return $level->label();
        } catch (\ValueError $e) {
            // Fallback for any legacy values
            $levels = [
                'beginner' => 'مبتدئ',
                'intermediate' => 'متوسط',
                'advanced' => 'متقدم',
            ];

            return $levels[$this->memorization_level] ?? $this->memorization_level;
        }
    }

    public function getFormattedMonthlyFeeAttribute(): string
    {
        return number_format((float) $this->monthly_fee, 2).' ريال';
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_students - $this->students()->count());
    }

    public function getIsFullAttribute(): bool
    {
        return $this->students()->count() >= $this->max_students;
    }

    public function getCanStartAttribute(): bool
    {
        return $this->students()->count() >= ($this->min_students_to_start ?? 3);
    }

    public function getScheduleTextAttribute(): string
    {
        if (! $this->schedule || ! $this->schedule->weekly_schedule) {
            return 'لم يتم تحديد الجدول بعد';
        }

        $days = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        $scheduleText = collect($this->schedule->weekly_schedule)
            ->map(function ($item) use ($days) {
                $day = $days[strtolower($item['day'])] ?? $item['day'];
                $time = $item['time'] ?? 'غير محدد';

                return "{$day} - {$time}";
            })
            ->join('، ');

        return $scheduleText ?: 'لم يتم تحديد الجدول بعد';
    }

    public function getScheduleDaysTextAttribute(): string
    {
        // First, check if we have direct schedule_days attribute
        if (! empty($this->schedule_days) && is_array($this->schedule_days)) {
            return WeekDays::getDisplayNames($this->schedule_days);
        }

        // Fallback to the schedule relationship if available
        if ($this->schedule && $this->schedule->weekly_schedule) {
            $scheduleDays = collect($this->schedule->weekly_schedule)
                ->pluck('day')
                ->unique()
                ->filter()
                ->map(function ($day) {
                    try {
                        return WeekDays::from(strtolower($day))->label();
                    } catch (\ValueError $e) {
                        return $day; // Fallback to original value if not a valid enum
                    }
                });

            return implode('، ', $scheduleDays->toArray());
        }

        return 'لم يتم تحديد الأيام بعد';
    }

    public function getAgeRangeTextAttribute(): string
    {
        if (! $this->age_group) {
            return 'جميع الأعمار';
        }

        return $this->age_group;
    }

    public function getProgressPercentageAttribute(): float
    {
        // Progress is based on monthly session target
        $monthlyTarget = $this->monthly_sessions_count ?? 4;
        if ($monthlyTarget <= 0) {
            return 0;
        }

        return min(100, ($this->sessions_completed / $monthlyTarget) * 100);
    }

    public function getDaysUntilStartAttribute(): int
    {
        // Use next scheduled session if available
        if (! $this->next_session_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->next_session_at, false));
    }

    public function getDaysUntilRegistrationDeadlineAttribute(): int
    {
        // No registration deadline - circles have open enrollment
        return 999;
    }

    // Methods
    public function enrollStudent(User $student, array $additionalData = []): self
    {
        if ($this->is_full) {
            throw new \Exception('الحلقة مكتملة العدد');
        }

        if (! $this->canEnrollStudent($student)) {
            throw new \Exception('لا يمكن تسجيل هذا الطالب في الحلقة');
        }

        $pivotData = array_merge([
            'enrolled_at' => now(),
            'status' => 'enrolled',
            'attendance_count' => 0,
            'missed_sessions' => 0,
            'makeup_sessions_used' => 0,
            'current_level' => $this->memorization_level,
        ], $additionalData);

        $this->students()->attach($student->id, $pivotData);

        // Update enrollment status if full
        if ($this->students()->count() >= $this->max_students) {
            $this->update(['enrollment_status' => CircleEnrollmentStatus::FULL]);
        } elseif ($this->enrollment_status === CircleEnrollmentStatus::CLOSED && $this->status === true) {
            // If there's space and circle is active, set to open for better UX
            $this->update(['enrollment_status' => CircleEnrollmentStatus::OPEN]);
        }

        return $this;
    }

    public function unenrollStudent(User $student, ?string $reason = null): self
    {
        $this->students()->detach($student->id);

        // Update enrollment status
        if ($this->enrollment_status === CircleEnrollmentStatus::FULL && $this->students()->count() < $this->max_students) {
            $this->update(['enrollment_status' => CircleEnrollmentStatus::OPEN]);
        }

        return $this;
    }

    public function canEnrollStudent(User $student): bool
    {
        // Check if already enrolled
        if ($this->students()->where('quran_circle_students.student_id', $student->id)->exists()) {
            return false;
        }

        // Check grade level
        if (! empty($this->grade_levels) && $student->grade_level) {
            if (! in_array($student->grade_level, $this->grade_levels)) {
                return false;
            }
        }

        // Check enrollment status and capacity
        return $this->enrollment_status === CircleEnrollmentStatus::OPEN &&
            ! $this->is_full &&
            $this->status === true;
    }

    public function start(): self
    {
        if (! $this->can_start) {
            throw new \Exception('عدد الطلاب غير كافي لبدء الحلقة');
        }

        $this->update([
            'status' => true, // status is boolean
            'enrollment_status' => CircleEnrollmentStatus::CLOSED,
            'start_date' => now(),
        ]);

        return $this;
    }

    public function suspend(?string $reason = null): self
    {
        // Note: status column is boolean (tinyint(1)), false = suspended/inactive
        // Note: 'notes' column doesn't exist in schema, reason parameter kept for API compatibility
        $this->update([
            'status' => false,
        ]);

        return $this;
    }

    public function resume(): self
    {
        // Note: status column is boolean (tinyint(1)), true = active/ongoing
        $this->update([
            'status' => true,
        ]);

        return $this;
    }

    public function complete(): self
    {
        // Note: status column is boolean (tinyint(1)), false = completed/inactive
        $this->update([
            'status' => false,
            'enrollment_status' => CircleEnrollmentStatus::CLOSED,
            'end_date' => now(),
        ]);

        // Issue certificates if enabled
        if ($this->certificates_enabled) {
            $this->issueCertificates();
        }

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        // Note: status column is boolean (tinyint(1)), false = cancelled/inactive
        // Note: 'notes' column doesn't exist in schema, reason parameter kept for API compatibility
        $this->update([
            'status' => false,
            'enrollment_status' => CircleEnrollmentStatus::CLOSED,
        ]);

        return $this;
    }

    public function recordSession(array $sessionData): QuranSession
    {
        return DB::transaction(function () use ($sessionData) {
            $session = $this->sessions()->create(array_merge($sessionData, [
                'academy_id' => $this->academy_id,
                'quran_teacher_id' => $this->quran_teacher_id,
                'session_type' => 'group',
                'participants_count' => $this->enrolled_students,
            ]));

            // Lock the circle row to prevent race conditions during counter increment
            $lockedCircle = self::lockForUpdate()->find($this->id);
            $lockedCircle->increment('sessions_completed');
            $lockedCircle->update(['last_session_at' => now()]);

            return $session;
        });
    }

    /**
     * Update homework-based progress from session homework records.
     * Called after session homework is submitted/updated.
     */
    public function updateProgressFromHomework(): void
    {
        $sessions = $this->sessions()->with('homework')->get();

        $totalMemorized = 0;
        $totalReviewed = 0;
        $totalReviewedSurahs = 0;

        foreach ($sessions as $session) {
            if ($session->homework) {
                $totalMemorized += $session->homework->new_memorization_pages ?? 0;
                $totalReviewed += $session->homework->review_pages ?? 0;
                $totalReviewedSurahs += count($session->homework->comprehensive_review_surahs ?? []);
            }
        }

        $this->update([
            'total_memorized_pages' => $totalMemorized,
            'total_reviewed_pages' => $totalReviewed,
            'total_reviewed_surahs' => $totalReviewedSurahs,
        ]);
    }

    /**
     * Get progress summary in Arabic
     */
    public function getProgressSummary(): string
    {
        $parts = [];

        if ($this->total_memorized_pages > 0) {
            $parts[] = "{$this->total_memorized_pages} صفحة محفوظة";
        }
        if ($this->total_reviewed_pages > 0) {
            $parts[] = "{$this->total_reviewed_pages} صفحة مراجعة";
        }
        if ($this->total_reviewed_surahs > 0) {
            $parts[] = "{$this->total_reviewed_surahs} سورة مراجعة شاملة";
        }

        return $parts ? implode('، ', $parts) : 'لم يتم تحديد التقدم';
    }

    public function updateRating(): self
    {
        $ratings = $this->students()
            ->wherePivotNotNull('parent_rating')
            ->orWherePivotNotNull('student_rating')
            ->get();

        if ($ratings->isEmpty()) {
            return $this;
        }

        $totalRating = 0;
        $ratingCount = 0;

        foreach ($ratings as $student) {
            if ($student->pivot->parent_rating) {
                $totalRating += $student->pivot->parent_rating;
                $ratingCount++;
            }
            if ($student->pivot->student_rating) {
                $totalRating += $student->pivot->student_rating;
                $ratingCount++;
            }
        }

        $avgRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;

        $this->update([
            'avg_rating' => round($avgRating, 1),
            'total_reviews' => $ratingCount,
        ]);

        // Update teacher's rating
        $this->quranTeacher->updateRating();

        return $this;
    }

    public function calculateStatistics(): self
    {
        $totalEnrolled = $this->students()->count();
        $stillEnrolled = $this->students()->wherePivot('status', 'enrolled')->count();
        $completed = $this->students()->wherePivot('status', 'completed')->count();
        $dropped = $this->students()->wherePivot('status', 'dropped')->count();

        $completionRate = $totalEnrolled > 0 ? ($completed / $totalEnrolled) * 100 : 0;
        $dropoutRate = $totalEnrolled > 0 ? ($dropped / $totalEnrolled) * 100 : 0;

        $this->update([
            'completion_rate' => round($completionRate, 2),
            'dropout_rate' => round($dropoutRate, 2),
        ]);

        return $this;
    }

    public function generateSchedule(): self
    {
        if (empty($this->schedule_days) || empty($this->schedule_times)) {
            return $this;
        }

        // Generate recurring sessions based on schedule
        $schedule = [];
        $currentDate = now()->startOfDay();
        $endDate = $currentDate->copy()->addMonths(3);

        while ($currentDate <= $endDate) {
            $dayName = strtolower($currentDate->format('l'));

            if (in_array($dayName, $this->schedule_days)) {
                foreach ($this->schedule_times as $time) {
                    $sessionDateTime = $currentDate->copy()->setTimeFromTimeString($time);
                    // Duration is determined by subscription/package, default to 60 for scheduling purposes
                    // Actual duration will be set when sessions are created through SessionManagementService
                    $duration = 60;

                    $schedule[] = [
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $duration,
                        'status' => 'scheduled',
                    ];
                }
            }

            $currentDate->addDay();
        }

        $this->update(['weekly_schedule' => $schedule]);

        return $this;
    }

    public function issueCertificates(): self
    {
        if (! $this->certificates_enabled) {
            return $this;
        }

        $completedStudents = $this->students()
            ->wherePivot('status', 'completed')
            ->wherePivot('certificate_issued', false)
            ->get();

        foreach ($completedStudents as $student) {
            // Generate certificate
            $certificateData = [
                'student_name' => $student->name,
                'circle_name' => $this->name,
                'teacher_name' => $this->quranTeacher->user->name,
                'academy_name' => $this->academy->name,
                'completion_date' => now(),
                'specialization' => $this->specialization_text,
                'level' => $this->memorization_level_text,
            ];

            // Update pivot to mark certificate as issued
            $this->students()->updateExistingPivot($student->id, [
                'certificate_issued' => true,
                'completion_date' => now(),
            ]);
        }

        return $this;
    }

    // Static methods
    public static function createCircle(array $data): self
    {
        return self::create(array_merge($data, [
            'circle_code' => self::generateCircleCode($data['academy_id']),
            'enrolled_students' => 0,
            'sessions_completed' => 0,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'completion_rate' => 0,
            'dropout_rate' => 0,
            'status' => false, // boolean: false = planning/inactive
            'enrollment_status' => CircleEnrollmentStatus::CLOSED,
        ]));
    }

    public static function generateCircleCode(int $academyId): string
    {
        $prefix = 'QC';
        $academyPart = $academyId;

        // Get all existing circle codes for this academy
        $existingCodes = self::withTrashed()
            ->where('academy_id', $academyId)
            ->pluck('circle_code')
            ->toArray();

        $maxSequence = 0;

        // Parse existing codes to find the highest sequence number
        foreach ($existingCodes as $code) {
            // Only process codes that match the standard format: QC-{academy}-{sequence}
            if (preg_match("/^{$prefix}-{$academyPart}-(\d+)$/", $code, $matches)) {
                $sequence = (int) $matches[1];
                $maxSequence = max($maxSequence, $sequence);
            }
        }

        $nextSequence = $maxSequence + 1;
        $code = "{$prefix}-{$academyPart}-".str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

        // Double-check for uniqueness (fallback safety)
        $attempt = 0;
        while (in_array($code, $existingCodes) && $attempt < 100) {
            $nextSequence++;
            $code = "{$prefix}-{$academyPart}-".str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
            $attempt++;
        }

        return $code;
    }

    // Boot method to handle model events
    protected static function booted()
    {
        static::creating(function ($circle) {
            if (empty($circle->circle_code)) {
                $circle->circle_code = self::generateCircleCode($circle->academy_id);
            }
        });

        // Clean up related data when circle is deleted
        static::deleting(function ($circle) {
            // Delete all sessions for this circle
            $circle->sessions()->delete();

            // Delete schedule if exists
            if ($circle->schedule) {
                $circle->schedule->delete();
            }

            // Clean up pivot table entries (students)
            $circle->students()->detach();
        });

        // Clean up when force deleting (hard delete)
        static::forceDeleting(function ($circle) {
            // Force delete all sessions for this circle
            $circle->sessions()->forceDelete();

            // Force delete schedule if exists
            if ($circle->schedule) {
                $circle->schedule->forceDelete();
            }

            // Clean up pivot table entries (students)
            $circle->students()->detach();
        });
    }

    public static function getOpenForEnrollment(int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('academy_id', $academyId)
            ->openForEnrollment()
            ->with(['quranTeacher', 'academy']);

        if (isset($filters['specialization'])) {
            $query->where('specialization', $filters['specialization']);
        }

        if (isset($filters['grade_level'])) {
            $query->byGradeLevel($filters['grade_level']);
        }

        if (isset($filters['age'])) {
            $query->byAgeRange($filters['age']);
        }

        if (isset($filters['day'])) {
            $query->byDay($filters['day']);
        }

        if (isset($filters['min_rating'])) {
            $query->highRated($filters['min_rating']);
        }

        return $query->orderBy('avg_rating', 'desc')
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public static function getStartingSoon(int $academyId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->startingSoon($days)
            ->with(['quranTeacher', 'students'])
            ->get();
    }
}
