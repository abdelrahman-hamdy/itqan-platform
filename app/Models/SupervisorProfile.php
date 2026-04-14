<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Traits\CascadesSoftDeleteToUser;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisorProfile extends Model
{
    use CascadesSoftDeleteToUser, HasFactory, ScopedToAcademy, SoftDeletes;

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
        'notes',
        'can_manage_teachers',
        'can_manage_students',
        'can_manage_parents',
        'can_reset_passwords',
        'can_manage_subscriptions',
        'can_view_subscriptions',
        'can_manage_payments',
        'can_manage_teacher_earnings',
        'can_monitor_sessions',
        'can_manage_sessions',
        'can_confirm_student_emails',
        'can_manage_interactive_courses',
        'can_manage_recording',
        'recording_session_types',
    ];

    protected $casts = [
        'can_manage_teachers' => 'boolean',
        'can_manage_students' => 'boolean',
        'can_manage_parents' => 'boolean',
        'can_reset_passwords' => 'boolean',
        'can_manage_subscriptions' => 'boolean',
        'can_view_subscriptions' => 'boolean',
        'can_manage_payments' => 'boolean',
        'can_manage_teacher_earnings' => 'boolean',
        'can_monitor_sessions' => 'boolean',
        'can_manage_sessions' => 'boolean',
        'can_confirm_student_emails' => 'boolean',
        'can_manage_interactive_courses' => 'boolean',
        'can_manage_recording' => 'boolean',
        'recording_session_types' => 'array',
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
                // Bypass academy scope — code generation needs to see all existing codes to ensure uniqueness
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

    // Request-scoped caches to avoid redundant queries within a single request
    protected ?array $cachedQuranTeacherIds = null;

    protected ?array $cachedAcademicTeacherIds = null;

    protected ?array $cachedInteractiveCourseIds = null;

    /**
     * Get assigned Quran teacher IDs (cached per request).
     */
    public function getAssignedQuranTeacherIds(): array
    {
        return $this->cachedQuranTeacherIds ??= $this->quranTeachers()->pluck('users.id')->toArray();
    }

    /**
     * Get assigned Academic teacher IDs (cached per request).
     */
    public function getAssignedAcademicTeacherIds(): array
    {
        return $this->cachedAcademicTeacherIds ??= $this->academicTeachers()->pluck('users.id')->toArray();
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
     * Check if supervisor can manage students.
     */
    public function canManageStudents(): bool
    {
        return $this->can_manage_students ?? false;
    }

    public function canManageParents(): bool
    {
        return $this->can_manage_parents ?? false;
    }

    public function canResetPasswords(): bool
    {
        return $this->can_reset_passwords ?? false;
    }

    public function canManageSubscriptions(): bool
    {
        return $this->can_manage_subscriptions ?? false;
    }

    public function canViewSubscriptions(): bool
    {
        return $this->can_view_subscriptions ?? false;
    }

    public function canManagePayments(): bool
    {
        return $this->can_manage_payments ?? false;
    }

    public function canManageTeacherEarnings(): bool
    {
        return $this->can_manage_teacher_earnings ?? false;
    }

    public function canMonitorSessions(): bool
    {
        return $this->can_monitor_sessions ?? false;
    }

    public function canManageSessions(): bool
    {
        return $this->can_manage_sessions ?? false;
    }

    public function canConfirmStudentEmails(): bool
    {
        return $this->can_confirm_student_emails ?? false;
    }

    public function canManageInteractiveCourses(): bool
    {
        return $this->can_manage_interactive_courses ?? false;
    }

    public function canManageRecording(): bool
    {
        return $this->can_manage_recording ?? false;
    }

    /**
     * Get the session types this supervisor can manage recordings for.
     */
    public function getRecordingSessionTypes(): array
    {
        return $this->recording_session_types ?? [];
    }

    /**
     * Check if supervisor can manage recording for a specific session type.
     * Empty array means all types are allowed.
     */
    public function canRecordSessionType(string $type): bool
    {
        if (! $this->canManageRecording()) {
            return false;
        }

        $allowed = $this->getRecordingSessionTypes();

        return empty($allowed) || in_array($type, $allowed);
    }

    /**
     * Get interactive courses derived from assigned academic teachers.
     * Interactive courses are assigned to academic teacher profiles,
     * so we look up the profiles of assigned academic teachers.
     */
    public function getDerivedInteractiveCourseIds(): array
    {
        if ($this->cachedInteractiveCourseIds !== null) {
            return $this->cachedInteractiveCourseIds;
        }

        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        if (empty($academicTeacherIds)) {
            return $this->cachedInteractiveCourseIds = [];
        }

        // Get AcademicTeacherProfile IDs from the User IDs
        $profileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
            ->pluck('id')
            ->toArray();

        if (empty($profileIds)) {
            return $this->cachedInteractiveCourseIds = [];
        }

        // Get all interactive courses assigned to these teacher profiles
        return $this->cachedInteractiveCourseIds = InteractiveCourse::whereIn('assigned_teacher_id', $profileIds)
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
