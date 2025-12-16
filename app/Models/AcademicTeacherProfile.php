<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ScopedToAcademy;
use App\Models\Traits\HasReviews;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicSubject;

class AcademicTeacherProfile extends Model
{
    use HasFactory, ScopedToAcademy, HasReviews, SoftDeletes;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id', // Nullable - will be linked during registration
        'email',
        'gender',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'teacher_code',
        'education_level',
        'university',
        'teaching_experience_years',
        'certifications',
        'subject_ids',         // ← SINGLE SOURCE OF TRUTH
        'grade_level_ids',     // ← SINGLE SOURCE OF TRUTH
        // 'subjects_text',    // ← REMOVED DUPLICATE
        // 'grade_levels_text', // ← REMOVED DUPLICATE
        'package_ids',
        'available_days',      // ← SINGLE SOURCE OF TRUTH
        'available_time_start',
        'available_time_end',
        'session_price_individual',
        'languages',
        'approval_status',    // Required for admin approval workflow
        'approved_by',
        'approved_at',
        'is_active',          // ← PRIMARY ACTIVATION FIELD
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
        // 'subjects_text' => 'array',   // ← REMOVED DUPLICATE
        // 'grade_levels_text' => 'array', // ← REMOVED DUPLICATE
        'package_ids' => 'array',
        'available_days' => 'array',     // ← SINGLE SOURCE OF TRUTH
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'teaching_experience_years' => 'integer',
        'session_price_individual' => 'decimal:2',
        'total_students' => 'integer',
        'total_courses_created' => 'integer',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
    ];

    /**
     * Generate a unique teacher code for the academy
     */
    public static function generateTeacherCode($academyId)
    {
        $academyId = $academyId ?: 1;
        $prefix = 'AT-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-';
        
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class, 'assigned_teacher_id');
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
        if (!is_array($gradeLevelIds)) {
            return collect();
        }

        return AcademicGradeLevel::whereIn('id', $gradeLevelIds)
                                 ->where('academy_id', $this->academy_id)
                                 ->get();
    }

    /**
     * Get subjects as text array for forms (backward compatibility)
     */
    public function getSubjectsTextAttribute()
    {
        if (empty($this->subject_ids)) {
            return [];
        }

        // Ensure subject_ids is an array
        $subjectIds = $this->subject_ids;
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true) ?: [];
        }
        if (!is_array($subjectIds)) {
            return [];
        }

        // Get the actual subject names from the database
        $subjects = AcademicSubject::whereIn('id', $subjectIds)
                                   ->where('academy_id', $this->academy_id)
                                   ->pluck('name', 'id')
                                   ->toArray();

        // Return array of subject names with IDs as keys (for consistent indexing)
        return $subjects;
    }

    /**
     * Get grade levels as text array for forms (backward compatibility)
     */
    public function getGradeLevelsTextAttribute()
    {
        if (empty($this->grade_level_ids)) {
            return [];
        }

        // Ensure grade_level_ids is an array
        $gradeLevelIds = $this->grade_level_ids;
        if (is_string($gradeLevelIds)) {
            $gradeLevelIds = json_decode($gradeLevelIds, true) ?: [];
        }
        if (!is_array($gradeLevelIds)) {
            return [];
        }

        // Get the actual grade level names from the database
        $gradeLevels = AcademicGradeLevel::whereIn('id', $gradeLevelIds)
                                         ->where('academy_id', $this->academy_id)
                                         ->pluck('name', 'id')
                                         ->toArray();

        // Return array of grade level names with IDs as keys (for consistent indexing)
        return $gradeLevels;
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
        if (!is_array($packageIds)) {
            return collect();
        }
        
        return \App\Models\AcademicPackage::whereIn('id', $packageIds)
                                 ->where('academy_id', $this->academy_id)
                                 ->where('is_active', true)
                                 ->get();
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
        // Since we don't have direct academy relationship, we'll need to determine this differently
        // For now, we can use a TODO comment and implement based on email domain or other logic
        return $query; // TODO: Implement academy scoping for registration flow
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
