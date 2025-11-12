<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;
use App\Traits\ScopedToAcademy;
use Illuminate\Support\Facades\DB;

class QuranTeacherProfile extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id', // Nullable - will be linked during registration
        'email',
        'first_name',
        'last_name',
        'phone',
        'avatar',
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
        'approval_status', // Added to allow admin to manage approval
        'approved_by',
        'approved_at',
        'is_active',
        'offers_trial_sessions',
        'rating',
        'total_students',
        'total_sessions',
        'session_price_individual',
        'session_price_group',
    ];

    protected $casts = [
        'certifications' => 'array',
        'available_days' => 'array',
        'languages' => 'array',
        'is_active' => 'boolean',
        'offers_trial_sessions' => 'boolean',
        'rating' => 'decimal:2',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
        'session_price_individual' => 'decimal:2',
        'session_price_group' => 'decimal:2',
        'teaching_experience_years' => 'integer',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
        'approved_at' => 'datetime',
    ];

        /**
     * Generate a unique teacher code for the academy
     */
    public static function generateTeacherCode($academyId)
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QT-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-';
        
        // Use a simple approach with multiple attempts for concurrent requests
        $maxRetries = 20;
        
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Get the highest existing sequence number for this academy
            $maxNumber = static::where('academy_id', $academyId)
                ->where('teacher_code', 'LIKE', $prefix . '%')
                ->selectRaw('MAX(CAST(SUBSTRING(teacher_code, -4) AS UNSIGNED)) as max_num')
                ->value('max_num') ?: 0;
            
            // Generate next sequence number (add random offset for concurrent requests)
            $nextNumber = $maxNumber + 1 + $attempt + mt_rand(0, 5);
            $newCode = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Check if this code already exists
            if (!static::where('teacher_code', $newCode)->exists()) {
                return $newCode;
            }
            
            // Add a small delay to reduce contention
            usleep(5000 + ($attempt * 2000)); // 5ms + increasing delay
        }
        
        // Fallback: use timestamp-based suffix if all retries failed
        $timestamp = substr(str_replace('.', '', microtime(true)), -4);
        return $prefix . $timestamp;
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

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
     * Helper Methods
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name . ' (' . $this->teacher_code . ')';
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
        return number_format($this->estimated_monthly_salary, 2) . ' ريال';
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Status Methods - Simplified
     */
    public function isPending(): bool
    {
        return false; // No more pending state - teachers are either active or inactive
    }

    public function isApproved(): bool
    {
        return true; // All teachers in the system are considered approved
    }

    public function isRejected(): bool
    {
        return false; // No more rejected state
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Actions - Simplified
     */
    public function activate(int $activatedBy): void
    {
        $this->update([
            'is_active' => true,
            'approval_status' => 'approved',  // Also approve when activating
            'approved_by' => $activatedBy,
            'approved_at' => now(),
        ]);

        // Also activate the related User account
        if ($this->user) {
            $this->user->update([
                'active_status' => true,
            ]);
        }
    }

    public function deactivate(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
        ]);

        // Also deactivate the related User account
        if ($this->user) {
            $this->user->update([
                'active_status' => false,
            ]);
        }
    }

    public function suspend(?string $reason = null): void
    {
        $this->deactivate($reason);
    }

    // Legacy methods for backward compatibility
    public function approve(int $approvedBy): void
    {
        $this->activate($approvedBy);
    }

    public function reject(int $rejectedBy, ?string $reason = null): void
    {
        $this->deactivate($reason);
    }

    /**
     * Scopes - Simplified
     */
    public function scopeApproved($query)
    {
        return $query; // All teachers in the system are considered approved
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->whereRaw('1=0'); // No more pending state - return empty query
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
