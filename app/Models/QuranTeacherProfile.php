<?php

namespace App\Models;

use App\Models\Traits\HasReviews;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranTeacherProfile extends Model
{
    use HasFactory, HasReviews, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id', // Nullable - will be linked during registration
        'gender',
        'avatar',
        'preview_video',
        'teacher_code',
        'educational_qualification',
        'certifications',
        'teaching_experience_years',
        'available_days',
        'available_time_start',
        'available_time_end',
        'languages',
        'bio_arabic',
        'bio_english',
        'package_ids',
        // Activation fields removed - use User.active_status instead
        'offers_trial_sessions',
        'rating',
        'total_reviews',
        'total_students',
        'total_sessions',
        'session_price_individual',
        'session_price_group',
    ];

    protected $casts = [
        'certifications' => 'array',
        'available_days' => 'array',
        'languages' => 'array',
        'package_ids' => 'array',
        // 'is_active' removed - use User.active_status instead
        'offers_trial_sessions' => 'boolean',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
        'session_price_individual' => 'decimal:2',
        'session_price_group' => 'decimal:2',
        'teaching_experience_years' => 'integer',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
        // 'approved_at' removed - activation handled via User.active_status
    ];

    /**
     * Generate a unique teacher code for the academy
     */
    public static function generateTeacherCode($academyId)
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QT-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

        // Use a simple approach with multiple attempts for concurrent requests
        $maxRetries = 20;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Get the highest existing sequence number for this academy
            // Use withoutGlobalScopes to bypass ScopedToAcademy and SoftDeletes filters
            $maxNumber = static::withoutGlobalScopes()
                ->where('academy_id', $academyId)
                ->where('teacher_code', 'LIKE', $prefix.'%')
                ->selectRaw('MAX(CAST(SUBSTRING(teacher_code, -4) AS UNSIGNED)) as max_num')
                ->value('max_num') ?: 0;

            // Generate next sequence number deterministically
            $nextNumber = $maxNumber + 1 + $attempt;
            $newCode = $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Check if this code already exists (without global scopes)
            if (! static::withoutGlobalScopes()->where('teacher_code', $newCode)->exists()) {
                return $newCode;
            }

            // Add a small delay to reduce contention
            usleep(5000 + ($attempt * 2000)); // 5ms + increasing delay
        }

        // Fallback: use timestamp-based suffix if all retries failed
        $timestamp = substr(str_replace('.', '', microtime(true)), -4);

        return $prefix.$timestamp;
    }

    /**
     * Boot method to auto-generate teacher code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->teacher_code)) {
                $model->teacher_code = static::generateTeacherCode($model->academy_id);
            }
        });

        static::saving(function ($model) {
            if (empty($model->teacher_code)) {
                $model->teacher_code = static::generateTeacherCode($model->academy_id);
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // QuranTeacherProfile -> Academy (direct relationship)
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // approvedBy() relationship removed - activation handled via User.active_status

    public function quranSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'quran_teacher_id');
    }

    public function quranCircles(): HasMany
    {
        return $this->hasMany(QuranCircle::class, 'quran_teacher_id');
    }

    public function trialRequests(): HasMany
    {
        return $this->hasMany(QuranTrialRequest::class, 'teacher_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'quran_teacher_id');
    }

    /**
     * Get the packages this teacher can offer
     * Note: QuranTeacherProfile doesn't have packages - returns empty collection for API compatibility
     */
    public function packages(): \Illuminate\Support\Collection
    {
        return collect();
    }

    /**
     * Get all reviews for this teacher
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(TeacherReview::class, 'reviewable');
    }

    /**
     * Personal Info Accessors - Delegate to User relationship
     * These fields are stored ONLY in the users table (single source of truth)
     */
    public function getFirstNameAttribute(): ?string
    {
        return $this->user?->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->user?->last_name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getPhoneCountryCodeAttribute(): ?string
    {
        return $this->user?->phone_country_code;
    }

    /**
     * Helper Methods
     */
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name.' ('.$this->teacher_code.')';
    }

    /**
     * Calculate estimated monthly salary based on session prices and circles taught
     */
    public function getEstimatedMonthlySalaryAttribute(): float
    {
        $totalMonthly = 0;

        // Calculate from group circles
        $groupCircles = $this->quranCircles()
            ->where('status', true)
            ->where('enrollment_status', '!=', 'closed')
            ->get();

        foreach ($groupCircles as $circle) {
            if ($circle->monthly_sessions_count && $this->session_price_group) {
                $totalMonthly += $circle->monthly_sessions_count * $this->session_price_group;
            }
        }

        // Calculate from individual sessions (if any)
        $individualSessions = $this->quranSessions()
            ->where('session_type', 'individual')
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();

        if ($individualSessions > 0 && $this->session_price_individual) {
            $totalMonthly += $individualSessions * $this->session_price_individual;
        }

        return $totalMonthly;
    }

    /**
     * Get formatted monthly salary
     */
    public function getFormattedMonthlySalaryAttribute(): string
    {
        return number_format($this->estimated_monthly_salary, 2).' ريال';
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return ! is_null($this->user_id);
    }

    /**
     * Check if teacher is active (delegates to User.active_status)
     * This is the SINGLE SOURCE OF TRUTH for activation status
     */
    public function isActive(): bool
    {
        return $this->user?->active_status ?? false;
    }

    /**
     * Scope to get only active teachers (via User.active_status)
     */
    public function scopeActive($query)
    {
        return $query->whereHas('user', fn ($q) => $q->where('active_status', true));
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeLinked($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }
}
