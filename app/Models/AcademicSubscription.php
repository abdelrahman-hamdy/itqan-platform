<?php

namespace App\Models;

use Exception;
use Log;
use App\Enums\SessionStatus;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use App\Enums\LessonStatus;
use Carbon\Carbon;
use App\Constants\DefaultAcademy;
use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Traits\HandlesSubscriptionRenewal;
use App\Models\Traits\PreventsDuplicatePendingSubscriptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * AcademicSubscription Model
 *
 * Handles subscriptions for academic tutoring (private lessons).
 * Extends BaseSubscription for common functionality and uses HandlesSubscriptionRenewal
 * for auto-renewal capabilities.
 *
 * KEY CONCEPTS:
 * - Session-based: Subscriptions track scheduled/completed/missed sessions
 * - Subject-specific: Each subscription is tied to a subject and grade level
 * - Self-contained: Package data is snapshotted at creation
 * - Auto-renewal: Enabled by default with NO grace period on payment failure
 * - Weekly schedule: Stores preferred days/times for sessions
 *
 * @property int|null $teacher_id
 * @property int|null $subject_id
 * @property int|null $grade_level_id
 * @property string|null $subject_name
 * @property string|null $grade_level_name
 * @property int|null $session_request_id
 * @property int|null $academic_package_id
 * @property string $subscription_type
 * @property int|null $sessions_per_week
 * @property float|null $hourly_rate
 * @property array|null $weekly_schedule
 * @property string|null $timezone
 * @property bool $auto_create_google_meet
 * @property bool $has_trial_session
 * @property bool $trial_session_used
 * @property Carbon|null $trial_session_date
 * @property string|null $trial_session_status
 * @property int $total_sessions_scheduled
 * @property int $total_sessions_completed
 * @property int $total_sessions_missed
 */
class AcademicSubscription extends BaseSubscription
{
    use HandlesSubscriptionRenewal;
    use PreventsDuplicatePendingSubscriptions;

    /**
     * The database table for this model
     */
    protected $table = 'academic_subscriptions';

    /**
     * Get the fields that identify a unique subscription combination.
     * For Academic subscriptions: teacher + package + subject.
     *
     * @return array<string>
     */
    protected function getDuplicateKeyFields(): array
    {
        return ['teacher_id', 'academic_package_id', 'subject_id'];
    }

    /**
     * Get the "pending" status value for Academic subscriptions.
     */
    protected function getPendingStatus(): mixed
    {
        return SessionSubscriptionStatus::PENDING;
    }

    /**
     * Get the "active" status value for Academic subscriptions.
     */
    protected function getActiveStatus(): mixed
    {
        return SessionSubscriptionStatus::ACTIVE;
    }

    /**
     * Get the "cancelled" status value for Academic subscriptions.
     */
    protected function getCancelledStatus(): mixed
    {
        return SessionSubscriptionStatus::CANCELLED;
    }

    /**
     * Academic-specific fillable fields
     * Merged with BaseSubscription::$baseFillable in constructor
     */
    protected $fillable = [
        // Teacher reference (points to AcademicTeacherProfile)
        'teacher_id',

        // Subject and grade level
        'subject_id',
        'grade_level_id',
        'subject_name',      // Snapshotted from subject
        'grade_level_name',  // Snapshotted from grade level

        // Session request reference
        'session_request_id',

        // Package reference
        'academic_package_id',

        // Subscription type
        'subscription_type',

        // Session scheduling
        'sessions_per_week',
        'weekly_schedule',
        'timezone',
        'auto_create_google_meet',

        // Trial session
        'has_trial_session',
        'trial_session_used',
        'trial_session_date',
        'trial_session_status',

        // Notes and preferences
        'student_notes',      // Student's notes/comments during subscription
        'learning_goals',     // Student's learning goals
        'preferred_times',    // Preferred scheduling times

        // Admin/Supervisor notes (standardized pattern)
        'admin_notes',        // Internal admin notes
        'supervisor_notes',   // Supervisor management notes

        // Amount fields
        'monthly_amount',
        'final_monthly_amount',

        // Date fields
        'start_date',
        'end_date',

        // Additional fields
        'auto_renew',
        'renewal_reminder_days',
        'pause_days_remaining',
        'completion_rate',

        // Session tracking (aligned with QuranSubscription pattern)
        'total_sessions',
        'total_sessions_scheduled',
        'total_sessions_completed',
        'total_sessions_missed',
        'sessions_used',        // Legacy - kept for backwards compatibility
        'sessions_remaining',   // Legacy - kept for backwards compatibility
    ];

    /**
     * Constructor: Merge fillable with parent's baseFillable
     */
    public function __construct(array $attributes = [])
    {
        // Merge Academic-specific fillable with base fillable
        $this->fillable = array_merge(parent::$baseFillable, $this->fillable);
        parent::__construct($attributes);
    }

    /**
     * Get casts: Merge Academic-specific casts with parent casts
     * IMPORTANT: Do NOT define protected $casts - it would override parent's casts
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Subscription dates
            'start_date' => 'datetime',
            'end_date' => 'datetime',

            // Session scheduling
            'sessions_per_week' => 'integer',
            'weekly_schedule' => 'array',
            'auto_create_google_meet' => 'boolean',

            // Trial session
            'has_trial_session' => 'boolean',
            'trial_session_used' => 'boolean',
            'trial_session_date' => 'datetime',

            // Preferences
            'preferred_times' => 'array',

            // Session tracking (aligned with QuranSubscription pattern)
            'total_sessions' => 'integer',
            'total_sessions_scheduled' => 'integer',
            'total_sessions_completed' => 'integer',
            'total_sessions_missed' => 'integer',
            'sessions_used' => 'integer',        // Legacy
            'sessions_remaining' => 'integer',   // Legacy
        ]);
    }

    /**
     * Default attributes
     */
    protected $attributes = [
        'status' => SessionSubscriptionStatus::PENDING->value,
        'payment_status' => SubscriptionPaymentStatus::PENDING->value,
        'currency' => 'SAR',
        'billing_cycle' => BillingCycle::MONTHLY->value,
        'auto_renew' => true,
        'progress_percentage' => 0,
        'certificate_issued' => false,
        'subscription_type' => 'private',
        'session_duration_minutes' => 60,
        'auto_create_google_meet' => true,
        'has_trial_session' => false,
        'trial_session_used' => false,
        // Session tracking defaults (aligned with QuranSubscription)
        'total_sessions' => 8,
        'total_sessions_scheduled' => 0,
        'total_sessions_completed' => 0,
        'total_sessions_missed' => 0,
        'sessions_used' => 0,
        'sessions_remaining' => 8,
    ];

    // ========================================
    // CONSTANTS
    // ========================================

    const SUBSCRIPTION_TYPE_PRIVATE = 'private';

    const SUBSCRIPTION_TYPE_GROUP = 'group'; // Reserved for future use

    // ========================================
    // RELATIONSHIPS (Academic-specific)
    // ========================================

    /**
     * Get the academic teacher profile
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * Alias for teacher relationship
     */
    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * Get the academic subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    /**
     * Get the grade level
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class);
    }

    /**
     * Get the session request (if subscription was created from a request)
     */
    public function sessionRequest(): BelongsTo
    {
        return $this->belongsTo(SessionRequest::class);
    }

    /**
     * Get the original package (for reference only - data is snapshotted)
     */
    public function academicPackage(): BelongsTo
    {
        return $this->belongsTo(AcademicPackage::class, 'academic_package_id');
    }

    /**
     * Alias for academicPackage (for API compatibility)
     */
    public function package(): BelongsTo
    {
        return $this->academicPackage();
    }

    /**
     * Get all academic sessions for this subscription
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'academic_subscription_id');
    }

    /**
     * Get the academic individual lesson for this subscription
     */
    public function lesson(): HasOne
    {
        return $this->hasOne(AcademicIndividualLesson::class, 'academic_subscription_id');
    }

    // Note: payments() relationship is inherited from BaseSubscription using morphMany

    /**
     * Get quiz assignments for this subscription
     */
    public function quizAssignments(): MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS
    // ========================================

    /**
     * Get subscription type identifier
     */
    public function getSubscriptionType(): string
    {
        return 'academic';
    }

    /**
     * Get subscription type label
     */
    public function getSubscriptionTypeLabel(): string
    {
        return $this->subscription_type === self::SUBSCRIPTION_TYPE_PRIVATE
            ? 'دروس أكاديمية خاصة'
            : 'دروس أكاديمية جماعية';
    }

    /**
     * Get subscription title for display
     */
    public function getSubscriptionTitle(): string
    {
        if ($this->package_name_ar) {
            return $this->package_name_ar;
        }

        $subjectName = $this->subject_name ?? $this->subject?->name ?? 'مادة';
        $gradeName = $this->grade_level_name ?? $this->gradeLevel?->name ?? '';

        return "دروس {$subjectName}".($gradeName ? " - {$gradeName}" : '');
    }

    /**
     * Get the teacher (User) for this subscription
     */
    public function getTeacher(): ?User
    {
        return $this->teacher?->user;
    }

    /**
     * Calculate renewal price based on billing cycle
     */
    public function calculateRenewalPrice(): float
    {
        // Use stored prices if available
        $price = $this->getPriceForBillingCycle();
        if ($price > 0) {
            return $price;
        }

        // Fall back to calculated monthly amount
        return $this->final_price ?? ($this->calculateMonthlyAmount() * ($this->billing_cycle?->months() ?? 1));
    }

    /**
     * Snapshot package data to subscription (self-containment)
     */
    public function snapshotPackageData(): array
    {
        $package = $this->academicPackage;

        if (! $package) {
            // Create snapshot from current data
            return [
                'package_name_ar' => $this->subject_name ?? 'باقة أكاديمية',
                'package_name_en' => 'Academic Package',
                'sessions_per_month' => ($this->sessions_per_week ?? 2) * 4.33,
                'session_duration_minutes' => $this->session_duration_minutes ?? 60,
                'monthly_price' => $this->calculateMonthlyAmount(),
            ];
        }

        return [
            'package_name_ar' => $package->name,
            'package_name_en' => $package->name,
            'package_description_ar' => $package->description,
            'package_description_en' => $package->description,
            'package_features' => $package->features ?? [],
            'sessions_per_month' => $package->sessions_per_month ?? ($this->sessions_per_week * 4.33),
            'session_duration_minutes' => $package->session_duration_minutes ?? $this->session_duration_minutes ?? 60,
            'monthly_price' => $package->monthly_price ?? $this->calculateMonthlyAmount(),
            'quarterly_price' => $package->quarterly_price ?? ($this->calculateMonthlyAmount() * 3 * 0.9),
            'yearly_price' => $package->yearly_price ?? ($this->calculateMonthlyAmount() * 12 * 0.8),
        ];
    }

    /**
     * Get sessions relationship for the subscription
     */
    public function getSessions()
    {
        return $this->sessions();
    }

    // ========================================
    // ACADEMIC-SPECIFIC SCOPES
    // ========================================

    /**
     * Scope: Get private lesson subscriptions
     */
    public function scopePrivate($query)
    {
        return $query->where('subscription_type', self::SUBSCRIPTION_TYPE_PRIVATE);
    }

    /**
     * Scope: Get subscriptions for a specific teacher
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope: Get subscriptions for a specific subject
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope: Get subscriptions for a specific grade level
     */
    public function scopeForGradeLevel($query, int $gradeLevelId)
    {
        return $query->where('grade_level_id', $gradeLevelId);
    }

    /**
     * Scope: Get subscriptions with trial available
     */
    public function scopeWithTrialAvailable($query)
    {
        return $query->where('has_trial_session', true)
            ->where('trial_session_used', false);
    }

    // ========================================
    // ACADEMIC-SPECIFIC ACCESSORS
    // ========================================

    /**
     * Get attendance rate based on completed and missed sessions
     */
    public function getAttendanceRateAttribute(): float
    {
        $totalAttendable = $this->total_sessions_completed + $this->total_sessions_missed;

        if ($totalAttendable === 0) {
            return 0;
        }

        return round(($this->total_sessions_completed / $totalAttendable) * 100, 2);
    }

    /**
     * Get completion rate (scheduled vs completed)
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_sessions_scheduled <= 0) {
            return 0;
        }

        return round(($this->total_sessions_completed / $this->total_sessions_scheduled) * 100, 2);
    }

    /**
     * Check if trial session is available
     */
    public function getTrialAvailableAttribute(): bool
    {
        return $this->has_trial_session && ! $this->trial_session_used;
    }

    /**
     * Get teacher name for display
     */
    public function getTeacherNameAttribute(): ?string
    {
        return $this->teacher?->user?->name;
    }

    // ========================================
    // SESSION MANAGEMENT METHODS
    // ========================================

    /**
     * Schedule a new session (increment scheduled count)
     */
    public function scheduleSession(): self
    {
        $this->increment('total_sessions_scheduled');

        return $this;
    }

    /**
     * Record session attendance
     */
    public function recordSessionAttendance(bool $attended): self
    {
        if ($attended) {
            $this->increment('total_sessions_completed');
        } else {
            $this->increment('total_sessions_missed');
        }

        // Update progress percentage based on completion
        $this->updateProgress();

        return $this;
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(): void
    {
        $this->update([
            'progress_percentage' => $this->completion_rate,
        ]);
    }

    /**
     * Use trial session
     */
    public function useTrialSession(): self
    {
        if (! $this->trial_available) {
            throw new Exception('لا توجد جلسة تجريبية متاحة');
        }

        $this->update([
            'trial_session_used' => true,
            'trial_session_date' => now(),
            'trial_session_status' => 'completed',
        ]);

        return $this;
    }

    /**
     * Use a session from the subscription (decrement remaining, increment used)
     * Aligned with QuranSubscription::useSession() pattern
     *
     * @throws Exception If no sessions remaining
     */
    public function useSession(): self
    {
        return DB::transaction(function () {
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception('الاشتراك غير موجود');
            }

            if ($subscription->sessions_remaining <= 0) {
                throw new Exception('لا توجد جلسات متبقية في الاشتراك');
            }

            $subscription->update([
                'sessions_used' => $subscription->sessions_used + 1,
                'sessions_remaining' => $subscription->sessions_remaining - 1,
                'total_sessions_completed' => $subscription->total_sessions_completed + 1,
                'last_session_at' => now(),
            ]);

            // When sessions run out, pause the subscription (awaiting renewal)
            if ($subscription->sessions_remaining <= 0) {
                $subscription->update([
                    'status' => SessionSubscriptionStatus::PAUSED,
                    'progress_percentage' => 100,
                    'paused_at' => now(),
                    'pause_reason' => 'انتهت الجلسات المتاحة - في انتظار التجديد',
                ]);
            }

            $this->refresh();

            return $this;
        });
    }

    /**
     * Return a session to the subscription (reverse of useSession)
     * Called when a session is cancelled after being counted
     */
    public function returnSession(): self
    {
        return DB::transaction(function () {
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception('الاشتراك غير موجود');
            }

            $subscription->update([
                'sessions_used' => max(0, $subscription->sessions_used - 1),
                'sessions_remaining' => $subscription->sessions_remaining + 1,
                'total_sessions_completed' => max(0, $subscription->total_sessions_completed - 1),
            ]);

            // If subscription was paused due to exhaustion, reactivate
            if ($subscription->status === SessionSubscriptionStatus::PAUSED
                && $subscription->pause_reason === 'انتهت الجلسات المتاحة - في انتظار التجديد') {
                $subscription->update([
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'paused_at' => null,
                    'pause_reason' => null,
                ]);
            }

            Log::info("Session returned to Academic subscription {$subscription->id}");

            $this->refresh();

            return $this;
        });
    }

    /**
     * Add sessions to the subscription (for renewals/upgrades)
     * Aligned with QuranSubscription::addSessions() pattern
     *
     * @param  int  $count  Number of sessions to add
     * @param  float|null  $price  Optional price adjustment
     */
    public function addSessions(int $count, ?float $price = null): self
    {
        $this->update([
            'total_sessions' => $this->total_sessions + $count,
            'sessions_remaining' => $this->sessions_remaining + $count,
            'final_price' => ($this->final_price ?? 0) + ($price ?? 0),
        ]);

        return $this;
    }

    /**
     * Extend sessions on renewal (called by HandlesSubscriptionRenewal trait)
     */
    protected function extendSessionsOnRenewal(): void
    {
        // Calculate how many new sessions to create for the new billing cycle
        $sessionsPerMonth = $this->sessions_per_month ?? 8;
        $billingCycleMultiplier = $this->billing_cycle->sessionMultiplier();
        $totalNewSessions = $sessionsPerMonth * $billingCycleMultiplier;

        // Get the current highest session number for this subscription
        $lastSessionNumber = AcademicSession::where('academic_subscription_id', $this->id)
            ->count();

        Log::info('Creating sessions for renewed subscription', [
            'subscription_id' => $this->id,
            'sessions_per_month' => $sessionsPerMonth,
            'billing_cycle' => $this->billing_cycle->value,
            'billing_cycle_multiplier' => $billingCycleMultiplier,
            'total_new_sessions' => $totalNewSessions,
            'starting_from_session' => $lastSessionNumber + 1,
        ]);

        // Create new unscheduled sessions for the renewed period
        for ($i = 1; $i <= $totalNewSessions; $i++) {
            $sessionNumber = $lastSessionNumber + $i;

            AcademicSession::create([
                'academy_id' => $this->academy_id,
                'academic_teacher_id' => $this->teacher_id,
                'academic_subscription_id' => $this->id,
                'student_id' => $this->student_id,
                'session_code' => 'AS-'.$this->id.'-'.str_pad($sessionNumber, 3, '0', STR_PAD_LEFT),
                'session_type' => 'individual',
                'status' => SessionStatus::UNSCHEDULED,
                'title' => "جلسة {$sessionNumber} - {$this->subject_name}",
                'description' => "جلسة في مادة {$this->subject_name} - {$this->grade_level_name}",
                'duration_minutes' => $this->session_duration_minutes ?? 60,
                'created_by' => $this->student_id,
            ]);
        }

        // Update subscription totals using Eloquent increment method
        $this->increment('total_sessions_scheduled', $totalNewSessions);

        Log::info('Renewal session creation complete', [
            'subscription_id' => $this->id,
            'new_sessions_created' => $totalNewSessions,
            'total_sessions_now' => $this->total_sessions_scheduled + $totalNewSessions,
        ]);
    }

    // ========================================
    // PRICING METHODS
    // ========================================

    /**
     * Calculate monthly amount based on sessions and hourly rate
     */
    public function calculateMonthlyAmount(): float
    {
        $sessionsPerMonth = $this->sessions_per_month ?? (($this->sessions_per_week ?? 2) * 4.33);
        $hourlyRate = $this->hourly_rate ?? 0;

        return round($sessionsPerMonth * $hourlyRate, 2);
    }

    // ========================================
    // STATIC FACTORY METHODS
    // ========================================

    /**
     * Create a new subscription with package data snapshot
     */
    public static function createSubscription(array $data): self
    {
        // Generate subscription code
        $data['subscription_code'] = static::generateSubscriptionCode(
            $data['academy_id'],
            'AS'
        );

        // Calculate sessions per month if not set
        if (! isset($data['sessions_per_month']) && isset($data['sessions_per_week'])) {
            $data['sessions_per_month'] = $data['sessions_per_week'] * 4.33;
        }

        // Set defaults
        $data = array_merge([
            'status' => SessionSubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'progress_percentage' => 0,
            'total_sessions_scheduled' => 0,
            'total_sessions_completed' => 0,
            'total_sessions_missed' => 0,
        ], $data);

        // Snapshot subject and grade level names
        if (! empty($data['subject_id']) && empty($data['subject_name'])) {
            $subject = AcademicSubject::find($data['subject_id']);
            $data['subject_name'] = $subject?->name;
        }

        if (! empty($data['grade_level_id']) && empty($data['grade_level_name'])) {
            $gradeLevel = AcademicGradeLevel::find($data['grade_level_id']);
            $data['grade_level_name'] = $gradeLevel?->name;
        }

        $subscription = static::create($data);

        // Snapshot package data if package_id provided
        if (! empty($data['academic_package_id']) && empty($data['package_name_ar'])) {
            $packageData = $subscription->snapshotPackageData();
            if (! empty($packageData)) {
                $subscription->update($packageData);
            }
        }

        return $subscription;
    }

    /**
     * Create a trial subscription
     */
    public static function createTrialSubscription(array $data): self
    {
        return static::createSubscription(array_merge($data, [
            'has_trial_session' => true,
            'trial_session_used' => false,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'final_price' => 0,
        ]));
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function booted()
    {
        parent::boot();

        // Auto-generate subscription code and calculate amounts on creation
        static::creating(function ($subscription) {
            // Sync date fields between old (start_date/end_date) and new (starts_at/ends_at) columns
            // Both columns must be populated for database constraints
            if ($subscription->start_date && ! $subscription->starts_at) {
                $subscription->starts_at = $subscription->start_date;
            } elseif ($subscription->starts_at && ! $subscription->start_date) {
                $subscription->start_date = $subscription->starts_at;
            }

            if ($subscription->end_date && ! $subscription->ends_at) {
                $subscription->ends_at = $subscription->end_date;
            } elseif ($subscription->ends_at && ! $subscription->end_date) {
                $subscription->end_date = $subscription->ends_at;
            }

            // Calculate sessions per month and amounts if not set
            if ($subscription->sessions_per_week && $subscription->hourly_rate) {
                if (! $subscription->sessions_per_month) {
                    $subscription->sessions_per_month = $subscription->sessions_per_week * 4.33;
                }
                if (! $subscription->monthly_price) {
                    $subscription->monthly_price = $subscription->calculateMonthlyAmount();
                }
                if (! $subscription->final_price) {
                    $subscription->final_price = $subscription->monthly_price - ($subscription->discount_amount ?? 0);
                }
            }

            // Set next billing date if not set
            if (! $subscription->next_billing_date && $subscription->starts_at) {
                $subscription->next_billing_date = $subscription->calculateEndDate($subscription->starts_at);
            }
        });

        // CRITICAL FIX: DO NOT send activation notification on creation!
        // Notifications are ONLY sent from activateFromPayment() after payment verification.
        // Sending on creation causes students to receive "subscription activated" notifications
        // immediately when clicking "proceed to payment" button, even if payment fails.
        //
        // static::created(function ($subscription) {
        //     $subscription->notifySubscriptionActivated();
        // });

        // Update progress when session counts change
        static::updated(function ($subscription) {
            if ($subscription->isDirty(['total_sessions_completed', 'total_sessions_scheduled'])) {
                $subscription->updateProgress();
            }

            // Cascade status to lesson
            if ($subscription->isDirty('status')) {
                $subscription->syncLessonStatus();
            }

            // Send notification when subscription is paused
            if ($subscription->isDirty('status') && $subscription->status === SessionSubscriptionStatus::PAUSED) {
                $subscription->notifySubscriptionPaused();
            }
        });
    }

    // ========================================
    // DISPLAY HELPERS
    // ========================================

    /**
     * Get subscription details for display
     */
    public function getSubscriptionDetailsAttribute(): array
    {
        return [
            'subscription_code' => $this->subscription_code,
            'student_name' => $this->student?->name,
            'teacher_name' => $this->teacher_name,
            'subject_name' => $this->subject_name ?? $this->subject?->name,
            'grade_level' => $this->grade_level_name ?? $this->gradeLevel?->name,
            'sessions_per_week' => $this->sessions_per_week,
            'session_duration' => $this->session_duration_minutes,
            'monthly_amount' => $this->formatted_price,
            'currency' => $this->currency,
            'status' => $this->status->label(),
            'payment_status' => $this->payment_status->label(),
            'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
            'days_remaining' => $this->days_remaining,
            'completion_rate' => $this->completion_rate,
            'attendance_rate' => $this->attendance_rate,
            'total_sessions' => $this->total_sessions_scheduled,
            'completed_sessions' => $this->total_sessions_completed,
            'missed_sessions' => $this->total_sessions_missed,
        ];
    }

    /**
     * Send notification when subscription is activated
     */
    public function notifySubscriptionActivated(): void
    {
        try {
            if (! $this->student) {
                return;
            }

            $notificationService = app(NotificationService::class);

            $subscriptionUrl = route('student.academic-subscriptions.show', [
                'subdomain' => $this->academy->subdomain ?? DefaultAcademy::subdomain(),
                'subscriptionId' => $this->id,
            ]);

            // Generate subscription name for notification
            $subscriptionName = $this->package_name_ar
                ?? $this->package?->name
                ?? $this->subject_name
                ?? __('payments.academic_subscription');
            $subscriptionTypeLabel = __('payments.subscription_types.academic');
            $subjectName = $this->subject_name ?? __('payments.academic.subject');

            $notificationService->send(
                $this->student,
                NotificationType::SUBSCRIPTION_ACTIVATED,
                [
                    'subscription_name' => $subscriptionName,
                    'subscription_type' => $subscriptionTypeLabel,
                    'subject_name' => $subjectName,
                    'sessions_per_week' => $this->sessions_per_week,
                    'start_date' => $this->starts_at?->format('Y-m-d'),
                    'end_date' => $this->ends_at?->format('Y-m-d'),
                ],
                $subscriptionUrl,
                [
                    'subscription_id' => $this->id,
                    'subscription_type' => 'academic',
                ],
                false
            );

            // Notify the academic teacher so they can schedule sessions
            if ($this->teacher_id) {
                $teacherUser = $this->teacher?->user;
                if ($teacherUser) {
                    $subdomain = $this->academy->subdomain ?? DefaultAcademy::subdomain();
                    $teacherActionUrl = route('teacher.academic-sessions.index', [
                        'subdomain' => $subdomain,
                    ]);

                    $notificationService->send(
                        $teacherUser,
                        NotificationType::NEW_STUDENT_SUBSCRIPTION_TEACHER,
                        [
                            'student_name' => $this->student->full_name,
                            'subscription_name' => $subscriptionName,
                            'subscription_type' => $subscriptionTypeLabel,
                            'total_sessions' => $this->total_sessions,
                        ],
                        $teacherActionUrl,
                        [
                            'subscription_id' => $this->id,
                            'subscription_type' => 'academic',
                        ],
                        true
                    );
                }
            }

            // Also notify parent if exists
            if ($this->student->studentProfile && $this->student->studentProfile->parent) {
                $notificationService->send(
                    $this->student->studentProfile->parent->user,
                    NotificationType::SUBSCRIPTION_ACTIVATED,
                    [
                        'subscription_name' => $subscriptionName,
                        'student_name' => $this->student->full_name,
                        'subscription_type' => $subscriptionTypeLabel,
                        'subject_name' => $subjectName,
                        'sessions_per_week' => $this->sessions_per_week,
                        'start_date' => $this->starts_at?->format('Y-m-d'),
                        'end_date' => $this->ends_at?->format('Y-m-d'),
                    ],
                    $subscriptionUrl,
                    [
                        'subscription_id' => $this->id,
                        'subscription_type' => 'academic',
                    ],
                    false
                );
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send academic subscription activated notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when subscription is paused
     */
    public function notifySubscriptionPaused(): void
    {
        try {
            if (! $this->student) {
                return;
            }

            $notificationService = app(NotificationService::class);

            $subscriptionUrl = route('student.academic-subscriptions.show', [
                'subdomain' => $this->academy->subdomain ?? DefaultAcademy::subdomain(),
                'subscriptionId' => $this->id,
            ]);

            $notificationService->send(
                $this->student,
                NotificationType::SUBSCRIPTION_EXPIRED,
                [
                    'subscription_type' => 'أكاديمي',
                    'subject_name' => $this->subject_name ?? 'الموضوع',
                    'end_date' => $this->ends_at?->format('Y-m-d'),
                    'total_sessions_completed' => $this->total_sessions_completed,
                    'total_sessions_scheduled' => $this->total_sessions_scheduled,
                ],
                $subscriptionUrl,
                [
                    'subscription_id' => $this->id,
                    'subscription_type' => 'academic',
                ],
                true  // Mark as important
            );

            // Also notify parent if exists
            if ($this->student->studentProfile && $this->student->studentProfile->parent) {
                $notificationService->send(
                    $this->student->studentProfile->parent->user,
                    NotificationType::SUBSCRIPTION_EXPIRED,
                    [
                        'student_name' => $this->student->full_name,
                        'subscription_type' => 'أكاديمي',
                        'subject_name' => $this->subject_name ?? 'الموضوع',
                        'end_date' => $this->ends_at?->format('Y-m-d'),
                        'total_sessions_completed' => $this->total_sessions_completed,
                        'total_sessions_scheduled' => $this->total_sessions_scheduled,
                    ],
                    $subscriptionUrl,
                    [
                        'subscription_id' => $this->id,
                        'subscription_type' => 'academic',
                    ],
                    true
                );
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send academic subscription expired notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ========================================
    // PAYMENT ACTIVATION
    // ========================================

    /**
     * Activate subscription from successful payment.
     *
     * Called by payment webhook after successful payment.
     * Creates the AcademicIndividualLesson and unscheduled sessions,
     * then activates the subscription. This ensures no orphaned
     * lessons/sessions exist for unpaid subscriptions.
     */
    public function activateFromPayment(Payment $payment): void
    {
        DB::transaction(function () {
            $startsAt = $this->starts_at ?? now();
            $endsAt = $this->ends_at ?? $this->calculateEndDate($startsAt);

            // Update subscription status
            $this->update([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => $startsAt,
                'start_date' => $this->start_date ?? $startsAt,
                'ends_at' => $endsAt,
                'end_date' => $this->end_date ?? $endsAt,
                'next_billing_date' => $endsAt,
                'last_payment_date' => now(),
            ]);

            Log::info('[AcademicSubscription] Activated from payment', [
                'subscription_id' => $this->id,
                'subscription_type' => $this->subscription_type,
                'starts_at' => $startsAt->toDateTimeString(),
                'ends_at' => $endsAt->toDateTimeString(),
            ]);

            // Create AcademicIndividualLesson + sessions (only after payment succeeds)
            $this->createLessonAndSessions();

            // Update teacher stats
            if ($this->teacher) {
                $this->teacher->increment('total_students');
            }

            // Send activation notification
            $this->notifySubscriptionActivated();
        });
    }

    /**
     * Create the AcademicIndividualLesson and unscheduled sessions for this subscription.
     *
     * Called from activateFromPayment() after payment is confirmed.
     * This prevents orphaned lessons/sessions for unpaid subscriptions.
     */
    public function createLessonAndSessions(): void
    {
        $subjectName = $this->subject_name ?? 'مادة';
        $gradeLevelName = $this->grade_level_name ?? '';
        $sessionsPerMonth = $this->sessions_per_month ?? 8;

        // Create the AcademicIndividualLesson (container for sessions)
        $lesson = AcademicIndividualLesson::create([
            'academy_id' => $this->academy_id,
            'academic_teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'academic_subscription_id' => $this->id,
            'name' => "دروس {$subjectName} - {$gradeLevelName}",
            'description' => "دروس خصوصية في مادة {$subjectName} للمرحلة {$gradeLevelName}",
            'academic_subject_id' => $this->subject_id,
            'academic_grade_level_id' => $this->grade_level_id,
            'total_sessions' => $sessionsPerMonth,
            'sessions_scheduled' => 0,
            'sessions_completed' => 0,
            'sessions_remaining' => $sessionsPerMonth,
            'default_duration_minutes' => $this->session_duration_minutes ?? 60,
            'preferred_times' => $this->weekly_schedule,
            'status' => LessonStatus::ACTIVE,
            'recording_enabled' => false,
            'created_by' => $this->student_id,
        ]);

        // Calculate total sessions based on billing cycle
        $billingCycleMultiplier = $this->billing_cycle->sessionMultiplier();
        $totalSessions = $sessionsPerMonth * $billingCycleMultiplier;

        // Create unscheduled sessions
        for ($i = 1; $i <= $totalSessions; $i++) {
            AcademicSession::create([
                'academy_id' => $this->academy_id,
                'academic_teacher_id' => $this->teacher_id,
                'academic_subscription_id' => $this->id,
                'academic_individual_lesson_id' => $lesson->id,
                'student_id' => $this->student_id,
                'session_code' => 'AS-'.$this->id.'-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                'session_type' => 'individual',
                'status' => SessionStatus::UNSCHEDULED,
                'title' => "جلسة {$i} - {$subjectName}",
                'description' => "جلسة في مادة {$subjectName} - {$gradeLevelName}",
                'duration_minutes' => $this->session_duration_minutes ?? 60,
                'created_by' => $this->student_id,
            ]);
        }

        Log::info('[AcademicSubscription] Lesson and sessions created', [
            'subscription_id' => $this->id,
            'lesson_id' => $lesson->id,
            'total_sessions' => $totalSessions,
        ]);
    }

    /**
     * Sync lesson status when subscription status changes.
     * Called from the updated observer.
     */
    public function syncLessonStatus(): void
    {
        $lesson = $this->lesson;
        if (! $lesson) {
            return;
        }

        $newStatus = match ($this->status) {
            SessionSubscriptionStatus::ACTIVE => LessonStatus::ACTIVE,
            SessionSubscriptionStatus::CANCELLED, SessionSubscriptionStatus::SUSPENDED => LessonStatus::CANCELLED,
            default => LessonStatus::PENDING,
        };

        $lesson->update(['status' => $newStatus]);
    }

    // ========================================
    // SESSION TRACKING METHODS (BaseSubscription Abstract Methods)
    // ========================================

    /**
     * Get total number of sessions in subscription
     * Uses total_sessions (the authoritative field)
     */
    public function getTotalSessions(): int
    {
        return $this->total_sessions ?? 0;
    }

    /**
     * Get number of sessions used/completed
     * Uses sessions_used (aligned with QuranSubscription pattern)
     */
    public function getSessionsUsed(): int
    {
        return $this->sessions_used ?? 0;
    }

    /**
     * Get number of sessions remaining
     * Uses sessions_remaining (aligned with QuranSubscription pattern)
     */
    public function getSessionsRemaining(): int
    {
        return $this->sessions_remaining ?? 0;
    }
}
