<?php

namespace App\Models;

use App\Constants\DefaultAcademy;
use App\Enums\BillingCycle;
use App\Enums\NotificationType;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Models\Traits\HandlesSubscriptionRenewal;
use App\Models\Traits\PreventsDuplicatePendingSubscriptions;
use App\Services\CircleEnrollmentService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QuranSubscription Model
 *
 * Handles subscriptions for Quran memorization programs (individual and group).
 * Extends BaseSubscription for common functionality and uses HandlesSubscriptionRenewal
 * for auto-renewal capabilities.
 *
 * KEY CONCEPTS:
 * - Session-based: Subscriptions track total_sessions_scheduled/completed/missed
 * - Individual vs Group: subscription_type determines the circle type
 * - Self-contained: Package data is snapshotted at creation (no dependency on QuranPackage after creation)
 * - Auto-renewal: Enabled by default with NO grace period on payment failure
 *
 * SUBSCRIPTION TYPES:
 * - 'individual': 1-to-1 sessions with teacher (creates QuranIndividualCircle)
 * - 'circle': Group sessions in a QuranCircle
 *
 * @property int|null $quran_teacher_id
 * @property int|null $package_id
 * @property string $subscription_type
 * @property int $total_sessions
 * @property int $total_sessions_scheduled
 * @property int $total_sessions_completed
 * @property int $total_sessions_missed
 * @property int $sessions_used (legacy - use total_sessions_completed)
 * @property int $sessions_remaining
 * @property int|null $trial_used
 * @property bool $is_trial_active
 * @property string|null $memorization_level
 * @property int|null $quran_circle_id
 * @property Carbon|null $starts_at (from BaseSubscription)
 * @property Carbon|null $ends_at (from BaseSubscription)
 */
class QuranSubscription extends BaseSubscription
{
    use HandlesSubscriptionRenewal;
    use PreventsDuplicatePendingSubscriptions;

    /**
     * The database table for this model
     */
    protected $table = 'quran_subscriptions';

    /**
     * Get the fields that identify a unique subscription combination.
     * For Quran subscriptions: teacher + package.
     *
     * @return array<string>
     */
    protected function getDuplicateKeyFields(): array
    {
        return ['quran_teacher_id', 'package_id'];
    }

    /**
     * Get the "pending" status value for Quran subscriptions.
     */
    protected function getPendingStatus(): mixed
    {
        return SessionSubscriptionStatus::PENDING;
    }

    /**
     * Get the "active" status value for Quran subscriptions.
     */
    protected function getActiveStatus(): mixed
    {
        return SessionSubscriptionStatus::ACTIVE;
    }

    /**
     * Get the "cancelled" status value for Quran subscriptions.
     */
    protected function getCancelledStatus(): mixed
    {
        return SessionSubscriptionStatus::CANCELLED;
    }

    /**
     * Quran-specific fillable fields
     * Merged with BaseSubscription::$baseFillable in constructor
     */
    protected static $quranFillable = [
        // Teacher reference
        'quran_teacher_id',

        // Package reference
        'package_id',

        // Subscription type
        'subscription_type',

        // Session tracking (unified with Academic pattern)
        'total_sessions',
        'total_sessions_scheduled',
        'total_sessions_completed',
        'total_sessions_missed',
        'sessions_used',        // Legacy - kept for backwards compatibility
        'sessions_remaining',   // Legacy - kept for backwards compatibility

        // Pricing (single field, no over-engineering)
        'total_price',

        // Trial system
        'trial_used',
        'is_trial_active',

        // Pause support
        'paused_at',
        'pause_reason',

        // Split notes (admin vs supervisor)
        'admin_notes',
        'supervisor_notes',

        // Polymorphic education unit reference (decoupled architecture)
        'education_unit_id',
        'education_unit_type',

        // Student preferences (for individual subscriptions - same as AcademicSubscription)
        'weekly_schedule',
        'student_notes',
        'learning_goals',
        'preferred_times',
    ];

    /**
     * Constructor: Merge fillable with parent's baseFillable
     */
    public function __construct(array $attributes = [])
    {
        // Call parent first (sets base fillable)
        parent::__construct($attributes);

        // Then merge Quran-specific fillable with what parent set
        $this->fillable = array_merge($this->fillable, static::$quranFillable);
    }

    /**
     * Get casts: Merge Quran-specific casts with parent casts
     * IMPORTANT: Do NOT define protected $casts - it would override parent's casts
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Session counts (unified with Academic pattern)
            'total_sessions' => 'integer',
            'total_sessions_scheduled' => 'integer',
            'total_sessions_completed' => 'integer',
            'total_sessions_missed' => 'integer',
            'sessions_used' => 'integer',        // Legacy
            'sessions_remaining' => 'integer',   // Legacy
            'trial_used' => 'integer',

            // Booleans
            'is_trial_active' => 'boolean',

            // Pause support
            'paused_at' => 'datetime',

            // Student preferences (JSON fields - same as AcademicSubscription)
            'weekly_schedule' => 'array',
            'learning_goals' => 'array',
            'preferred_times' => 'array',
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
        'certificate_issued' => false,
        'subscription_type' => 'individual',
        'total_sessions_scheduled' => 0,
        'total_sessions_completed' => 0,
        'total_sessions_missed' => 0,
        'sessions_used' => 0,
        'sessions_remaining' => 0,
        'trial_used' => 0,
        'is_trial_active' => false,
    ];

    // ========================================
    // CONSTANTS
    // ========================================

    const SUBSCRIPTION_TYPE_INDIVIDUAL = 'individual';

    const SUBSCRIPTION_TYPE_CIRCLE = 'group';

    const SUBSCRIPTION_TYPE_GROUP = 'group'; // Alias for SUBSCRIPTION_TYPE_CIRCLE

    const MEMORIZATION_LEVELS = [
        'beginner' => 'مبتدئ',
        'elementary' => 'أساسي',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
        'expert' => 'متقن',
        'hafiz' => 'حافظ',
    ];

    // ========================================
    // RELATIONSHIPS (Quran-specific)
    // ========================================

    /**
     * Get the Quran teacher (direct user relationship)
     */
    public function quranTeacherUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    /**
     * Get the Quran teacher profile relationship (for eager loading)
     */
    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class, 'quran_teacher_id', 'user_id');
    }

    /**
     * Get the original package (for reference only - data is snapshotted)
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(QuranPackage::class, 'package_id');
    }

    /**
     * Get all Quran sessions for this subscription
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'quran_subscription_id');
    }

    /**
     * Get the individual circle (for individual subscriptions)
     */
    public function individualCircle(): HasOne
    {
        return $this->hasOne(QuranIndividualCircle::class, 'subscription_id');
    }

    /**
     * Get the Quran circle (for group subscriptions)
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }

    /**
     * Alias for quranCircle (for API compatibility)
     */
    public function circle(): BelongsTo
    {
        return $this->quranCircle();
    }

    /**
     * Polymorphic relationship to the education unit (circle or individual circle)
     *
     * This is the NEW decoupled architecture relationship.
     * Education units can exist independently from subscriptions.
     */
    public function educationUnit(): MorphTo
    {
        return $this->morphTo('education_unit');
    }

    /**
     * Get the education unit (alias for polymorphic relationship)
     * Returns either QuranIndividualCircle or QuranCircle based on education_unit_type
     */
    public function getEducationUnitModelAttribute()
    {
        // Use the polymorphic relationship if set
        if ($this->education_unit_id && $this->education_unit_type) {
            return $this->educationUnit()->first();
        }

        // Fallback to old relationships for backward compatibility
        if ($this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
            return $this->individualCircle;
        }

        return $this->quranCircle;
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
        return 'quran';
    }

    /**
     * Get subscription type label
     */
    public function getSubscriptionTypeLabel(): string
    {
        return $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL
            ? 'اشتراك قرآن فردي'
            : 'اشتراك حلقة قرآن';
    }

    /**
     * Get subscription title for display
     */
    public function getSubscriptionTitle(): string
    {
        if ($this->package_name_ar) {
            return $this->package_name_ar;
        }

        $teacherName = $this->quranTeacher?->name ?? 'معلم القرآن';

        return $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL
            ? "جلسات فردية مع {$teacherName}"
            : 'حلقة قرآنية';
    }

    /**
     * Get the teacher for this subscription
     */
    public function getTeacher(): ?User
    {
        return $this->quran_teacher_id ? User::find($this->quran_teacher_id) : null;
    }

    /**
     * Calculate renewal price based on billing cycle
     */
    public function calculateRenewalPrice(): float
    {
        return $this->getPriceForBillingCycle();
    }

    /**
     * Snapshot package data to subscription (self-containment)
     */
    public function snapshotPackageData(): array
    {
        $package = $this->package;

        if (! $package) {
            return [];
        }

        return [
            'package_name_ar' => $package->name,
            'package_name_en' => $package->name,
            'package_description_ar' => $package->description,
            'package_description_en' => $package->description,
            'package_features' => $package->features ?? [],
            'sessions_per_month' => $package->sessions_per_month,
            'session_duration_minutes' => $package->session_duration_minutes ?? 45,
            'monthly_price' => $package->monthly_price ?? $package->price,
            'quarterly_price' => $package->quarterly_price ?? (($package->monthly_price ?? $package->price) * 3 * 0.9),
            'yearly_price' => $package->yearly_price ?? (($package->monthly_price ?? $package->price) * 12 * 0.8),
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
    // QURAN-SPECIFIC SCOPES
    // ========================================

    /**
     * Scope: Get individual subscriptions
     */
    public function scopeIndividual($query)
    {
        return $query->where('subscription_type', self::SUBSCRIPTION_TYPE_INDIVIDUAL);
    }

    /**
     * Scope: Get circle (group) subscriptions
     */
    public function scopeCircle($query)
    {
        return $query->where('subscription_type', self::SUBSCRIPTION_TYPE_CIRCLE);
    }

    /**
     * Scope: Get trial subscriptions
     */
    public function scopeInTrial($query)
    {
        return $query->where('is_trial_active', true);
    }

    /**
     * Scope: Get subscriptions needing renewal (low sessions)
     */
    public function scopeNeedsSessionRenewal($query, int $threshold = 3)
    {
        return $query->where('sessions_remaining', '<=', $threshold)
            ->where('status', SessionSubscriptionStatus::ACTIVE);
    }

    /**
     * Scope: Get subscriptions by teacher
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    // ========================================
    // QURAN-SPECIFIC ACCESSORS
    // ========================================

    /**
     * Get memorization level label
     */
    public function getMemorizationLevelLabelAttribute(): string
    {
        return self::MEMORIZATION_LEVELS[$this->memorization_level] ?? $this->memorization_level ?? 'غير محدد';
    }

    /**
     * Get completion rate (sessions used / total sessions)
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_sessions <= 0) {
            return 0;
        }

        return round(($this->sessions_used / $this->total_sessions) * 100, 2);
    }

    /**
     * Check if in trial mode
     */
    public function getIsInTrialAttribute(): bool
    {
        return $this->is_trial_active && ($this->trial_used ?? 0) < 2;
    }

    /**
     * Get price per session
     */
    public function getPricePerSessionAttribute(): float
    {
        if (! $this->total_sessions || $this->total_sessions <= 0) {
            return 0;
        }

        $price = $this->final_price ?? $this->getPriceForBillingCycle();

        return round($price / $this->total_sessions, 2);
    }

    // ========================================
    // SESSION MANAGEMENT METHODS
    // ========================================

    /**
     * Use a session from the subscription
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

            Log::info("Session returned to Quran subscription {$subscription->id}");

            $this->refresh();

            return $this;
        });
    }

    /**
     * Use a trial session
     */
    public function useTrialSession(): self
    {
        return DB::transaction(function () {
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception('الاشتراك غير موجود');
            }

            if (! $subscription->is_trial_active || $subscription->trial_used >= 2) {
                throw new Exception('لا توجد جلسات تجريبية متبقية');
            }

            $subscription->update([
                'trial_used' => $subscription->trial_used + 1,
                'last_session_at' => now(),
            ]);

            // Deactivate trial if limit reached
            if ($subscription->trial_used >= 2) {
                $subscription->update(['is_trial_active' => false]);
            }

            $this->refresh();

            return $this;
        });
    }

    /**
     * Add sessions to subscription (for renewals or upgrades)
     */
    public function addSessions(int $count, ?float $price = null): self
    {
        $this->update([
            'total_sessions' => $this->total_sessions + $count,
            'sessions_remaining' => $this->sessions_remaining + $count,
            'final_price' => ($this->final_price ?? 0) + ($price ?? ($count * $this->price_per_session)),
        ]);

        return $this;
    }

    /**
     * Extend sessions on renewal (called by HandlesSubscriptionRenewal trait)
     */
    protected function extendSessionsOnRenewal(): void
    {
        $sessionsPerMonth = $this->sessions_per_month ?? 4;
        $multiplier = $this->billing_cycle?->sessionMultiplier() ?? 1;
        $newSessions = $sessionsPerMonth * $multiplier;

        $this->update([
            'total_sessions' => $this->total_sessions + $newSessions,
            'sessions_remaining' => $this->sessions_remaining + $newSessions,
        ]);

        // Update individual circle if exists
        $this->updateIndividualCircle();
    }

    // ========================================
    // EDUCATION UNIT MANAGEMENT (Decoupled Architecture)
    // ========================================

    /**
     * Sync education unit status with subscription status
     *
     * This is called when subscription status changes to keep the linked
     * education unit in sync. Works with both new polymorphic relationship
     * and legacy direct relationships.
     */
    public function syncEducationUnitStatus(): void
    {
        // Try polymorphic relationship first (new architecture)
        if ($this->education_unit_id && $this->education_unit_type) {
            $unit = $this->educationUnit;
            if ($unit && method_exists($unit, 'update')) {
                $this->updateEducationUnitFromSubscription($unit);
            }

            return;
        }

        // Fallback to legacy relationships
        if ($this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL && $this->individualCircle) {
            $this->updateEducationUnitFromSubscription($this->individualCircle);
        }
    }

    /**
     * Update education unit fields from subscription
     *
     * @param  QuranIndividualCircle|QuranCircle  $unit
     */
    protected function updateEducationUnitFromSubscription($unit): void
    {
        $updateData = [];

        // QuranIndividualCircle uses is_active boolean, not status column
        if ($this->isDirty('status')) {
            $updateData['is_active'] = $this->status === SessionSubscriptionStatus::ACTIVE;
        }

        // Sync session counts for individual circles
        if ($unit instanceof QuranIndividualCircle) {
            if ($this->isDirty('total_sessions')) {
                $updateData['total_sessions'] = $this->total_sessions;
            }
            if ($this->isDirty('sessions_remaining')) {
                $updateData['sessions_remaining'] = $this->sessions_remaining;
            }
        }

        if (! empty($updateData)) {
            $unit->update($updateData);
        }
    }

    /**
     * Link this subscription to an existing education unit
     *
     * @param  QuranIndividualCircle|QuranCircle  $unit
     */
    public function linkToEducationUnit($unit): self
    {
        $this->update([
            'education_unit_id' => $unit->id,
            'education_unit_type' => get_class($unit),
        ]);

        return $this;
    }

    // ========================================
    // LEGACY INDIVIDUAL CIRCLE MANAGEMENT
    // (Kept for backward compatibility - prefer linkToEducationUnit)
    // ========================================

    /**
     * Create individual circle for individual subscriptions
     *
     * @deprecated Use EducationUnitService::createIndividualCircle() instead.
     *             This method is kept for backward compatibility but circles
     *             should be created independently in the decoupled architecture.
     */
    public function createIndividualCircle(): ?QuranIndividualCircle
    {
        if ($this->subscription_type !== self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
            return null;
        }

        if ($this->individualCircle) {
            return $this->individualCircle;
        }

        if (! $this->quran_teacher_id || ! $this->student_id) {
            return null;
        }

        $teacherUser = User::find($this->quran_teacher_id);
        if (! $teacherUser || $teacherUser->user_type !== UserType::QURAN_TEACHER->value) {
            throw new Exception('Valid teacher user not found');
        }

        return QuranIndividualCircle::create([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'student_id' => $this->student_id,
            'subscription_id' => $this->id,
            'specialization' => $this->metadata['specialization'] ?? 'memorization',
            'memorization_level' => $this->memorization_level ?? 'beginner',
            'total_sessions' => $this->total_sessions,
            'sessions_remaining' => $this->sessions_remaining,
            'default_duration_minutes' => $this->session_duration_minutes ?? 45,
            'is_active' => $this->isActive(),
            'created_by' => $this->created_by,
        ]);
    }

    /**
     * Update individual circle when subscription changes
     *
     * @deprecated Use syncEducationUnitStatus() instead.
     *             This method is kept for backward compatibility.
     */
    public function updateIndividualCircle(): void
    {
        if ($this->subscription_type !== self::SUBSCRIPTION_TYPE_INDIVIDUAL || ! $this->individualCircle) {
            return;
        }

        $this->individualCircle->update([
            'total_sessions' => $this->total_sessions,
            'sessions_remaining' => $this->sessions_remaining,
            'is_active' => $this->isActive(),
        ]);
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
            'QS'
        );

        // Set defaults
        $data = array_merge([
            'status' => SessionSubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'sessions_used' => 0,
            'trial_used' => 0,
            'progress_percentage' => 0,
            'is_trial_active' => false,
        ], $data);

        $subscription = static::create($data);

        // Snapshot package data if package_id provided
        if (! empty($data['package_id']) && empty($data['package_name_ar'])) {
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
            'is_trial_active' => true,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'final_price' => 0,
            'total_sessions' => 2, // Trial sessions
            'sessions_remaining' => 2,
        ]));
    }

    /**
     * Check if student has active individual subscription with teacher
     */
    public static function hasActiveIndividualSubscription(int $studentId, int $teacherId, int $academyId): bool
    {
        return static::where('student_id', $studentId)
            ->where('quran_teacher_id', $teacherId)
            ->where('academy_id', $academyId)
            ->where('subscription_type', self::SUBSCRIPTION_TYPE_INDIVIDUAL)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->exists();
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function booted()
    {
        parent::boot();

        // Validate before creating individual subscription
        static::creating(function ($subscription) {
            \Log::info('[QuranSubscription::creating] Status before save', [
                'subscription_type' => $subscription->subscription_type,
                'status' => $subscription->status,
                'status_type' => gettype($subscription->status),
                'payment_status' => $subscription->payment_status,
            ]);

            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
                // Only check for duplicates if all required fields are present
                if ($subscription->student_id && $subscription->quran_teacher_id && $subscription->academy_id) {
                    if (static::hasActiveIndividualSubscription(
                        $subscription->student_id,
                        $subscription->quran_teacher_id,
                        $subscription->academy_id
                    )) {
                        throw new Exception('لديك اشتراك فردي نشط بالفعل مع هذا المعلم');
                    }
                }
            }
        });

        // After subscription created - send notification (NO auto-creation of circles)
        // DECOUPLED ARCHITECTURE: Education units are created independently
        static::created(function ($subscription) {
            \Log::info('[QuranSubscription::created] Subscription saved', [
                'id' => $subscription->id,
                'subscription_type' => $subscription->subscription_type,
                'status' => $subscription->status,
                'payment_status' => $subscription->payment_status,
                'education_unit_id' => $subscription->education_unit_id,
            ]);

            // ONLY send notification if subscription is ACTIVE and PAID (not for pending)
            if ($subscription->status === SessionSubscriptionStatus::ACTIVE
                && $subscription->payment_status === SubscriptionPaymentStatus::PAID) {
                $subscription->notifySubscriptionActivated();
            }
        });

        // Update education unit status when subscription changes (if linked)
        static::updated(function ($subscription) {
            // Sync status to education unit if linked via polymorphic relationship
            if ($subscription->isDirty(['status', 'total_sessions', 'sessions_remaining'])) {
                $subscription->syncEducationUnitStatus();
            }

            // Send notification when subscription is paused (sessions exhausted)
            if ($subscription->isDirty('status') && $subscription->status === SessionSubscriptionStatus::PAUSED) {
                $subscription->notifySubscriptionPaused();
            }
        });

        // Update sessions remaining when sessions are used
        static::updating(function ($subscription) {
            if ($subscription->isDirty('sessions_used') && ! $subscription->isDirty('sessions_remaining')) {
                $subscription->sessions_remaining = $subscription->total_sessions - $subscription->sessions_used;
            }
        });

        // DECOUPLED ARCHITECTURE: NO cascade deletion of circles
        // Education units exist independently from subscriptions
        // Deleting a subscription only unlinks it from the education unit (via SET NULL FK)
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get surah names (Arabic)
     */
    protected function getSurahNames(): array
    {
        return [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
            13 => 'الرعد', 14 => 'إبراهيم', 15 => 'الحجر', 16 => 'النحل',
            17 => 'الإسراء', 18 => 'الكهف', 19 => 'مريم', 20 => 'طه',
            21 => 'الأنبياء', 22 => 'الحج', 23 => 'المؤمنون', 24 => 'النور',
            25 => 'الفرقان', 26 => 'الشعراء', 27 => 'النمل', 28 => 'القصص',
            29 => 'العنكبوت', 30 => 'الروم', 31 => 'لقمان', 32 => 'السجدة',
            33 => 'الأحزاب', 34 => 'سبأ', 35 => 'فاطر', 36 => 'يس',
            37 => 'الصافات', 38 => 'ص', 39 => 'الزمر', 40 => 'غافر',
            41 => 'فصلت', 42 => 'الشورى', 43 => 'الزخرف', 44 => 'الدخان',
            45 => 'الجاثية', 46 => 'الأحقاف', 47 => 'محمد', 48 => 'الفتح',
            49 => 'الحجرات', 50 => 'ق', 51 => 'الذاريات', 52 => 'الطور',
            53 => 'النجم', 54 => 'القمر', 55 => 'الرحمن', 56 => 'الواقعة',
            57 => 'الحديد', 58 => 'المجادلة', 59 => 'الحشر', 60 => 'الممتحنة',
            61 => 'الصف', 62 => 'الجمعة', 63 => 'المنافقون', 64 => 'التغابن',
            65 => 'الطلاق', 66 => 'التحريم', 67 => 'الملك', 68 => 'القلم',
            69 => 'الحاقة', 70 => 'المعارج', 71 => 'نوح', 72 => 'الجن',
            73 => 'المزمل', 74 => 'المدثر', 75 => 'القيامة', 76 => 'الإنسان',
            77 => 'المرسلات', 78 => 'النبأ', 79 => 'النازعات', 80 => 'عبس',
            81 => 'التكوير', 82 => 'الانفطار', 83 => 'المطففين', 84 => 'الانشقاق',
            85 => 'البروج', 86 => 'الطارق', 87 => 'الأعلى', 88 => 'الغاشية',
            89 => 'الفجر', 90 => 'البلد', 91 => 'الشمس', 92 => 'الليل',
            93 => 'الضحى', 94 => 'الشرح', 95 => 'التين', 96 => 'العلق',
            97 => 'القدر', 98 => 'البينة', 99 => 'الزلزلة', 100 => 'العاديات',
            101 => 'القارعة', 102 => 'التكاثر', 103 => 'العصر', 104 => 'الهمزة',
            105 => 'الفيل', 106 => 'قريش', 107 => 'الماعون', 108 => 'الكوثر',
            109 => 'الكافرون', 110 => 'النصر', 111 => 'المسد', 112 => 'الإخلاص',
            113 => 'الفلق', 114 => 'الناس',
        ];
    }

    /**
     * Activate subscription from successful payment.
     *
     * Called by payment webhook after successful payment.
     * For group subscriptions, this also enrolls the student in the circle.
     */
    public function activateFromPayment(Payment $payment): void
    {
        DB::transaction(function () {
            // Clear grace period metadata on successful payment
            $metadata = $this->metadata ?? [];
            unset(
                $metadata['grace_period_ends_at'],
                $metadata['grace_period_expires_at'],
                $metadata['grace_period_started_at'],
                $metadata['grace_notification_last_sent_at'],
                $metadata['renewal_failed_count'],
                $metadata['last_renewal_failure_at'],
                $metadata['last_renewal_failure_reason']
            );

            // Update subscription status
            $this->update([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => $this->starts_at ?? now(),
                'next_billing_date' => now()->addMonth(),
                'last_payment_date' => now(),
                'metadata' => $metadata ?: null,
            ]);

            // For group subscriptions, enroll student in the circle using CircleEnrollmentService
            \Log::info('[QuranSubscription] Checking for group enrollment', [
                'subscription_id' => $this->id,
                'subscription_type' => $this->subscription_type,
                'education_unit_id' => $this->education_unit_id,
                'is_group' => $this->subscription_type === self::SUBSCRIPTION_TYPE_GROUP,
            ]);

            if ($this->subscription_type === self::SUBSCRIPTION_TYPE_GROUP && $this->education_unit_id) {
                \Log::info('[QuranSubscription] Starting circle enrollment', [
                    'subscription_id' => $this->id,
                ]);

                $enrollmentService = app(CircleEnrollmentService::class);
                $result = $enrollmentService->completeEnrollmentAfterPayment($this);

                \Log::info('[QuranSubscription] Circle enrollment result', [
                    'subscription_id' => $this->id,
                    'success' => $result['success'] ?? false,
                    'message' => $result['message'] ?? $result['error'] ?? 'No message',
                ]);

                if (! $result['success']) {
                    \Log::warning('[QuranSubscription] Failed to complete enrollment after payment', [
                        'subscription_id' => $this->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }

            // For individual subscriptions, create the individual circle
            if ($this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
                \Log::info('[QuranSubscription] Creating individual circle for subscription', [
                    'subscription_id' => $this->id,
                ]);

                try {
                    $circle = $this->createIndividualCircle();

                    \Log::info('[QuranSubscription] Individual circle created', [
                        'subscription_id' => $this->id,
                        'circle_id' => $circle?->id,
                    ]);
                } catch (Exception $e) {
                    \Log::error('[QuranSubscription] Failed to create individual circle', [
                        'subscription_id' => $this->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send activation notification
            $this->notifySubscriptionActivated();
        });
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

            // Generate action URL for the notification
            $subdomain = $this->academy->subdomain ?? DefaultAcademy::subdomain();
            $actionUrl = null;

            // Try to get circle-specific URL first
            if ($this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL && $this->individualCircle?->id) {
                $actionUrl = route('individual-circles.show', [
                    'subdomain' => $subdomain,
                    'circle' => $this->individualCircle->id,
                ]);
            } elseif ($this->quran_circle_id) {
                $actionUrl = route('student.circles.show', [
                    'subdomain' => $subdomain,
                    'circleId' => $this->quran_circle_id,
                ]);
            }

            // Fallback to subscriptions list page if no circle URL available
            if (! $actionUrl) {
                $actionUrl = route('student.subscriptions', [
                    'subdomain' => $subdomain,
                ]);
            }

            // Generate subscription name for notification
            $isIndividual = $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL;
            $subscriptionName = $this->package_name_ar
                ?? $this->package?->name
                ?? ($isIndividual
                    ? __('payments.notifications.quran_individual_subscription')
                    : __('payments.notifications.quran_group_subscription'));
            $subscriptionTypeLabel = $isIndividual
                ? __('payments.subscription_types.individual')
                : __('payments.subscription_types.group');

            $notificationService->send(
                $this->student,
                NotificationType::SUBSCRIPTION_ACTIVATED,
                [
                    'subscription_name' => $subscriptionName,
                    'subscription_type' => $subscriptionTypeLabel,
                    'total_sessions' => $this->total_sessions,
                    'start_date' => $this->starts_at?->format('Y-m-d'),
                    'end_date' => $this->ends_at?->format('Y-m-d'),
                ],
                $actionUrl,
                [
                    'subscription_id' => $this->id,
                    'subscription_type' => $this->subscription_type,
                ],
                false
            );

            // Notify the Quran teacher so they can schedule sessions
            if ($this->quran_teacher_id) {
                $teacherUser = $this->quranTeacherUser;
                if ($teacherUser) {
                    $teacherActionUrl = route('teacher.individual-circles.index', [
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
                            'subscription_type' => $this->subscription_type,
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
                        'total_sessions' => $this->total_sessions,
                        'start_date' => $this->starts_at?->format('Y-m-d'),
                        'end_date' => $this->ends_at?->format('Y-m-d'),
                    ],
                    $actionUrl,
                    [
                        'subscription_id' => $this->id,
                        'subscription_type' => $this->subscription_type,
                    ],
                    false
                );
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send subscription activated notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when subscription is paused (sessions exhausted)
     */
    public function notifySubscriptionPaused(): void
    {
        try {
            if (! $this->student) {
                return;
            }

            $notificationService = app(NotificationService::class);

            // Generate circle URL only if we have a valid circle ID
            $circleUrl = null;
            $subdomain = $this->academy->subdomain ?? DefaultAcademy::subdomain();

            if ($this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL && $this->individualCircle?->id) {
                $circleUrl = route('individual-circles.show', [
                    'subdomain' => $subdomain,
                    'circle' => $this->individualCircle->id,
                ]);
            } elseif ($this->quran_circle_id) {
                $circleUrl = route('student.circles.show', [
                    'subdomain' => $subdomain,
                    'circleId' => $this->quran_circle_id,
                ]);
            }

            $notificationService->send(
                $this->student,
                NotificationType::SUBSCRIPTION_EXPIRED,
                [
                    'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                    'end_date' => $this->ends_at?->format('Y-m-d'),
                    'sessions_used' => $this->sessions_used,
                    'total_sessions' => $this->total_sessions,
                ],
                $circleUrl,
                [
                    'subscription_id' => $this->id,
                    'subscription_type' => $this->subscription_type,
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
                        'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                        'end_date' => $this->ends_at?->format('Y-m-d'),
                        'sessions_used' => $this->sessions_used,
                        'total_sessions' => $this->total_sessions,
                    ],
                    $circleUrl,
                    [
                        'subscription_id' => $this->id,
                        'subscription_type' => $this->subscription_type,
                    ],
                    true
                );
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send subscription expired notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ========================================
    // SESSION TRACKING METHODS (BaseSubscription Abstract Methods)
    // ========================================

    /**
     * Get total number of sessions in subscription
     */
    public function getTotalSessions(): int
    {
        return $this->total_sessions ?? 0;
    }

    /**
     * Get number of sessions used/completed
     */
    public function getSessionsUsed(): int
    {
        return $this->sessions_used ?? 0;
    }

    /**
     * Get number of sessions remaining
     */
    public function getSessionsRemaining(): int
    {
        return $this->sessions_remaining ?? 0;
    }
}
