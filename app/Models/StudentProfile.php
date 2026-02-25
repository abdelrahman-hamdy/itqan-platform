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
        // SECURITY: academy_id excluded — set by boot() hook from grade_level or user, never via mass assignment
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
        'academy_id' => 'integer',
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
            // Resolve academy ID from grade level or user for both code generation and direct column
            $academyId = 1; // Default fallback
            if ($model->grade_level_id) {
                $gradeLevel = AcademicGradeLevel::find($model->grade_level_id);
                $academyId = $gradeLevel ? $gradeLevel->academy_id : 1;
            } elseif ($model->user_id) {
                $user = User::find($model->user_id);
                $academyId = $user ? $user->academy_id : 1;
            }

            if (empty($model->student_code)) {
                // Generate unique code with timestamp
                $timestamp = now()->format('His'); // HHMMSS
                $random = random_int(100, 999);
                $model->student_code = 'ST-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.$timestamp.$random;
            }

            // Also ensure academy_id column is populated
            if (empty($model->academy_id)) {
                if ($model->grade_level_id) {
                    $gradeLevel = $gradeLevel ?? AcademicGradeLevel::find($model->grade_level_id);
                    $model->academy_id = $gradeLevel ? $gradeLevel->academy_id : null;
                } elseif ($model->user_id) {
                    $user = $user ?? User::find($model->user_id);
                    $model->academy_id = $user ? $user->academy_id : null;
                }
            }
        });
    }

    /**
     * Replace the trait's relationship-based global scope with a faster direct
     * column scope now that student_profiles has an academy_id column.
     * Falls back to the gradeLevel relationship for legacy records where the
     * column may not have been backfilled (e.g., very old data or orphaned rows).
     */
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('academy_via_relationship', function ($builder) {
            $academyContextService = app(\App\Services\AcademyContextService::class);
            $currentAcademyId = $academyContextService->getCurrentAcademyId();

            if ($currentAcademyId && ! $academyContextService->isGlobalViewMode()) {
                $builder->where(function ($query) use ($currentAcademyId) {
                    $query->where('student_profiles.academy_id', $currentAcademyId)
                        ->orWhere(function ($fallback) use ($currentAcademyId) {
                            // Fallback for records where academy_id wasn't backfilled (very old data)
                            $fallback->whereNull('student_profiles.academy_id')
                                ->whereHas('gradeLevel', function ($g) use ($currentAcademyId) {
                                    $g->where('academy_id', $currentAcademyId);
                                });
                        });
                });
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
     * Get academy ID — prefers the direct column (fast), falls back to the
     * gradeLevel relationship for legacy records that predate the column.
     */
    public function getAcademyIdAttribute(): ?int
    {
        // Use direct column first (faster, more reliable)
        if (isset($this->attributes['academy_id']) && $this->attributes['academy_id'] !== null) {
            return (int) $this->attributes['academy_id'];
        }
        // Fallback to relationship for legacy records
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
        return $query->where('academy_id', $academyId);
    }
}
