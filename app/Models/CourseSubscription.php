<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Traits\PreventsDuplicatePendingSubscriptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CourseSubscription Model
 *
 * Unified model for both recorded courses and interactive courses.
 * Handles one-time purchase subscriptions with lifetime or timed access.
 *
 * KEY CONCEPTS:
 * - One-time purchase: No recurring billing (uses BillingCycle::LIFETIME)
 * - Two course types: 'recorded' (self-paced) and 'interactive' (live sessions)
 * - Self-contained: Course data is snapshotted at creation
 * - Progress tracking differs by type:
 *   - Recorded: lesson completion, watch time
 *   - Interactive: session attendance, grades
 * - NO auto-renewal: Courses are one-time purchases
 *
 * COURSE TYPES:
 * - 'recorded': Pre-recorded video lessons, self-paced
 * - 'interactive': Live sessions with teacher, fixed schedule
 *
 * @property int|null $recorded_course_id
 * @property int|null $interactive_course_id
 * @property string $course_type
 * @property string|null $enrollment_type
 * @property string|null $access_type
 * @property int|null $access_duration_months
 * @property bool $lifetime_access
 * @property int $completed_lessons
 * @property int $total_lessons
 * @property int $total_duration_minutes
 * @property int $attendance_count
 * @property int $total_possible_attendance
 * @property float|null $final_grade
 * @property bool $quiz_passed
 * @property int $quiz_attempts
 * @property float|null $final_score
 */
class CourseSubscription extends BaseSubscription
{
    // Note: NO HandlesSubscriptionRenewal trait - courses don't auto-renew
    use PreventsDuplicatePendingSubscriptions;

    /**
     * The database table for this model
     */
    protected $table = 'course_subscriptions';

    /**
     * Get the fields that identify a unique subscription combination.
     * For Course subscriptions: the course ID (either recorded or interactive).
     *
     * @return array<string>
     */
    protected function getDuplicateKeyFields(): array
    {
        // Return the appropriate field based on course type
        return $this->course_type === self::COURSE_TYPE_INTERACTIVE
            ? ['interactive_course_id']
            : ['recorded_course_id'];
    }

    /**
     * Get the "pending" status value for Course subscriptions.
     * Uses EnrollmentStatus instead of SessionSubscriptionStatus.
     */
    protected function getPendingStatus(): mixed
    {
        return EnrollmentStatus::PENDING;
    }

    /**
     * Get the "active" status value for Course subscriptions.
     * For courses, "ENROLLED" is the equivalent of "ACTIVE".
     */
    protected function getActiveStatus(): mixed
    {
        return EnrollmentStatus::ENROLLED;
    }

    /**
     * Get the "cancelled" status value for Course subscriptions.
     */
    protected function getCancelledStatus(): mixed
    {
        return EnrollmentStatus::CANCELLED;
    }

    /**
     * Course-specific fillable fields
     * Merged with BaseSubscription::$baseFillable in constructor
     */
    protected $fillable = [
        // Course references (one will be set based on course_type)
        'recorded_course_id',
        'interactive_course_id',

        // Course type
        'course_type',

        // Enrollment info
        'enrollment_type',
        'enrolled_at',
        'enrolled_by',

        // Access configuration
        'access_type',
        'access_duration_months',
        'lifetime_access',

        // Pricing
        'price_paid',
        'original_price',
        'discount_code',

        // Recorded course progress
        'completed_lessons',
        'total_lessons',
        'total_duration_minutes',
        'last_accessed_at',
        'completion_date',

        // Interactive course progress
        'attendance_count',
        'total_possible_attendance',
        'final_grade',

        // Quiz tracking
        'quiz_attempts',
        'quiz_passed',
        'final_score',

        // Refund
        'refund_requested_at',
        'refund_reason',
        'refund_processed_at',
        'refund_amount',

        // Engagement
        'notes_count',
        'bookmarks_count',
        'completion_certificate_url',
    ];

    /**
     * Constructor: Merge fillable with parent's baseFillable
     */
    public function __construct(array $attributes = [])
    {
        // Merge Course-specific fillable with base fillable
        $this->fillable = array_merge(parent::$baseFillable, $this->fillable);
        parent::__construct($attributes);
    }

    /**
     * Get casts: Merge Course-specific casts with parent casts
     * NOTE: We override 'status' to use EnrollmentStatus instead of SessionSubscriptionStatus
     * because courses can be COMPLETED (unlike session-based subscriptions)
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Override status to use EnrollmentStatus (courses can be completed)
            'status' => EnrollmentStatus::class,

            // Access
            'access_duration_months' => 'integer',
            'lifetime_access' => 'boolean',
            'enrolled_at' => 'datetime',

            // Pricing
            'price_paid' => 'decimal:2',
            'original_price' => 'decimal:2',

            // Recorded course progress
            'completed_lessons' => 'integer',
            'total_lessons' => 'integer',
            'total_duration_minutes' => 'integer',
            'last_accessed_at' => 'datetime',
            'completion_date' => 'datetime',

            // Interactive course progress
            'attendance_count' => 'integer',
            'total_possible_attendance' => 'integer',
            'final_grade' => 'decimal:2',

            // Quiz
            'quiz_attempts' => 'integer',
            'quiz_passed' => 'boolean',
            'final_score' => 'decimal:2',

            // Refund
            'refund_requested_at' => 'datetime',
            'refund_processed_at' => 'datetime',
            'refund_amount' => 'decimal:2',

            // Engagement
            'notes_count' => 'integer',
            'bookmarks_count' => 'integer',
        ]);
    }

    /**
     * Default attributes
     */
    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'currency' => 'SAR',
        'billing_cycle' => 'lifetime',  // Courses are one-time purchases
        'auto_renew' => false,          // No auto-renewal for courses
        'progress_percentage' => 0,
        'certificate_issued' => false,
        'course_type' => 'recorded',
        'enrollment_type' => 'paid',
        'lifetime_access' => true,
        'completed_lessons' => 0,
        'total_lessons' => 0,
        'total_duration_minutes' => 0,
        'attendance_count' => 0,
        'total_possible_attendance' => 0,
        'quiz_attempts' => 0,
        'quiz_passed' => false,
        'notes_count' => 0,
        'bookmarks_count' => 0,
    ];

    // ========================================
    // CONSTANTS
    // ========================================

    const COURSE_TYPE_RECORDED = 'recorded';

    const COURSE_TYPE_INTERACTIVE = 'interactive';

    const ENROLLMENT_TYPE_FREE = 'free';

    const ENROLLMENT_TYPE_PAID = 'paid';

    const ENROLLMENT_TYPE_TRIAL = 'trial';

    const ENROLLMENT_TYPE_GIFT = 'gift';

    // ========================================
    // RELATIONSHIPS (Course-specific)
    // ========================================

    /**
     * Get the recorded course (for recorded course subscriptions)
     */
    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    /**
     * Get the interactive course (for interactive course subscriptions)
     */
    public function interactiveCourse(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class);
    }

    /**
     * Get the course (polymorphic helper)
     */
    public function getCourseAttribute()
    {
        return $this->course_type === self::COURSE_TYPE_INTERACTIVE
            ? $this->interactiveCourse
            : $this->recordedCourse;
    }

    /**
     * Alias for student() relationship (for API compatibility)
     */
    public function user(): BelongsTo
    {
        return $this->student();
    }

    /**
     * Course relationship - returns the appropriate course model
     * Note: This is a "fake" relationship for PHPStan compatibility
     * Use getCourseAttribute() accessor for actual usage
     */
    public function course(): BelongsTo
    {
        // Return recorded course by default - accessor handles polymorphism
        return $this->belongsTo(RecordedCourse::class, 'recorded_course_id');
    }

    /**
     * Get the user who enrolled the student
     */
    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Get progress records (for recorded courses)
     */
    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'recorded_course_id', 'recorded_course_id')
            ->where('user_id', $this->student_id);
    }

    // Note: payments() relationship is inherited from BaseSubscription using morphMany

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS
    // ========================================

    /**
     * Get subscription type identifier
     */
    public function getSubscriptionType(): string
    {
        return 'course';
    }

    /**
     * Get subscription type label
     */
    public function getSubscriptionTypeLabel(): string
    {
        return $this->course_type === self::COURSE_TYPE_INTERACTIVE
            ? 'دورة تفاعلية'
            : 'دورة مسجلة';
    }

    /**
     * Get subscription title for display
     */
    public function getSubscriptionTitle(): string
    {
        if ($this->package_name_ar) {
            return $this->package_name_ar;
        }

        $course = $this->course;

        return $course?->name ?? $course?->title ?? 'دورة تدريبية';
    }

    /**
     * Get the teacher for this subscription (if applicable)
     */
    public function getTeacher(): ?User
    {
        if ($this->course_type === self::COURSE_TYPE_INTERACTIVE) {
            return $this->interactiveCourse?->assignedTeacher;
        }

        // Recorded courses may or may not have a teacher
        return $this->recordedCourse?->instructor;
    }

    /**
     * Calculate renewal price (not applicable for courses - one-time purchase)
     */
    public function calculateRenewalPrice(): float
    {
        // Courses don't renew - return 0 or the original price for reference
        return $this->final_price ?? $this->price_paid ?? 0;
    }

    /**
     * Snapshot course data to subscription (self-containment)
     */
    public function snapshotPackageData(): array
    {
        $course = $this->course;

        if (! $course) {
            return [];
        }

        return [
            'package_name_ar' => $course->name ?? $course->title,
            'package_name_en' => $course->name_en ?? $course->title_en ?? $course->title,
            'package_description_ar' => $course->description ?? $course->short_description,
            'package_description_en' => $course->description_en ?? $course->description,
            'monthly_price' => $course->price ?? 0,  // For courses, this is the one-time price
        ];
    }

    /**
     * Get sessions relationship (for interactive courses)
     */
    public function getSessions()
    {
        if ($this->course_type === self::COURSE_TYPE_INTERACTIVE) {
            return $this->interactiveCourse?->sessions();
        }

        // For recorded courses, return lessons
        return $this->recordedCourse?->lessons();
    }

    // ========================================
    // COURSE-SPECIFIC SCOPES
    // ========================================

    /**
     * Scope: Get recorded course subscriptions
     */
    public function scopeRecordedCourses($query)
    {
        return $query->where('course_type', self::COURSE_TYPE_RECORDED);
    }

    /**
     * Scope: Get interactive course subscriptions
     */
    public function scopeInteractiveCourses($query)
    {
        return $query->where('course_type', self::COURSE_TYPE_INTERACTIVE);
    }

    /**
     * Scope: Get subscriptions with lifetime access
     */
    public function scopeLifetimeAccess($query)
    {
        return $query->where('lifetime_access', true);
    }

    /**
     * Scope: Get subscriptions in progress (started but not completed)
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', EnrollmentStatus::ENROLLED)
            ->where('progress_percentage', '>', 0)
            ->where('progress_percentage', '<', 100);
    }

    /**
     * Scope: Get not started subscriptions
     */
    public function scopeNotStarted($query)
    {
        return $query->where('status', EnrollmentStatus::ENROLLED)
            ->where('progress_percentage', 0);
    }

    /**
     * Scope: Get free enrollments
     */
    public function scopeFree($query)
    {
        return $query->where('enrollment_type', self::ENROLLMENT_TYPE_FREE);
    }

    /**
     * Scope: Get eligible for certificate
     */
    public function scopeEligibleForCertificate($query)
    {
        return $query->where('certificate_issued', false)
            ->where('progress_percentage', '>=', 90);
    }

    // ========================================
    // COURSE-SPECIFIC ACCESSORS
    // ========================================

    /**
     * Get access status display
     */
    public function getAccessStatusAttribute(): string
    {
        if ($this->lifetime_access) {
            return 'وصول مدى الحياة';
        }

        if ($this->ends_at && $this->ends_at->isFuture()) {
            $days = now()->diffInDays($this->ends_at);

            return "باقي {$days} يوم";
        }

        return 'منتهي الصلاحية';
    }

    /**
     * Get enrollment type label
     */
    public function getEnrollmentTypeLabelAttribute(): string
    {
        return match ($this->enrollment_type) {
            self::ENROLLMENT_TYPE_FREE => 'مجاني',
            self::ENROLLMENT_TYPE_PAID => 'مدفوع',
            self::ENROLLMENT_TYPE_TRIAL => 'تجريبي',
            self::ENROLLMENT_TYPE_GIFT => 'هدية',
            default => $this->enrollment_type ?? 'مدفوع',
        };
    }

    /**
     * Get completion rate (lessons completed / total)
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->course_type === self::COURSE_TYPE_INTERACTIVE) {
            // For interactive courses, use attendance
            if ($this->total_possible_attendance <= 0) {
                return 0;
            }

            return round(($this->attendance_count / $this->total_possible_attendance) * 100, 2);
        }

        // For recorded courses, use lesson completion
        if ($this->total_lessons <= 0) {
            return 0;
        }

        return round(($this->completed_lessons / $this->total_lessons) * 100, 2);
    }

    /**
     * Get attendance percentage (for interactive courses)
     */
    public function getAttendancePercentageAttribute(): float
    {
        if ($this->total_possible_attendance <= 0) {
            return 0;
        }

        return round(($this->attendance_count / $this->total_possible_attendance) * 100, 2);
    }

    /**
     * Get total duration formatted
     */
    public function getTotalDurationFormattedAttribute(): string
    {
        return $this->formatDuration($this->total_duration_minutes * 60);
    }

    /**
     * Check if student passed the course (for interactive courses)
     */
    public function getHasPassedAttribute(): bool
    {
        if ($this->final_grade !== null) {
            return $this->final_grade >= 60; // 60% passing grade
        }

        return $this->quiz_passed || $this->progress_percentage >= 100;
    }

    /**
     * Check if can earn certificate
     */
    public function getCanEarnCertificateAttribute(): bool
    {
        return ! $this->certificate_issued && $this->progress_percentage >= 90;
    }

    // ========================================
    // STATUS HELPER OVERRIDES (for EnrollmentStatus)
    // ========================================

    /**
     * Check if subscription is active (enrolled)
     * Overrides parent to use EnrollmentStatus
     */
    public function isActive(): bool
    {
        return $this->status === EnrollmentStatus::ENROLLED;
    }

    /**
     * Check if subscription is pending
     * Overrides parent to use EnrollmentStatus
     */
    public function isPending(): bool
    {
        return $this->status === EnrollmentStatus::PENDING;
    }

    /**
     * Check if subscription is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === EnrollmentStatus::COMPLETED;
    }

    /**
     * Check if subscription is cancelled
     * Overrides parent to use EnrollmentStatus
     */
    public function isCancelled(): bool
    {
        return $this->status === EnrollmentStatus::CANCELLED;
    }

    /**
     * Check if subscription allows content access
     * Completed courses still allow access
     */
    public function canAccess(): bool
    {
        return $this->status->canAccess() && $this->payment_status->allowsAccess();
    }

    /**
     * Check if subscription is eligible for certificate
     * Overrides parent to allow completed courses
     */
    public function isCertificateEligible(): bool
    {
        return $this->isCompleted() || $this->progress_percentage >= 90;
    }

    // ========================================
    // PROGRESS MANAGEMENT METHODS
    // ========================================

    /**
     * Update progress for recorded courses
     */
    public function updateRecordedCourseProgress(): self
    {
        if ($this->course_type !== self::COURSE_TYPE_RECORDED) {
            return $this;
        }

        $courseProgress = $this->progress()->get();

        $completedLessons = $courseProgress->where('is_completed', true)->count();

        $progressPercentage = 0;
        if ($this->recordedCourse) {
            $totalLessons = $this->recordedCourse->total_lessons ?? 0;
            if ($totalLessons > 0) {
                $progressPercentage = ($completedLessons / $totalLessons) * 100;
            }
        }

        $this->update([
            'completed_lessons' => $completedLessons,
            'total_lessons' => $this->recordedCourse?->total_lessons ?? $this->total_lessons,
            'progress_percentage' => $progressPercentage,
            'last_accessed_at' => now(),
        ]);

        // Check for completion
        if ($progressPercentage >= 100 && $this->isActive()) {
            $this->markAsCompleted();
        }

        return $this;
    }

    /**
     * Record attendance for interactive course
     */
    public function recordAttendance(bool $attended = true): self
    {
        if ($this->course_type !== self::COURSE_TYPE_INTERACTIVE) {
            return $this;
        }

        if ($attended) {
            $this->increment('attendance_count');
        }

        // Update progress percentage based on attendance
        if ($this->total_possible_attendance > 0) {
            $this->update([
                'progress_percentage' => $this->attendance_percentage,
            ]);
        }

        return $this;
    }

    /**
     * Mark subscription as completed
     */
    public function markAsCompleted(): self
    {
        $this->update([
            'status' => EnrollmentStatus::COMPLETED,
            'completion_date' => now(),
            'progress_percentage' => 100,
        ]);

        // Issue certificate if eligible
        if ($this->can_earn_certificate) {
            $this->issueCertificateForCourse();
        }

        return $this;
    }

    /**
     * Issue certificate for this subscription
     */
    public function issueCertificateForCourse(): self
    {
        if ($this->certificate_issued || ! $this->can_earn_certificate) {
            return $this;
        }

        try {
            $certificateService = app(\App\Services\CertificateService::class);

            if ($this->course_type === self::COURSE_TYPE_RECORDED) {
                $certificateService->issueCertificateForRecordedCourse($this);
            } else {
                // For interactive courses
                $certificateService->issueCertificateForInteractiveCourse($this);
            }

            $this->refresh();
        } catch (\Exception $e) {
            \Log::error("Failed to issue certificate for CourseSubscription {$this->id}: ".$e->getMessage());
        }

        return $this;
    }

    // ========================================
    // ACCESS MANAGEMENT METHODS
    // ========================================

    /**
     * Extend access by months
     */
    public function extendAccess(int $months): self
    {
        if ($this->lifetime_access) {
            return $this;
        }

        $newExpiry = $this->ends_at && $this->ends_at->isFuture()
            ? $this->ends_at->addMonths($months)
            : now()->addMonths($months);

        $this->update([
            'ends_at' => $newExpiry,
            'status' => EnrollmentStatus::ENROLLED,
        ]);

        return $this;
    }

    /**
     * Grant lifetime access
     */
    public function grantLifetimeAccess(): self
    {
        $this->update([
            'lifetime_access' => true,
            'ends_at' => null,
            'status' => EnrollmentStatus::ENROLLED,
        ]);

        return $this;
    }

    /**
     * Request refund
     */
    public function requestRefund(string $reason): self
    {
        $this->update([
            'refund_requested_at' => now(),
            'refund_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Process refund
     * Note: Refunds are tracked by refund_amount and refund_processed_at fields,
     * not by payment_status. The subscription is cancelled when refunded.
     */
    public function processRefund(float $amount): self
    {
        $this->update([
            'status' => EnrollmentStatus::CANCELLED,
            'refund_processed_at' => now(),
            'refund_amount' => $amount,
            'cancellation_reason' => 'استرداد المبلغ',
        ]);

        return $this;
    }

    // ========================================
    // STATIC FACTORY METHODS
    // ========================================

    /**
     * Create a new course subscription with data snapshot
     */
    public static function createSubscription(array $data): self
    {
        // Generate subscription code
        $data['subscription_code'] = static::generateSubscriptionCode(
            $data['academy_id'],
            'CS'
        );

        // Set defaults
        $data = array_merge([
            'status' => EnrollmentStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'billing_cycle' => BillingCycle::LIFETIME,
            'auto_renew' => false,
            'progress_percentage' => 0,
            'enrolled_at' => now(),
        ], $data);

        // Determine course type
        if (! empty($data['interactive_course_id'])) {
            $data['course_type'] = self::COURSE_TYPE_INTERACTIVE;
        } else {
            $data['course_type'] = self::COURSE_TYPE_RECORDED;
        }

        $subscription = static::create($data);

        // Snapshot course data
        $packageData = $subscription->snapshotPackageData();
        if (! empty($packageData)) {
            $subscription->update($packageData);
        }

        // Initialize course-specific data
        $subscription->initializeCourseData();

        return $subscription;
    }

    /**
     * Create free enrollment
     */
    public static function createFreeEnrollment(array $data): self
    {
        return static::createSubscription(array_merge($data, [
            'enrollment_type' => self::ENROLLMENT_TYPE_FREE,
            'status' => EnrollmentStatus::ENROLLED,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'final_price' => 0,
            'price_paid' => 0,
            'lifetime_access' => true,
        ]));
    }

    /**
     * Initialize course data after creation
     */
    protected function initializeCourseData(): void
    {
        if ($this->course_type === self::COURSE_TYPE_RECORDED && $this->recordedCourse) {
            $this->update([
                'total_lessons' => $this->recordedCourse->total_lessons ?? 0,
                'total_duration_minutes' => $this->recordedCourse->total_duration_minutes ?? 0,
            ]);
        } elseif ($this->course_type === self::COURSE_TYPE_INTERACTIVE && $this->interactiveCourse) {
            $this->update([
                'total_possible_attendance' => $this->interactiveCourse->sessions()->count(),
            ]);
        }
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function booted()
    {
        parent::boot();

        // Set ends_at based on access duration on creation
        static::creating(function ($subscription) {
            if (! $subscription->lifetime_access && ! $subscription->ends_at) {
                $months = $subscription->access_duration_months ?? 12;
                $subscription->ends_at = now()->addMonths($months);
            }
        });

        // Update last accessed timestamp
        static::updating(function ($subscription) {
            if ($subscription->isDirty(['completed_lessons', 'attendance_count'])) {
                $subscription->last_accessed_at = now();
            }
        });
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Format duration for display
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' ثانية';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes.' دقيقة'.($remainingSeconds > 0 ? ' و '.$remainingSeconds.' ثانية' : '');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours.' ساعة'.
            ($remainingMinutes > 0 ? ' و '.$remainingMinutes.' دقيقة' : '');
    }

    /**
     * Get next lesson (for recorded courses)
     */
    public function getNextLesson(): ?Lesson
    {
        if ($this->course_type !== self::COURSE_TYPE_RECORDED || ! $this->recordedCourse) {
            return null;
        }

        return $this->recordedCourse
            ->lessons()
            ->whereDoesntHave('progress', function ($query) {
                $query->where('user_id', $this->student_id)
                    ->where('is_completed', true);
            })
            ->orderBy('order')
            ->first();
    }

    // ========================================
    // SESSION TRACKING METHODS (BaseSubscription Abstract Methods)
    // ========================================

    /**
     * Get total number of sessions in subscription
     * For interactive courses: uses total_possible_attendance
     * For recorded courses: uses total_lessons
     */
    public function getTotalSessions(): int
    {
        if ($this->course_type === self::COURSE_TYPE_INTERACTIVE) {
            return $this->total_possible_attendance ?? 0;
        }

        // Recorded courses
        return $this->total_lessons ?? 0;
    }

    /**
     * Get number of sessions used/completed
     * For interactive courses: uses attendance_count
     * For recorded courses: uses completed_lessons
     */
    public function getSessionsUsed(): int
    {
        if ($this->course_type === self::COURSE_TYPE_INTERACTIVE) {
            return $this->attendance_count ?? 0;
        }

        // Recorded courses
        return $this->completed_lessons ?? 0;
    }

    /**
     * Get number of sessions remaining
     * Calculated as: total_sessions - sessions_used
     */
    public function getSessionsRemaining(): int
    {
        $total = $this->getTotalSessions();
        $used = $this->getSessionsUsed();

        return max(0, $total - $used);
    }
}
