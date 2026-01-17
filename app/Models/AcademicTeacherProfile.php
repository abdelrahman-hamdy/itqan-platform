<?php

namespace App\Models;

use App\Enums\EducationalQualification;
use App\Models\Traits\HasReviews;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicTeacherProfile extends Model
{
    use HasFactory, HasReviews, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id', // Nullable - will be linked during registration
        'gender',
        'avatar',
        'teacher_code',
        'education_level',
        'university',
        'teaching_experience_years',
        'certifications',
        'subject_ids',         // ← SINGLE SOURCE OF TRUTH
        'grade_level_ids',     // ← SINGLE SOURCE OF TRUTH
        'package_ids',
        'available_days',      // ← SINGLE SOURCE OF TRUTH
        'available_time_start',
        'available_time_end',
        'session_price_individual',
        'languages',
        // Activation fields removed - use User.active_status instead
        'notes',
        'bio_arabic',
        'bio_english',
        'rating',             // Review statistics
        'total_reviews',      // Review statistics
    ];

    protected $casts = [
        'certifications' => 'array',
        'languages' => 'array',
        'subject_ids' => 'array',        // ← SINGLE SOURCE OF TRUTH
        'grade_level_ids' => 'array',    // ← SINGLE SOURCE OF TRUTH
        'package_ids' => 'array',
        'available_days' => 'array',     // ← SINGLE SOURCE OF TRUTH
        // Activation casts removed - use User.active_status instead
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'teaching_experience_years' => 'integer',
        'session_price_individual' => 'decimal:2',
        'total_students' => 'integer',
        'total_courses_created' => 'integer',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
        'education_level' => EducationalQualification::class,
    ];

    /**
     * Generate a unique teacher code for the academy
     */
    public static function generateTeacherCode($academyId)
    {
        $academyId = $academyId ?: 1;
        $prefix = 'AT-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

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
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // AcademicTeacherProfile -> Academy (direct relationship)
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

    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class, 'assigned_teacher_id');
    }

    /**
     * Get individual lessons taught by this teacher
     */
    public function privateSessions(): HasMany
    {
        return $this->hasMany(AcademicIndividualLesson::class, 'academic_teacher_id');
    }

    /**
     * Get subscriptions taught by this teacher
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AcademicSubscription::class, 'academic_teacher_id');
    }

    /**
     * Get sessions taught by this teacher
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'academic_teacher_id');
    }

    /**
     * Get recorded courses (through creator user)
     * Note: RecordedCourse doesn't have direct teacher_id, uses created_by
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class, 'created_by', 'user_id');
    }

    /**
     * Get students taught by this teacher (through subscriptions)
     */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            AcademicSubscription::class,
            'academic_teacher_id', // Foreign key on academic_subscriptions
            'id',                   // Foreign key on users
            'id',                   // Local key on academic_teacher_profiles
            'student_id'           // Local key on academic_subscriptions
        );
    }

    /**
     * Packages relationship (for eager loading)
     * Returns packages based on package_ids JSON array
     */
    public function packages(): BelongsToMany
    {
        // Return empty relationship - actual packages come from getPackagesAttribute accessor
        return $this->belongsToMany(AcademicPackage::class, 'academic_teacher_packages', 'teacher_id', 'package_id');
    }

    /**
     * Get all reviews for this teacher
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(TeacherReview::class, 'reviewable');
    }

    /**
     * Get the subjects this teacher can teach
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(AcademicSubject::class, 'academic_teacher_subjects', 'teacher_id', 'subject_id')
            ->withPivot(['proficiency_level', 'years_experience', 'is_primary', 'certification'])
            ->withTimestamps();
    }

    /**
     * Get the grade levels this teacher can teach
     */
    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(AcademicGradeLevel::class, 'academic_teacher_grade_levels', 'teacher_id', 'grade_level_id')
            ->withPivot(['years_experience', 'specialization_notes'])
            ->withTimestamps();
    }

    /**
     * Get the subjects this teacher can teach based on subject_ids array
     */
    public function getSubjectsAttribute()
    {
        if (empty($this->subject_ids)) {
            return collect();
        }

        // Ensure subject_ids is an array
        $subjectIds = $this->subject_ids;
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true) ?: [];
        }
        if (! is_array($subjectIds)) {
            return collect();
        }

        return AcademicSubject::whereIn('id', $subjectIds)
            ->where('academy_id', $this->academy_id)
            ->get();
    }

    /**
     * Get the grade levels this teacher can teach based on grade_level_ids array
     */
    public function getGradeLevelsAttribute()
    {
        if (empty($this->grade_level_ids)) {
            return collect();
        }

        // Ensure grade_level_ids is an array
        $gradeLevelIds = $this->grade_level_ids;
        if (is_string($gradeLevelIds)) {
            $gradeLevelIds = json_decode($gradeLevelIds, true) ?: [];
        }
        if (! is_array($gradeLevelIds)) {
            return collect();
        }

        return AcademicGradeLevel::whereIn('id', $gradeLevelIds)
            ->where('academy_id', $this->academy_id)
            ->get();
    }

    /**
     * Get the packages this teacher can offer based on package_ids array
     */
    public function getPackagesAttribute()
    {
        if (empty($this->package_ids)) {
            return collect();
        }

        // Ensure package_ids is an array
        $packageIds = $this->package_ids;
        if (is_string($packageIds)) {
            $packageIds = json_decode($packageIds, true) ?: [];
        }
        if (! is_array($packageIds)) {
            return collect();
        }

        return \App\Models\AcademicPackage::whereIn('id', $packageIds)
            ->where('academy_id', $this->academy_id)
            ->where('is_active', true)
            ->get();
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

    // Note: Gender is now stored directly in the academic_teacher_profiles.gender column
    // Removed getGenderAttribute accessor to use the profile's own gender field

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
     * Get the education level label in Arabic
     */
    public function getEducationLevelInArabicAttribute(): ?string
    {
        return $this->education_level?->label();
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
     * Check if teacher can teach a specific subject
     */
    public function canTeachSubject(int $subjectId): bool
    {
        $subjectIds = $this->subject_ids ?? [];
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true) ?: [];
        }

        return is_array($subjectIds) && in_array($subjectId, $subjectIds);
    }

    /**
     * Check if teacher can teach a specific grade level
     */
    public function canTeachGradeLevel(int $gradeLevelId): bool
    {
        $gradeLevelIds = $this->grade_level_ids ?? [];
        if (is_string($gradeLevelIds)) {
            $gradeLevelIds = json_decode($gradeLevelIds, true) ?: [];
        }

        return is_array($gradeLevelIds) && in_array($gradeLevelId, $gradeLevelIds);
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

    public function scopeCanTeachSubject($query, int $subjectId)
    {
        return $query->whereJsonContains('subject_ids', $subjectId);
    }

    public function scopeCanTeachGradeLevel($query, int $gradeLevelId)
    {
        return $query->whereJsonContains('grade_level_ids', $gradeLevelId);
    }
}
