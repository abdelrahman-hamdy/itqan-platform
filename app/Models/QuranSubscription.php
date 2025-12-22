<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Traits\HandlesSubscriptionRenewal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * QuranSubscription Model
 *
 * Handles subscriptions for Quran memorization programs (individual and group).
 * Extends BaseSubscription for common functionality and uses HandlesSubscriptionRenewal
 * for auto-renewal capabilities.
 *
 * KEY CONCEPTS:
 * - Session-based: Subscriptions track sessions_used/sessions_remaining
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
 * @property int $sessions_used
 * @property int $sessions_remaining
 * @property int|null $trial_used
 * @property bool $is_trial_active
 * @property int|null $current_surah
 * @property string|null $memorization_level
 */
class QuranSubscription extends BaseSubscription
{
    use HandlesSubscriptionRenewal;

    /**
     * The database table for this model
     */
    protected $table = 'quran_subscriptions';

    /**
     * Quran-specific fillable fields
     * Merged with BaseSubscription::$baseFillable in constructor
     */
    protected static $quranFillable = [
        // Teacher reference
        'quran_teacher_id',

        // Package reference (kept for backward compatibility, but data is snapshotted)
        'package_id',

        // Subscription type
        'subscription_type',

        // Session tracking
        'total_sessions',
        'sessions_used',
        'sessions_remaining',

        // Pricing (legacy field in this table)
        'total_price',

        // Trial system
        'trial_used',
        'is_trial_active',

        // Quran-specific progress
        'current_surah',
        'memorization_level',
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
            // Session counts
            'total_sessions' => 'integer',
            'sessions_used' => 'integer',
            'sessions_remaining' => 'integer',
            'trial_used' => 'integer',

            // Booleans
            'is_trial_active' => 'boolean',

            // Quran progress
            'current_surah' => 'integer',
        ]);
    }

    /**
     * Default attributes
     */
    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'currency' => 'SAR',
        'billing_cycle' => 'monthly',
        'auto_renew' => true,
        'progress_percentage' => 0,
        'certificate_issued' => false,
        'subscription_type' => 'individual',
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
     * Get the Quran teacher with profile data as a simple object
     * @deprecated Use quranTeacher relationship instead
     */
    public function getQuranTeacherInfoAttribute()
    {
        if (!$this->quran_teacher_id) {
            return null;
        }

        $user = User::find($this->quran_teacher_id);
        if (!$user) {
            return null;
        }

        $teacherProfile = $user->quranTeacherProfile;

        return (object) [
            'id' => $teacherProfile?->id ?? $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'full_name' => $user->name,
            'user' => $user,
            'teaching_experience_years' => $teacherProfile?->teaching_experience_years,
            'bio_arabic' => $teacherProfile?->bio_arabic,
            'bio_english' => $teacherProfile?->bio_english,
            'educational_qualification' => $teacherProfile?->educational_qualification,
            'avatar' => $user->avatar,
            'teacher_code' => $teacherProfile?->teacher_code,
            'rating' => $teacherProfile?->rating ?? 0,
            'total_students' => $teacherProfile?->total_students ?? 0,
            'total_sessions' => $teacherProfile?->total_sessions ?? 0,
        ];
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
     * Get payment records for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

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
            : "حلقة قرآنية";
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

        if (!$package) {
            return [];
        }

        return [
            'package_name_ar' => $package->name_ar ?? $package->name,
            'package_name_en' => $package->name_en ?? $package->name,
            'package_description_ar' => $package->description_ar ?? $package->description,
            'package_description_en' => $package->description_en ?? $package->description,
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
            ->where('status', SubscriptionStatus::ACTIVE);
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
     * Get formatted current surah name
     */
    public function getCurrentSurahNameAttribute(): string
    {
        if (!$this->current_surah) {
            return 'لم يتم تحديد السورة';
        }

        $surahNames = $this->getSurahNames();

        return $surahNames[$this->current_surah] ?? "سورة رقم {$this->current_surah}";
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
        if (!$this->total_sessions || $this->total_sessions <= 0) {
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

            if (!$subscription) {
                throw new \Exception('الاشتراك غير موجود');
            }

            if ($subscription->sessions_remaining <= 0) {
                throw new \Exception('لا توجد جلسات متبقية في الاشتراك');
            }

            $subscription->update([
                'sessions_used' => $subscription->sessions_used + 1,
                'sessions_remaining' => $subscription->sessions_remaining - 1,
                'last_session_at' => now(),
            ]);

            // Check if subscription should be marked as completed
            if ($subscription->sessions_remaining <= 0) {
                $subscription->update([
                    'status' => SubscriptionStatus::COMPLETED,
                    'progress_percentage' => 100,
                ]);
            }

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

            if (!$subscription) {
                throw new \Exception('الاشتراك غير موجود');
            }

            if (!$subscription->is_trial_active || $subscription->trial_used >= 2) {
                throw new \Exception('لا توجد جلسات تجريبية متبقية');
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
    // INDIVIDUAL CIRCLE MANAGEMENT
    // ========================================

    /**
     * Create individual circle for individual subscriptions
     */
    public function createIndividualCircle(): ?QuranIndividualCircle
    {
        if ($this->subscription_type !== self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
            return null;
        }

        if ($this->individualCircle) {
            return $this->individualCircle;
        }

        if (!$this->quran_teacher_id || !$this->student_id) {
            return null;
        }

        $teacherUser = User::find($this->quran_teacher_id);
        if (!$teacherUser || $teacherUser->user_type !== 'quran_teacher') {
            throw new \Exception('Valid teacher user not found');
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
            'status' => $this->isActive() ? 'active' : 'pending',
            'created_by' => $this->created_by,
        ]);
    }

    /**
     * Update individual circle when subscription changes
     */
    public function updateIndividualCircle(): void
    {
        if ($this->subscription_type !== self::SUBSCRIPTION_TYPE_INDIVIDUAL || !$this->individualCircle) {
            return;
        }

        $circleStatus = match ($this->status) {
            SubscriptionStatus::ACTIVE => 'active',
            SubscriptionStatus::CANCELLED => 'cancelled',
            SubscriptionStatus::COMPLETED => 'completed',
            default => 'pending',
        };

        $this->individualCircle->update([
            'total_sessions' => $this->total_sessions,
            'sessions_remaining' => $this->sessions_remaining,
            'status' => $circleStatus,
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
            'status' => SubscriptionStatus::PENDING,
            'payment_status' => SubscriptionPaymentStatus::PENDING,
            'sessions_used' => 0,
            'trial_used' => 0,
            'progress_percentage' => 0,
            'is_trial_active' => false,
        ], $data);

        $subscription = static::create($data);

        // Snapshot package data if package_id provided
        if (!empty($data['package_id']) && empty($data['package_name_ar'])) {
            $packageData = $subscription->snapshotPackageData();
            if (!empty($packageData)) {
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
            'status' => SubscriptionStatus::ACTIVE,
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
            ->where('status', SubscriptionStatus::ACTIVE)
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
            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
                // Only check for duplicates if all required fields are present
                if ($subscription->student_id && $subscription->quran_teacher_id && $subscription->academy_id) {
                    if (static::hasActiveIndividualSubscription(
                        $subscription->student_id,
                        $subscription->quran_teacher_id,
                        $subscription->academy_id
                    )) {
                        throw new \Exception('لديك اشتراك فردي نشط بالفعل مع هذا المعلم');
                    }
                }
            }
        });

        // Create individual circle after subscription is created
        static::created(function ($subscription) {
            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
                $circle = $subscription->createIndividualCircle();

                if ($circle && $subscription->isActive() && $circle->status !== 'active') {
                    $circle->update(['status' => 'active']);
                }
            }

            // Send activation notification
            $subscription->notifySubscriptionActivated();
        });

        // Update individual circle when subscription changes
        static::updated(function ($subscription) {
            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL &&
                $subscription->isDirty(['status', 'total_sessions', 'sessions_remaining'])) {
                $subscription->updateIndividualCircle();
            }

            // Send notification when subscription expires
            if ($subscription->isDirty('status') && $subscription->status === \App\Enums\SubscriptionStatus::EXPIRED) {
                $subscription->notifySubscriptionExpired();
            }
        });

        // Update sessions remaining when sessions are used
        static::updating(function ($subscription) {
            if ($subscription->isDirty('sessions_used') && !$subscription->isDirty('sessions_remaining')) {
                $subscription->sessions_remaining = $subscription->total_sessions - $subscription->sessions_used;
            }
        });

        // Clean up individual circles on deletion
        static::deleting(function ($subscription) {
            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL && $subscription->individualCircle) {
                $subscription->individualCircle->delete();
            }
        });

        static::forceDeleting(function ($subscription) {
            if ($subscription->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL) {
                $subscription->individualCircle()?->forceDelete();
            }
        });
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
     * Send notification when subscription is activated
     */
    public function notifySubscriptionActivated(): void
    {
        try {
            if (!$this->student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            $circleUrl = $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL
                ? route('individual-circles.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'circle' => $this->individualCircle?->id ?? '',
                ])
                : route('student.circles.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'circleId' => $this->quran_circle_id ?? '',
                ]);

            $notificationService->send(
                $this->student,
                \App\Enums\NotificationType::SUBSCRIPTION_ACTIVATED,
                [
                    'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                    'total_sessions' => $this->total_sessions,
                    'start_date' => $this->start_date?->format('Y-m-d'),
                    'end_date' => $this->end_date?->format('Y-m-d'),
                ],
                $circleUrl,
                [
                    'subscription_id' => $this->id,
                    'subscription_type' => $this->subscription_type,
                ],
                false
            );

            // Also notify parent if exists
            if ($this->student->studentProfile && $this->student->studentProfile->parent) {
                $notificationService->send(
                    $this->student->studentProfile->parent->user,
                    \App\Enums\NotificationType::SUBSCRIPTION_ACTIVATED,
                    [
                        'student_name' => $this->student->full_name,
                        'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                        'total_sessions' => $this->total_sessions,
                        'start_date' => $this->start_date?->format('Y-m-d'),
                        'end_date' => $this->end_date?->format('Y-m-d'),
                    ],
                    $circleUrl,
                    [
                        'subscription_id' => $this->id,
                        'subscription_type' => $this->subscription_type,
                    ],
                    false
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send subscription activated notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when subscription expires
     */
    public function notifySubscriptionExpired(): void
    {
        try {
            if (!$this->student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            $circleUrl = $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL
                ? route('individual-circles.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'circle' => $this->individualCircle?->id ?? '',
                ])
                : route('student.circles.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'circleId' => $this->quran_circle_id ?? '',
                ]);

            $notificationService->send(
                $this->student,
                \App\Enums\NotificationType::SUBSCRIPTION_EXPIRED,
                [
                    'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                    'end_date' => $this->end_date?->format('Y-m-d'),
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
                    \App\Enums\NotificationType::SUBSCRIPTION_EXPIRED,
                    [
                        'student_name' => $this->student->full_name,
                        'subscription_type' => $this->subscription_type === self::SUBSCRIPTION_TYPE_INDIVIDUAL ? 'فردي' : 'جماعي',
                        'end_date' => $this->end_date?->format('Y-m-d'),
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
        } catch (\Exception $e) {
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
     *
     * @return int
     */
    public function getTotalSessions(): int
    {
        return $this->total_sessions ?? 0;
    }

    /**
     * Get number of sessions used/completed
     *
     * @return int
     */
    public function getSessionsUsed(): int
    {
        return $this->sessions_used ?? 0;
    }

    /**
     * Get number of sessions remaining
     *
     * @return int
     */
    public function getSessionsRemaining(): int
    {
        return $this->sessions_remaining ?? 0;
    }
}
