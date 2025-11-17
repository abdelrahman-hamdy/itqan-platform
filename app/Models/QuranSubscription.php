<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'quran_teacher_id',
        'package_id',
        'subscription_code',
        'subscription_type',
        'total_sessions',
        'sessions_used',
        'sessions_remaining',
        'total_price',
        'discount_amount',
        'final_price',
        'currency',
        'billing_cycle',
        'payment_status',
        'subscription_status',
        'trial_used',
        'is_trial_active',
        'current_surah',
        'current_verse',
        'verses_memorized',
        'memorization_level',
        'progress_percentage',
        'last_session_at',
        'starts_at',
        'paused_at',
        'pause_reason',
        'cancelled_at',
        'cancellation_reason',
        'auto_renew',
        'next_payment_at',
        'last_payment_at',
        'rating',
        'review_text',
        'reviewed_at',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'sessions_used' => 'integer',
        'sessions_remaining' => 'integer',
        'trial_used' => 'integer',
        'verses_memorized' => 'integer',
        'progress_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_trial_active' => 'boolean',
        'auto_renew' => 'boolean',
        'rating' => 'integer',
        'starts_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_session_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Constants
    const BILLING_CYCLES = [
        'monthly' => 'شهرية',
        'quarterly' => 'ربع سنوية (ثلاثة أشهر)',
        'yearly' => 'سنوية',
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

    // Real user relationship - renamed to avoid conflict with accessor
    public function quranTeacherUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    // Accessor that works with the quranTeacher.user pattern
    public function getQuranTeacherAttribute()
    {
        // Debug: check if accessor is being called
        \Log::info('QuranSubscription getQuranTeacherAttribute called for ID: '.$this->id);

        if (! $this->quran_teacher_id) {
            return null;
        }

        // Get the user directly
        $user = \App\Models\User::find($this->quran_teacher_id);
        if (! $user) {
            return null;
        }

        // Get teacher profile if exists
        $teacherProfile = $user->quranTeacherProfile;

        // Return an object that has both user properties and the user relationship
        $result = (object) [
            'id' => $teacherProfile ? $teacherProfile->id : $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'full_name' => $user->name,
            'user' => $user,
            // Add other properties that views might expect
            'teaching_experience_years' => $teacherProfile->teaching_experience_years ?? null,
            'bio_arabic' => $teacherProfile->bio_arabic ?? null,
            'bio_english' => $teacherProfile->bio_english ?? null,
            'educational_qualification' => $teacherProfile->educational_qualification ?? null,
            'avatar' => $user->avatar,
            'teacher_code' => $teacherProfile->teacher_code ?? null,
            'rating' => $teacherProfile->rating ?? 0,
            'total_students' => $teacherProfile->total_students ?? 0,
            'total_sessions' => $teacherProfile->total_sessions ?? 0,
        ];

        \Log::info('QuranSubscription accessor returning object with user property');

        return $result;
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(QuranPackage::class, 'package_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'quran_subscription_id');
    }

    public function individualCircle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuranIndividualCircle::class, 'subscription_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuranProgress::class);
    }

    public function homework(): HasMany
    {
        return $this->hasMany(QuranHomework::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('subscription_status', 'expired');
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('subscription_status', 'active');
    }

    public function scopeInTrial($query)
    {
        return $query->where('is_trial_active', true);
    }

    public function scopeTrialExpired($query)
    {
        return $query->where('is_trial_active', true)
            ->whereRaw('trial_used >= 2'); // Assuming default trial sessions is 2
    }

    public function scopePaused($query)
    {
        return $query->where('subscription_status', 'paused');
    }

    public function scopeCancelled($query)
    {
        return $query->where('subscription_status', 'cancelled');
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByPackageType($query, $type)
    {
        return $query->where('package_type', $type);
    }

    public function scopeNeedsRenewal($query)
    {
        return $query->where('sessions_remaining', '<=', 3)
            ->where('subscription_status', 'active');
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => 'نشط',
            'expired' => 'منتهي الصلاحية',
            'paused' => 'متوقف مؤقتاً',
            'cancelled' => 'ملغي',
            'pending' => 'في الانتظار',
            'suspended' => 'معلق',
        ];

        return $statuses[$this->subscription_status] ?? $this->subscription_status;
    }

    public function getPaymentStatusTextAttribute(): string
    {
        $statuses = [
            'paid' => 'مدفوع',
            'pending' => 'في انتظار الدفع',
            'failed' => 'فشل الدفع',
            'refunded' => 'مسترد',
            'cancelled' => 'ملغي',
        ];

        return $statuses[$this->payment_status] ?? $this->payment_status;
    }

    public function getPackageTypeTextAttribute(): string
    {
        $types = [
            'basic' => 'الباقة الأساسية',
            'standard' => 'الباقة المعيارية',
            'premium' => 'الباقة المميزة',
            'intensive' => 'الباقة المكثفة',
            'custom' => 'باقة مخصصة',
        ];

        return $types[$this->package_type] ?? $this->package_type;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->total_price, 2).' '.$this->currency;
    }

    public function getFormattedPricePerSessionAttribute(): string
    {
        return number_format((float) $this->price_per_session, 2).' '.$this->currency;
    }

    public function getCurrentSurahFormattedAttribute(): string
    {
        if (! $this->current_surah) {
            return 'لم يتم تحديد السورة بعد';
        }

        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
            // ... add all 114 surahs
        ];

        return $surahNames[$this->current_surah] ?? 'سورة رقم '.$this->current_surah;
    }

    public function getMemorizationLevelTextAttribute(): string
    {
        $levels = [
            'beginner' => 'مبتدئ',
            'elementary' => 'أساسي',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            'expert' => 'متقن',
            'hafiz' => 'حافظ',
        ];

        return $levels[$this->memorization_level] ?? $this->memorization_level;
    }

    public function getDaysRemainingAttribute(): int
    {
        return -1; // Unlimited since we removed expires_at
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->subscription_status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->subscription_status === 'expired';
    }

    public function getIsInTrialAttribute(): bool
    {
        return $this->is_trial_active && $this->trial_used < 2;
    }

    public function getCanRenewAttribute(): bool
    {
        return in_array($this->subscription_status, ['active', 'expired']) &&
               $this->sessions_remaining <= 5;
    }

    public function getCompletionRateAttribute(): float
    {
        if ($this->total_sessions <= 0) {
            return 0;
        }

        return ($this->sessions_used / $this->total_sessions) * 100;
    }

    // Methods
    public function useSession(): self
    {
        return \DB::transaction(function () {
            // Lock the row for update to prevent race conditions
            $subscription = self::lockForUpdate()->find($this->id);

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
                $subscription->markAsCompleted();
            }

            // Refresh the current instance
            $this->refresh();

            return $this;
        });
    }

    public function useTrialSession(): self
    {
        return \DB::transaction(function () {
            // Lock the row for update to prevent race conditions
            $subscription = self::lockForUpdate()->find($this->id);

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

            // Check if trial should be deactivated
            if ($subscription->trial_used >= 2) {
                $subscription->update(['is_trial_active' => false]);
            }

            // Refresh the current instance
            $this->refresh();

            return $this;
        });
    }

    public function addSessions(int $sessionsCount, ?float $price = null): self
    {
        $this->update([
            'total_sessions' => $this->total_sessions + $sessionsCount,
            'sessions_remaining' => $this->sessions_remaining + $sessionsCount,
            'total_price' => $this->total_price + ($price ?? ($sessionsCount * $this->price_per_session)),
        ]);

        return $this;
    }

    public function pause(?string $reason = null): self
    {
        $this->update([
            'subscription_status' => 'paused',
            'paused_at' => now(),
            'pause_reason' => $reason,
        ]);

        return $this;
    }

    public function resume(): self
    {
        $this->update([
            'subscription_status' => 'active',
            'paused_at' => null,
            'pause_reason' => null,
        ]);

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'subscription_status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'subscription_status' => 'completed',
            'auto_renew' => false,
        ]);

        return $this;
    }

    public function renew(array $newPackageData = []): self
    {
        $renewalData = array_merge([
            'sessions_used' => 0,
            'sessions_remaining' => $newPackageData['total_sessions'] ?? $this->total_sessions,
            'total_sessions' => $newPackageData['total_sessions'] ?? $this->total_sessions,
            'total_price' => $newPackageData['total_price'] ?? $this->total_price,
            'subscription_status' => 'active',
            'starts_at' => now(),
            'last_payment_at' => now(),
            'next_payment_at' => $this->billing_cycle === 'monthly' ? now()->addMonth() : null,
        ], $newPackageData);

        $this->update($renewalData);

        return $this;
    }

    public function updateProgress(?int $currentVerse = null, ?int $versesMemorized = null, ?float $progressPercentage = null): self
    {
        $updateData = [];

        if ($currentVerse !== null) {
            $updateData['current_verse'] = $currentVerse;
        }

        if ($versesMemorized !== null) {
            $updateData['verses_memorized'] = $versesMemorized;
        }

        if ($progressPercentage !== null) {
            $updateData['progress_percentage'] = min(100, max(0, $progressPercentage));
        }

        if (! empty($updateData)) {
            $this->update($updateData);
        }

        return $this;
    }

    public function addRating(int $rating, ?string $reviewText = null): self
    {
        $this->update([
            'rating' => $rating,
            'review_text' => $reviewText,
            'reviewed_at' => now(),
        ]);

        // Update teacher's rating
        $this->quranTeacher->updateRating();

        return $this;
    }

    public function calculateNextPayment(): ?\Carbon\Carbon
    {
        if (! $this->auto_renew || $this->subscription_status !== 'active') {
            return null;
        }

        return match ($this->billing_cycle) {
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => null
        };
    }

    public function extendExpiry(int $days): self
    {
        // Since expires_at column was removed, this method is no longer applicable
        // Subscriptions are now managed by status and billing cycle instead of expiry dates
        return $this;
    }

    // Static methods
    public static function createSubscription(array $data): self
    {
        $subscription = self::create(array_merge($data, [
            'subscription_code' => self::generateSubscriptionCode($data['academy_id']),
            'sessions_used' => 0,
            'trial_used' => 0,
            'progress_percentage' => 0,
            'verses_memorized' => 0,
            'subscription_status' => 'pending',
            'payment_status' => 'pending',
        ]));

        return $subscription;
    }

    public static function createTrialSubscription(array $data): self
    {
        return self::createSubscription(array_merge($data, [
            'is_trial_active' => true,
            'subscription_status' => 'active',
            'payment_status' => 'free',
            'total_price' => 0,
            'price_per_session' => 0,
        ]));
    }

    public static function generateSubscriptionCode(int $academyId): string
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QS-'.$academyId.'-';

        // Use a retry approach with randomization for concurrent requests
        $maxRetries = 20;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Get the highest existing sequence number for this academy (including soft deleted)
            $maxNumber = static::withTrashed()
                ->where('academy_id', $academyId)
                ->where('subscription_code', 'LIKE', $prefix.'%')
                ->selectRaw('MAX(CAST(SUBSTRING(subscription_code, -6) AS UNSIGNED)) as max_num')
                ->value('max_num') ?: 0;

            // Generate next sequence number (add random offset for concurrent requests)
            $nextNumber = $maxNumber + 1 + $attempt + mt_rand(0, 5);
            $newCode = $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Check if this code already exists (including soft deleted)
            if (! static::withTrashed()->where('subscription_code', $newCode)->exists()) {
                return $newCode;
            }

            // Add a small delay to reduce contention
            usleep(5000 + ($attempt * 2000)); // 5ms + increasing delay
        }

        // Fallback: use timestamp-based suffix if all retries failed
        $timestamp = substr(str_replace('.', '', microtime(true)), -6);

        return $prefix.$timestamp;
    }

    public static function getExpiringSoon(int $academyId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->expiringSoon($days)
            ->with(['student', 'quranTeacher.user'])
            ->get();
    }

    public static function getRenewalCandidates(int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->needsRenewal()
            ->with(['student', 'quranTeacher.user'])
            ->get();
    }

    /**
     * Check if student already has an active individual subscription with this teacher
     */
    public static function hasActiveIndividualSubscription($studentId, $teacherId, $academyId): bool
    {
        return static::where('student_id', $studentId)
            ->where('quran_teacher_id', $teacherId)
            ->where('academy_id', $academyId)
            ->where('subscription_type', 'individual')
            ->where('subscription_status', 'active')
            ->exists();
    }

    /**
     * Create individual circle for individual subscriptions
     */
    public function createIndividualCircle(): ?QuranIndividualCircle
    {
        // Only create for individual subscriptions
        if ($this->subscription_type !== 'individual') {
            return null;
        }

        // Don't create if already exists
        if ($this->individualCircle) {
            return $this->individualCircle;
        }

        // Ensure we have required data
        if (! $this->quran_teacher_id || ! $this->student_id || ! $this->package) {
            return null;
        }

        // Get the teacher user ID directly (workaround for relationship caching issue)
        $teacherUserId = $this->quran_teacher_id; // Already the user ID since we fixed the foreign key
        $teacherUser = \App\Models\User::find($teacherUserId);
        if (! $teacherUser || $teacherUser->user_type !== 'quran_teacher') {
            throw new \Exception('Valid teacher user not found');
        }

        $circle = QuranIndividualCircle::create([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $teacherUserId, // Use user ID, not teacher profile ID
            'student_id' => $this->student_id,
            'subscription_id' => $this->id,
            'specialization' => $this->package->specialization ?? 'memorization',
            'memorization_level' => $this->memorization_level ?? 'beginner',
            'total_sessions' => $this->total_sessions,
            'sessions_remaining' => $this->total_sessions,
            'default_duration_minutes' => $this->package->session_duration_minutes ?? 45,
            'status' => $this->subscription_status === 'active' ? 'active' : 'pending',
            'created_by' => $this->created_by,
        ]);

        return $circle;
    }

    /**
     * Update individual circle when subscription is updated
     */
    public function updateIndividualCircle(): void
    {
        if ($this->subscription_type === 'individual' && $this->individualCircle) {
            $this->individualCircle->update([
                'total_sessions' => $this->total_sessions,
                'sessions_remaining' => $this->total_sessions - $this->individualCircle->sessions_completed,
                'status' => $this->subscription_status === 'active' ? 'active' :
                           ($this->subscription_status === 'cancelled' ? 'cancelled' : 'pending'),
            ]);
        }
    }

    // Boot method to handle model events
    protected static function booted()
    {
        // Validate before creating individual subscription
        static::creating(function ($subscription) {
            if ($subscription->subscription_type === 'individual') {
                // Check for existing active individual subscription with same teacher
                if (static::hasActiveIndividualSubscription(
                    $subscription->student_id,
                    $subscription->quran_teacher_id,
                    $subscription->academy_id
                )) {
                    throw new \Exception('لديك اشتراك فردي نشط بالفعل مع هذا المعلم');
                }
            }
        });

        // Validate before updating subscription status
        static::updating(function ($subscription) {
            if ($subscription->subscription_type === 'individual' &&
                $subscription->isDirty('subscription_status') &&
                $subscription->subscription_status === 'active') {

                // Check for existing active individual subscription with same teacher
                if (static::hasActiveIndividualSubscription(
                    $subscription->student_id,
                    $subscription->quran_teacher_id,
                    $subscription->academy_id
                )) {
                    throw new \Exception('لديك اشتراك فردي نشط بالفعل مع هذا المعلم');
                }
            }
        });

        // Create individual circle after subscription is created
        static::created(function ($subscription) {
            if ($subscription->subscription_type === 'individual') {
                $circle = $subscription->createIndividualCircle();

                // If subscription is already active, ensure circle is also active
                if ($circle && $subscription->subscription_status === 'active' && $circle->status !== 'active') {
                    $circle->update(['status' => 'active']);
                }
            }
        });

        // Update individual circle when subscription status changes
        static::updated(function ($subscription) {
            if ($subscription->subscription_type === 'individual' &&
                $subscription->isDirty(['subscription_status', 'total_sessions'])) {
                $subscription->updateIndividualCircle();
            }
        });

        // Update sessions remaining when sessions are used
        static::updating(function ($subscription) {
            if ($subscription->isDirty('sessions_used')) {
                $subscription->sessions_remaining = $subscription->total_sessions - $subscription->sessions_used;
            }
        });

        // Handle subscription deletion - clean up individual circles
        static::deleting(function ($subscription) {
            if ($subscription->subscription_type === 'individual' && $subscription->individualCircle) {
                // Delete the individual circle and its sessions (cascade will handle sessions)
                $subscription->individualCircle->delete();
            }
        });

        // Handle force deletion (hard delete)
        static::forceDeleting(function ($subscription) {
            if ($subscription->subscription_type === 'individual') {
                // Force delete individual circle and its sessions
                $subscription->individualCircle()?->forceDelete();
            }
        });
    }
}
