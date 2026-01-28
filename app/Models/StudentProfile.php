<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademyViaRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, ScopedToAcademyViaRelationship, SoftDeletes;

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
        'parent_phone', // International phone number in E.164 format
        'parent_phone_country_code', // e.g., +966
        'parent_phone_country', // ISO 3166-1 alpha-2 (e.g., SA)
        'enrollment_date',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'grade_level_id' => 'integer',
        'parent_id' => 'integer',
        'nationality' => 'string', // ISO 3166-1 alpha-2 code, validated against CountryList
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
                $model->student_code = 'ST-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.$timestamp.$random;
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function parentProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student_relationships', 'student_id', 'parent_id')
            ->using(ParentStudentRelationship::class)
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    /**
     * Helper Methods
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name.' ('.$this->student_code.')';
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return ! is_null($this->user_id);
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
