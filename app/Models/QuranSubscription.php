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
        'quran_circle_id',
        'subscription_code',
        'subscription_type',
        'session_type',
        'specialization',
        'recitation_style',
        'memorization_level',
        'sessions_per_week',
        'session_duration_minutes',
        'preferred_schedule',
        'price_per_session',
        'monthly_fee',
        'currency',
        'payment_method',
        'status',
        'trial_sessions',
        'trial_used',
        'total_sessions',
        'completed_sessions',
        'missed_sessions',
        'makeup_sessions_allowed',
        'makeup_sessions_used',
        'progress_notes',
        'current_surah',
        'current_verse',
        'memorized_verses_count',
        'memorized_pages_count',
        'memorized_surahs',
        'tajweed_level',
        'recitation_accuracy',
        'performance_rating',
        'goals',
        'special_requirements',
        'starts_at',
        'expires_at',
        'last_session_at',
        'next_session_at',
        'paused_at',
        'pause_reason',
        'cancelled_at',
        'cancellation_reason',
        'parent_approval',
        'created_by',
        'updated_by',
        'notes'
    ];

    protected $casts = [
        'price_per_session' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'trial_sessions' => 'integer',
        'trial_used' => 'integer',
        'total_sessions' => 'integer',
        'completed_sessions' => 'integer',
        'missed_sessions' => 'integer',
        'makeup_sessions_allowed' => 'integer',
        'makeup_sessions_used' => 'integer',
        'sessions_per_week' => 'integer',
        'session_duration_minutes' => 'integer',
        'memorized_verses_count' => 'integer',
        'memorized_pages_count' => 'integer',
        'recitation_accuracy' => 'decimal:1',
        'performance_rating' => 'decimal:1',
        'parent_approval' => 'boolean',
        'memorized_surahs' => 'array',
        'goals' => 'array',
        'special_requirements' => 'array',
        'preferred_schedule' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_session_at' => 'datetime',
        'next_session_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime'
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
        return $this->belongsTo(QuranTeacher::class);
    }

    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeIndividual($query)
    {
        return $query->where('subscription_type', 'individual');
    }

    public function scopeCircle($query)
    {
        return $query->where('subscription_type', 'circle');
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeInTrial($query)
    {
        return $query->where('trial_used', '<', DB::raw('trial_sessions'));
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getSubscriptionTypeTextAttribute(): string
    {
        $types = [
            'individual' => 'فردي',
            'circle' => 'حلقة جماعية'
        ];

        return $types[$this->subscription_type] ?? $this->subscription_type;
    }

    public function getSpecializationTextAttribute(): string
    {
        $specializations = [
            'memorization' => 'حفظ',
            'recitation' => 'تلاوة',
            'tajweed' => 'تجويد',
            'complete' => 'شامل (حفظ وتجويد)'
        ];

        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'trial' => 'فترة تجريبية',
            'active' => 'نشط',
            'paused' => 'متوقف مؤقتاً',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        $colors = [
            'trial' => 'info',
            'active' => 'success',
            'paused' => 'warning',
            'expired' => 'danger',
            'cancelled' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_sessions == 0) {
            return 0;
        }

        return ($this->completed_sessions / $this->total_sessions) * 100;
    }

    public function getAttendanceRateAttribute(): float
    {
        $totalScheduled = $this->completed_sessions + $this->missed_sessions;
        if ($totalScheduled == 0) {
            return 100;
        }

        return ($this->completed_sessions / $totalScheduled) * 100;
    }

    public function getTrialRemainingAttribute(): int
    {
        return max(0, $this->trial_sessions - $this->trial_used);
    }

    public function getMakeupSessionsRemainingAttribute(): int
    {
        return max(0, $this->makeup_sessions_allowed - $this->makeup_sessions_used);
    }

    public function getMonthlyPriceAttribute(): float
    {
        if ($this->monthly_fee) {
            return $this->monthly_fee;
        }

        // Calculate from session price
        return $this->price_per_session * $this->sessions_per_week * 4.33; // Average weeks per month
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    // Methods
    public function recordSession(array $sessionData): QuranSession
    {
        $session = $this->sessions()->create(array_merge($sessionData, [
            'academy_id' => $this->academy_id,
            'student_id' => $this->student_id,
            'quran_teacher_id' => $this->quran_teacher_id
        ]));

        // Update subscription stats
        $this->increment('completed_sessions');
        $this->update(['last_session_at' => now()]);

        // Update progress if provided
        if (isset($sessionData['verses_memorized_today'])) {
            $this->increment('memorized_verses_count', $sessionData['verses_memorized_today']);
        }

        return $session;
    }

    public function recordMissedSession(string $reason = null): self
    {
        $this->increment('missed_sessions');
        
        // Allow makeup session if attendance rate is good
        if ($this->attendance_rate >= 80 && $this->makeup_sessions_remaining > 0) {
            // Makeup session can be scheduled
        }

        return $this;
    }

    public function useMakeupSession(): self
    {
        if ($this->makeup_sessions_remaining > 0) {
            $this->increment('makeup_sessions_used');
        }

        return $this;
    }

    public function useTrialSession(): self
    {
        if ($this->trial_remaining > 0) {
            $this->increment('trial_used');
        }

        return $this;
    }

    public function pause(string $reason = null): self
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
            'pause_reason' => $reason
        ]);

        return $this;
    }

    public function resume(): self
    {
        $pausedDays = $this->paused_at ? now()->diffInDays($this->paused_at) : 0;
        
        $this->update([
            'status' => 'active',
            'paused_at' => null,
            'pause_reason' => null,
            'expires_at' => $this->expires_at ? $this->expires_at->addDays($pausedDays) : null
        ]);

        return $this;
    }

    public function cancel(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        return $this;
    }

    public function extend(int $months): self
    {
        $newExpiry = $this->expires_at && $this->expires_at->isFuture() 
            ? $this->expires_at->addMonths($months)
            : now()->addMonths($months);

        $this->update([
            'expires_at' => $newExpiry,
            'status' => 'active'
        ]);

        return $this;
    }

    public function updateProgress(array $progressData): self
    {
        $this->update([
            'current_surah' => $progressData['current_surah'] ?? $this->current_surah,
            'current_verse' => $progressData['current_verse'] ?? $this->current_verse,
            'memorized_verses_count' => $progressData['memorized_verses_count'] ?? $this->memorized_verses_count,
            'memorized_pages_count' => $progressData['memorized_pages_count'] ?? $this->memorized_pages_count,
            'tajweed_level' => $progressData['tajweed_level'] ?? $this->tajweed_level,
            'recitation_accuracy' => $progressData['recitation_accuracy'] ?? $this->recitation_accuracy,
            'performance_rating' => $progressData['performance_rating'] ?? $this->performance_rating
        ]);

        return $this;
    }

    public function getNextSessionTime(): ?\Carbon\Carbon
    {
        if (!$this->preferred_schedule || $this->status !== 'active') {
            return null;
        }

        // Logic to calculate next session based on preferred schedule
        // This would involve complex scheduling logic
        return $this->next_session_at;
    }

    public function canScheduleSession(): bool
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function generateProgressReport(): array
    {
        return [
            'student_name' => $this->student->name,
            'teacher_name' => $this->quranTeacher?->user->name,
            'subscription_type' => $this->subscription_type_text,
            'specialization' => $this->specialization_text,
            'total_sessions' => $this->completed_sessions,
            'attendance_rate' => round($this->attendance_rate, 1),
            'memorized_verses' => $this->memorized_verses_count,
            'memorized_pages' => $this->memorized_pages_count,
            'current_progress' => $this->current_surah . ' - آية ' . $this->current_verse,
            'tajweed_level' => $this->tajweed_level,
            'recitation_accuracy' => $this->recitation_accuracy,
            'performance_rating' => $this->performance_rating,
            'last_session' => $this->last_session_at?->format('Y-m-d'),
            'days_remaining' => $this->days_remaining
        ];
    }
} 