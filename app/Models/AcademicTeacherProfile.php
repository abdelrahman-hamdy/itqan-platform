<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AcademicTeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teacher_code',
        'education_level',
        'university',
        'graduation_year',
        'qualification_degree',
        'teaching_experience_years',
        'certifications',
        'languages',
        'subject_ids',
        'grade_level_ids',
        'available_days',
        'available_time_start',
        'available_time_end',
        'session_price_individual',
        'min_session_duration',
        'max_session_duration',
        'max_students_per_group',
        'bio_arabic',
        'bio_english',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_active',
        'rating',
        'total_students',
        'total_sessions',
        'total_courses_created',
        'notes',
    ];

    protected $casts = [
        'certifications' => 'array',
        'languages' => 'array',
        'subject_ids' => 'array',
        'grade_level_ids' => 'array',
        'available_days' => 'array',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'graduation_year' => 'integer',
        'teaching_experience_years' => 'integer',
        'session_price_individual' => 'decimal:2',
        'min_session_duration' => 'integer',
        'max_session_duration' => 'integer',
        'max_students_per_group' => 'integer',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
        'total_courses_created' => 'integer',
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
                $model->teacher_code = 'AT-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

    /**
     * Get subjects this teacher can teach (for display purposes)
     */
    public function getSubjectNamesAttribute()
    {
        if (empty($this->subject_ids)) {
            return collect();
        }
        
        return Subject::whereIn('id', $this->subject_ids)
            ->where('is_active', true)
            ->pluck('name');
    }

    /**
     * Get grade levels this teacher can teach (for display purposes)
     */
    public function getGradeLevelNamesAttribute()
    {
        if (empty($this->grade_level_ids)) {
            return collect();
        }
        
        return GradeLevel::whereIn('id', $this->grade_level_ids)
            ->where('is_active', true)
            ->pluck('name');
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

    public function getEducationLevelInArabicAttribute(): string
    {
        return match($this->education_level) {
            'diploma' => 'دبلوم',
            'bachelor' => 'بكالوريوس',
            'master' => 'ماجستير',
            'phd' => 'دكتوراه',
            default => $this->education_level,
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
     * Check if teacher can teach a specific subject
     */
    public function canTeachSubject(int $subjectId): bool
    {
        return in_array($subjectId, $this->subject_ids ?? []);
    }

    /**
     * Check if teacher can teach a specific grade level
     */
    public function canTeachGradeLevel(int $gradeLevelId): bool
    {
        return in_array($gradeLevelId, $this->grade_level_ids ?? []);
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

    public function scopeCanTeachSubject($query, int $subjectId)
    {
        return $query->whereJsonContains('subject_ids', $subjectId);
    }

    public function scopeCanTeachGradeLevel($query, int $gradeLevelId)
    {
        return $query->whereJsonContains('grade_level_ids', $gradeLevelId);
    }
}
