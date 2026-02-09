<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisorProfile extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'gender',
        'supervisor_code',
        'performance_rating',
        'notes',
        'can_manage_teachers',
    ];

    protected $casts = [
        'performance_rating' => 'decimal:2',
        'can_manage_teachers' => 'boolean',
    ];

    /**
     * Boot method to auto-generate supervisor code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->supervisor_code)) {
                // Use academy_id from the model, or fallback to 1 if not set
                $academyId = $model->academy_id ?: 1;
                $prefix = 'SUP-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

                // Find the highest existing sequence number for this academy
                $maxCode = static::withoutGlobalScopes()
                    ->where('supervisor_code', 'like', $prefix.'%')
                    ->orderByRaw('CAST(SUBSTRING(supervisor_code, -4) AS UNSIGNED) DESC')
                    ->value('supervisor_code');

                if ($maxCode) {
                    // Extract the sequence number and increment
                    $sequence = (int) substr($maxCode, -4) + 1;
                } else {
                    $sequence = 1;
                }

                $model->supervisor_code = $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // SupervisorProfile -> Academy (direct relationship)
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

    /**
     * Get all responsibilities for this supervisor.
     */
    public function responsibilities(): HasMany
    {
        return $this->hasMany(SupervisorResponsibility::class);
    }

    /**
     * Get assigned Quran teachers (User models with user_type='quran_teacher').
     */
    public function quranTeachers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'responsable', 'supervisor_responsibilities')
            ->where('user_type', UserType::QURAN_TEACHER->value);
    }

    /**
     * Get assigned Academic teachers (User models with user_type='academic_teacher').
     */
    public function academicTeachers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'responsable', 'supervisor_responsibilities')
            ->where('user_type', UserType::ACADEMIC_TEACHER->value);
    }

    /**
     * Get assigned interactive courses (direct assignment, kept as-is).
     */
    public function interactiveCourses(): MorphToMany
    {
        return $this->morphedByMany(InteractiveCourse::class, 'responsable', 'supervisor_responsibilities');
    }

    /**
     * Helper methods
     */
    public function getDisplayName(): string
    {
        return $this->user->name.' ('.$this->supervisor_code.')';
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return ! is_null($this->user_id);
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
        return $query->whereHas('user', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    /**
     * Check if supervisor has any responsibilities of a specific type.
     */
    public function hasResponsibilityType(string $modelClass): bool
    {
        return $this->responsibilities()->where('responsable_type', $modelClass)->exists();
    }

    /**
     * Get all resource IDs for a specific responsibility type.
     */
    public function getResponsibilityIds(string $modelClass): array
    {
        return $this->responsibilities()
            ->where('responsable_type', $modelClass)
            ->pluck('responsable_id')
            ->toArray();
    }

    /**
     * Get count of responsibilities by type.
     */
    public function getResponsibilityCountByType(): array
    {
        return [
            'quran_teachers' => $this->quranTeachers()->count(),
            'academic_teachers' => $this->academicTeachers()->count(),
            'interactive_courses' => $this->interactiveCourses()->count(),
        ];
    }

    /**
     * Check if supervisor has any responsibilities at all.
     */
    public function hasAnyResponsibilities(): bool
    {
        return $this->responsibilities()->exists();
    }

    /**
     * Get total count of all responsibilities.
     */
    public function getTotalResponsibilitiesCount(): int
    {
        return $this->responsibilities()->count();
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get assigned Quran teacher IDs.
     */
    public function getAssignedQuranTeacherIds(): array
    {
        return $this->quranTeachers()->pluck('users.id')->toArray();
    }

    /**
     * Get assigned Academic teacher IDs.
     */
    public function getAssignedAcademicTeacherIds(): array
    {
        return $this->academicTeachers()->pluck('users.id')->toArray();
    }

    /**
     * Get all assigned teacher IDs (both Quran and Academic).
     */
    public function getAllAssignedTeacherIds(): array
    {
        return array_merge(
            $this->getAssignedQuranTeacherIds(),
            $this->getAssignedAcademicTeacherIds()
        );
    }

    /**
     * Check if supervisor can manage teachers.
     */
    public function canManageTeachers(): bool
    {
        return $this->can_manage_teachers ?? false;
    }

    /**
     * Get interactive courses derived from assigned academic teachers.
     * Interactive courses are assigned to academic teacher profiles,
     * so we look up the profiles of assigned academic teachers.
     */
    public function getDerivedInteractiveCourseIds(): array
    {
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        if (empty($academicTeacherIds)) {
            return [];
        }

        // Get AcademicTeacherProfile IDs from the User IDs
        $profileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
            ->pluck('id')
            ->toArray();

        if (empty($profileIds)) {
            return [];
        }

        // Get all interactive courses assigned to these teacher profiles
        return InteractiveCourse::whereIn('assigned_teacher_id', $profileIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get count of interactive courses derived from academic teachers.
     */
    public function getDerivedInteractiveCoursesCount(): int
    {
        return count($this->getDerivedInteractiveCourseIds());
    }
}
