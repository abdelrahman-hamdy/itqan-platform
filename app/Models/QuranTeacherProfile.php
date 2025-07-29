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
        'user_id',
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
                $academyId = $model->user->academy_id ?? 1;
                $count = static::whereHas('user', function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })->count() + 1;
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

    public function quranSubscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'quran_teacher_id');
    }

    /**
     * Helper methods
     */
    public function getDisplayName(): string
    {
        return $this->user->name . ' (' . $this->teacher_code . ')';
    }

    public function getFullNameAttribute(): string
    {
        return $this->user->name;
    }

    public function getEducationalQualificationInArabicAttribute(): string
    {
        return match($this->educational_qualification) {
            'bachelor' => 'بكالوريوس',
            'master' => 'ماجستير',
            'phd' => 'دكتوراه',
            'other' => 'أخرى',
            default => $this->educational_qualification,
        };
    }

    public function getApprovalStatusInArabicAttribute(): string
    {
        return match($this->approval_status) {
            'pending' => 'في الانتظار',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => $this->approval_status,
        };
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function approve(User $approver): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'is_active' => true,
        ]);
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'approval_status' => 'rejected',
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

    public function scopeForAcademy($query, int $academyId)
    {
        return $query->whereHas('user', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }
}
