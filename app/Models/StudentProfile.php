<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ScopedToAcademyViaRelationship;

class StudentProfile extends Model
{
    use HasFactory, ScopedToAcademyViaRelationship;

    protected $fillable = [
        'user_id', // Nullable - will be linked during registration
        'email',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'student_code',
        'grade_level_id',
        'birth_date',
        'gender',
        'nationality',
        'parent_id',
        'address',
        'emergency_contact',
        'enrollment_date',
        'graduation_date',
        'academic_status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'graduation_date' => 'date',
        'grade_level_id' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Boot method to auto-generate student code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->student_code)) {
                // Get academy ID from grade level or user
                $academyId = 1; // Default fallback
                if ($model->grade_level_id) {
                    $gradeLevel = \App\Models\AcademicGradeLevel::find($model->grade_level_id);
                    $academyId = $gradeLevel ? $gradeLevel->academy_id : 1;
                } elseif ($model->user_id) {
                    $user = \App\Models\User::find($model->user_id);
                    $academyId = $user ? $user->academy_id : 1;
                }
                
                // Generate unique code with timestamp
                $timestamp = now()->format('His'); // HHMMSS
                $random = rand(100, 999);
                $model->student_code = 'ST-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . $timestamp . $random;
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'gradeLevel.academy'; // StudentProfile -> GradeLevel -> Academy
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class, 'grade_level_id');
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
        return $this->full_name . ' (' . $this->student_code . ')';
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Get academy through grade level
     */
    public function getAcademyIdAttribute(): ?int
    {
        return $this->gradeLevel?->academy_id;
    }

    /**
     * Scopes
     */
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
        return $query->whereHas('gradeLevel', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }
}
