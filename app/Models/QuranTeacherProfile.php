<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class QuranTeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // Nullable - will be linked during registration
        'email',
        'first_name',
        'last_name',
        'phone',
        'teacher_code',
        'educational_qualification',
        'certifications',
        'teaching_experience_years',
        'available_time_start',
        'available_time_end',
        'available_days',
        'languages',
        'bio_arabic',
        'bio_english',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_active',
        'rating',
        'total_students',
        'total_sessions',
    ];

    protected $casts = [
        'certifications' => 'array',
        'available_days' => 'array',
        'languages' => 'array',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
        'teaching_experience_years' => 'integer',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
    ];

    /**
     * Boot method to auto-generate teacher code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->teacher_code)) {
                // Extract academy from email or use default academy ID 1
                $academyId = 1; // TODO: Extract from email domain or admin context
                $count = static::count() + 1;
                $model->teacher_code = 'QT-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationships
     */
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
        return $this->hasManyThrough(QuranSession::class, User::class, 'id', 'teacher_id', 'user_id', 'id');
    }

    public function quranCircles(): HasMany
    {
        return $this->hasManyThrough(QuranCircle::class, User::class, 'id', 'teacher_id', 'user_id', 'id');
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
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Status Methods
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->isApproved();
    }

    /**
     * Actions
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
            'is_active' => true,
        ]);
    }

    public function reject(int $rejectedBy, ?string $reason = null): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => Carbon::now(),
            'is_active' => false,
        ]);
    }

    public function suspend(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
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
        // Since we don't have direct academy relationship, we'll need to determine this differently
        // For now, we can use a TODO comment and implement based on email domain or other logic
        return $query; // TODO: Implement academy scoping for registration flow
    }
}
