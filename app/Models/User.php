<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Profile Models

// Course and Circle Models

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

    /**
     * Boot method to add observers
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Automatically create profile based on user_type
            // Skip teachers as they are handled manually during registration
            if ($user->user_type && $user->academy_id && ! in_array($user->user_type, ['quran_teacher', 'academic_teacher'])) {
                $user->createProfile();
            }
        });
    }

    /**
     * User roles constants
     */
    const ROLE_SUPER_ADMIN = 'super_admin';

    const ROLE_ACADEMY_ADMIN = 'academy_admin';

    const ROLE_QURAN_TEACHER = 'quran_teacher';

    const ROLE_ACADEMIC_TEACHER = 'academic_teacher';

    const ROLE_SUPERVISOR = 'supervisor';

    const ROLE_STUDENT = 'student';

    const ROLE_PARENT = 'parent';

    /**
     * Get unique identifier for LiveKit and data channel communications
     */
    public function getIdentifier(): string
    {
        return $this->id.'_'.str_replace(' ', '_', strtolower($this->first_name.'_'.$this->last_name));
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($this->user_type, $roles);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academy_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'user_type',
        // 'status', // ← REMOVED - using active_status only
        // 'role' field removed - using user_type instead
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'avatar',
        'profile_completed_at',
        'active_status',
        'phone_verification_token',
        'password_reset_token',
        'remember_token',

        // Google OAuth and integration fields
        'google_id',
        'google_email',
        'google_connected_at',
        'google_disconnected_at',
        'google_calendar_enabled',
        'google_permissions',
        'meeting_preferences',
        'auto_create_meetings',
        'meeting_prep_minutes',
        'notify_on_google_disconnect',
        'notify_admin_on_disconnect',

        // Teacher Google preferences
        'teacher_auto_record',
        'teacher_default_duration',
        'teacher_meeting_prep_minutes',
        'teacher_send_reminders',
        'teacher_reminder_times',
        'sync_to_google_calendar',
        'allow_calendar_conflicts',
        'calendar_visibility',
        'notify_on_student_join',
        'notify_on_session_end',
        'notification_method',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verification_token',
        'password_reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
            'active_status' => 'boolean',

            // Google OAuth fields
            'google_connected_at' => 'datetime',
            'google_disconnected_at' => 'datetime',
            'google_calendar_enabled' => 'boolean',
            'google_permissions' => 'array',
            'meeting_preferences' => 'array',
            'auto_create_meetings' => 'boolean',
            'meeting_prep_minutes' => 'integer',
            'notify_on_google_disconnect' => 'boolean',
            'notify_admin_on_disconnect' => 'boolean',

            // Teacher Google preferences
            'teacher_auto_record' => 'boolean',
            'teacher_default_duration' => 'integer',
            'teacher_meeting_prep_minutes' => 'integer',
            'teacher_send_reminders' => 'boolean',
            'teacher_reminder_times' => 'array',
            'sync_to_google_calendar' => 'boolean',
            'allow_calendar_conflicts' => 'boolean',
            'notify_on_student_join' => 'boolean',
            'notify_on_session_end' => 'boolean',
        ];
    }

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the user's profile based on user_type
     */
    public function getProfile()
    {
        return match ($this->user_type) {
            'student' => $this->studentProfile,
            'quran_teacher' => $this->quranTeacherProfile,
            'academic_teacher' => $this->academicTeacherProfile,
            'parent' => $this->parentProfile,
            'supervisor' => $this->supervisorProfile,
            'admin' => null, // Admins use basic user info only
            default => null,
        };
    }

    /**
     * Specific profile relationship methods for easier querying
     */
    public function quranTeacherProfile(): HasOne
    {
        return $this->hasOne(QuranTeacherProfile::class);
    }

    public function academicTeacherProfile(): HasOne
    {
        return $this->hasOne(AcademicTeacherProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get student profile without global scopes (for internal use)
     * This ensures students can always access their own profile regardless of academy context
     */
    public function studentProfileUnscoped(): HasOne
    {
        return $this->hasOne(StudentProfile::class)->withoutGlobalScopes();
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function supervisorProfile(): HasOne
    {
        return $this->hasOne(SupervisorProfile::class);
    }

    /**
     * Subjects relationship for academic teachers
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(AcademicSubject::class, 'teacher_id');
    }

    /**
     * Parent-child relationships for family accounts
     * Safely re-enabled with proper conditions
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id')
            ->where('user_type', 'student');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id')
            ->where('user_type', 'parent');
    }

    /**
     * User sessions for tracking
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Quran circles that this user (student) is enrolled in
     */
    public function quranCircles(): BelongsToMany
    {
        return $this->belongsToMany(QuranCircle::class, 'quran_circle_students', 'student_id', 'circle_id')
            ->withPivot([
                'enrolled_at',
                'status',
                'attendance_count',
                'missed_sessions',
                'makeup_sessions_used',
                'current_level',
                'progress_notes',
                'parent_rating',
                'student_rating',
                'completion_date',
                'certificate_issued',
            ])
            ->withTimestamps();
    }

    /**
     * Individual Quran circles that this user (student) is enrolled in
     */
    public function quranIndividualCircles(): HasMany
    {
        return $this->hasMany(QuranIndividualCircle::class, 'student_id');
    }

    /**
     * Interactive course enrollments for this user (student)
     */
    public function interactiveCourseEnrollments(): HasMany
    {
        return $this->hasMany(InteractiveCourseEnrollment::class, 'student_id');
    }

    /**
     * Recorded course enrollments for this user (student)
     */
    public function recordedCourseEnrollments(): HasMany
    {
        return $this->hasMany(CourseSubscription::class, 'student_id');
    }

    /**
     * Get full name attribute
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: 'مستخدم غير محدد';
    }

    /**
     * Get display name based on profile data
     */
    public function getDisplayNameAttribute(): string
    {
        $profile = $this->getProfile();

        if ($profile && method_exists($profile, 'getDisplayName')) {
            return $profile->getDisplayName();
        }

        return $this->name;
    }

    /**
     * Check if user has completed profile
     */
    public function hasCompletedProfile(): bool
    {
        return ! is_null($this->profile_completed_at);
    }

    /**
     * Check if user email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check if user phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Create profile based on user type
     */
    public function createProfile(): void
    {
        // Skip if user already has a profile or if user_type is admin/super_admin
        if ($this->getProfile() || in_array($this->user_type, ['admin', 'super_admin'])) {
            return;
        }

        $profileData = [
            'user_id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
        ];

        $profileDataWithAcademy = array_merge($profileData, [
            'academy_id' => $this->academy_id,
        ]);

        switch ($this->user_type) {
            case 'student':
                // Get a random grade level from the user's academy
                $gradeLevel = \App\Models\AcademicGradeLevel::where('academy_id', $this->academy_id)->inRandomOrder()->first();

                StudentProfile::create(array_merge($profileData, [
                    'grade_level_id' => $gradeLevel ? $gradeLevel->id : null,
                    'birth_date' => now()->subYears(rand(8, 18)),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'nationality' => 'Saudi',
                    'parent_id' => $this->parent_id,
                    'academic_status' => 'enrolled',
                    'enrollment_date' => now()->subMonths(rand(1, 12)),
                ]));
                break;

            case 'quran_teacher':
                QuranTeacherProfile::create(array_merge($profileDataWithAcademy, [
                    'educational_qualification' => 'bachelor',
                    'teaching_experience_years' => 1,
                    'approval_status' => 'pending',
                ]));
                break;

            case 'academic_teacher':
                AcademicTeacherProfile::create(array_merge($profileDataWithAcademy, [
                    'education_level' => 'bachelor',
                    'qualification_degree' => 'بكالوريوس في التربية',
                    'teaching_experience_years' => 1,
                    'session_price_individual' => 60,
                    'approval_status' => 'pending',
                ]));
                break;

            case 'parent':
                ParentProfile::create(array_merge($profileDataWithAcademy, [
                    'relationship_type' => 'father',
                    'preferred_contact_method' => 'phone',
                ]));
                break;

            case 'supervisor':
                SupervisorProfile::create(array_merge($profileDataWithAcademy, [
                    'department' => 'general',
                    'supervision_level' => 'junior',
                    'hired_date' => now()->toDateString(),
                ]));
                break;
        }
    }

    /**
     * Check if user is active - Simplified
     */
    public function isActive(): bool
    {
        return $this->active_status;
    }

    /**
     * Check if user can access dashboard (power users)
     */
    public function canAccessDashboard(): bool
    {
        return in_array($this->user_type, [
            'admin',
            'supervisor',
            'quran_teacher',
            'academic_teacher',
        ]);
    }

    /**
     * Get dashboard route based on user type
     */
    public function getDashboardRoute(): string
    {
        return match ($this->user_type) {
            'admin' => '/panel',
            'supervisor' => '/supervisor',
            'quran_teacher', 'academic_teacher' => '/teacher',
            default => '/profile',
        };
    }

    /**
     * User type helper methods
     */
    public function isStudent(): bool
    {
        return $this->user_type === 'student';
    }

    public function isQuranTeacher(): bool
    {
        return $this->user_type === 'quran_teacher';
    }

    public function isAcademicTeacher(): bool
    {
        return $this->user_type === 'academic_teacher';
    }

    public function isParent(): bool
    {
        return $this->user_type === 'parent';
    }

    public function isSupervisor(): bool
    {
        return $this->user_type === 'supervisor';
    }

    public function isAdmin(): bool
    {
        return in_array($this->user_type, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    public function isAcademyAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user is a teacher (any type)
     */
    public function isTeacher(): bool
    {
        return in_array($this->user_type, ['quran_teacher', 'academic_teacher']);
    }

    /**
     * Check if user is staff (admin, supervisor, or teacher)
     */
    public function isStaff(): bool
    {
        return in_array($this->user_type, ['admin', 'super_admin', 'supervisor', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Check if user is end user (student or parent)
     */
    public function isEndUser(): bool
    {
        return in_array($this->user_type, ['student', 'parent']);
    }

    /**
     * Scope to filter by user type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope to filter by academy
     */
    public function scopeForAcademy($query, int $academyId): Builder
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Scope to get active users - Simplified
     */
    public function scopeActive($query): Builder
    {
        return $query->where('active_status', true);
    }

    /**
     * Scope to get users with completed profiles
     */
    public function scopeProfileCompleted($query): Builder
    {
        return $query->whereNotNull('profile_completed_at');
    }

    /**
     * Scope to get users with verified email
     */
    public function scopeEmailVerified($query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to get dashboard users (power users)
     */
    public function scopeDashboardUsers($query): Builder
    {
        return $query->whereIn('user_type', ['admin', 'supervisor', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Scope to get end users (students and parents)
     */
    public function scopeEndUsers($query): Builder
    {
        return $query->whereIn('user_type', ['student', 'parent']);
    }

    /**
     * Boot method to add global scopes
     */
    protected static function booted()
    {
        // Global scope temporarily disabled to prevent memory exhaustion
        // TODO: Implement tenant scoping at the application level instead of model level
        // static::addGlobalScope('tenant', function (Builder $builder) {
        //     // Implementation needed for proper multi-tenant scoping
        // });
    }

    /**
     * Filament User Interface Implementation
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Super admins can access ALL panels
        if ($this->isSuperAdmin()) {
            return true;
        }

        // For regular users, check specific panel permissions
        switch ($panel->getId()) {
            case 'admin':
                return false; // Only super admins can access admin panel

            case 'academy':
                return in_array($this->user_type, ['admin', 'quran_teacher', 'academic_teacher', 'supervisor']);

            case 'teacher':
                // Only Quran teachers can access the teacher panel
                return $this->user_type === 'quran_teacher';

            case 'academic-teacher':
                // Only academic teachers can access the academic teacher panel
                return $this->user_type === 'academic_teacher';

            case 'supervisor':
                return $this->isSupervisor();

            default:
                return false;
        }
    }

    /**
     * Filament Tenancy Interface Implementation
     */
    public function getTenants(Panel $panel): Collection
    {
        // Only apply tenancy to panels that have tenancy configured
        // Admin panel should NOT use tenancy at all
        if (! in_array($panel->getId(), ['academy', 'teacher', 'supervisor', 'academic-teacher'])) {
            return Academy::where('id', -1)->get(); // Empty collection for non-tenant panels
        }

        // For tenant-enabled panels:
        // Super admins can access all academies
        if ($this->isSuperAdmin()) {
            return Academy::all();
        }

        // Regular users can only access their assigned academy
        if ($this->academy) {
            return Academy::where('id', $this->academy_id)->get();
        }

        return Academy::where('id', -1)->get(); // Empty eloquent collection
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // Super admins can access any academy
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Regular users can only access their assigned academy
        return $this->academy_id === $tenant->id;
    }

    /**
     * Chatify Integration Methods
     */
    
    /**
     * Get the user's display name for Chatify
     */
    public function getChatifyName(): string
    {
        // For students, use their student profile name
        if ($this->isStudent() && $this->studentProfile) {
            $firstName = $this->studentProfile->first_name ?? '';
            $lastName = $this->studentProfile->last_name ?? '';
            return trim($firstName . ' ' . $lastName) ?: $this->name;
        }
        
        // For Quran teachers, use their profile name
        if ($this->isQuranTeacher() && $this->quranTeacherProfile) {
            return $this->quranTeacherProfile->full_name ?? $this->name;
        }
        
        // For Academic teachers, use their profile name
        if ($this->isAcademicTeacher() && $this->academicTeacherProfile) {
            return $this->academicTeacherProfile->full_name ?? $this->name;
        }
        
        // For parents, use their parent profile name
        if ($this->isParent() && $this->parentProfile) {
            $firstName = $this->parentProfile->first_name ?? '';
            $lastName = $this->parentProfile->last_name ?? '';
            return trim($firstName . ' ' . $lastName) ?: $this->name;
        }
        
        // Default to full name or name field
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        }
        
        return $this->name;
    }
    
    /**
     * Get the user's avatar URL for Chatify
     */
    public function getChatifyAvatar(): ?string
    {
        // Check if user has a direct avatar
        if ($this->avatar) {
            // If it's a full URL, return as is
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            // Otherwise, assume it's a path in storage
            return asset('storage/' . $this->avatar);
        }
        
        // For students, check their profile avatar
        if ($this->isStudent() && $this->studentProfile && $this->studentProfile->avatar) {
            return asset('storage/' . $this->studentProfile->avatar);
        }
        
        // For Quran teachers, check their profile avatar
        if ($this->isQuranTeacher() && $this->quranTeacherProfile && $this->quranTeacherProfile->avatar) {
            return asset('storage/' . $this->quranTeacherProfile->avatar);
        }
        
        // For Academic teachers, check their profile avatar
        if ($this->isAcademicTeacher() && $this->academicTeacherProfile && $this->academicTeacherProfile->avatar) {
            return asset('storage/' . $this->academicTeacherProfile->avatar);
        }
        
        // For parents, check their profile avatar
        if ($this->isParent() && $this->parentProfile && $this->parentProfile->avatar) {
            return asset('storage/' . $this->parentProfile->avatar);
        }
        
        // Return null to use Chatify's default avatar generation
        return null;
    }
    
    /**
     * Get user info formatted for Chatify
     */
    public function getChatifyInfo(): array
    {
        return [
            'name' => $this->getChatifyName(),
            'avatar' => $this->getChatifyAvatar(),
            'role' => $this->getUserTypeLabel(),
            'academy' => $this->academy ? $this->academy->name : null,
        ];
    }
    
    /**
     * Get user type label in Arabic
     */
    public function getUserTypeLabel(): string
    {
        $labels = [
            'super_admin' => 'مدير النظام',
            'admin' => 'مدير الأكاديمية',
            'academy_admin' => 'مدير الأكاديمية',
            'quran_teacher' => 'معلم قرآن',
            'academic_teacher' => 'معلم أكاديمي',
            'supervisor' => 'مشرف',
            'student' => 'طالب',
            'parent' => 'ولي أمر',
        ];
        
        return $labels[$this->user_type] ?? $this->user_type;
    }
    
    /**
     * Chat Groups Relationships
     */
    
    /**
     * Get chat groups the user owns
     */
    public function ownedChatGroups(): HasMany
    {
        return $this->hasMany(ChatGroup::class, 'owner_id');
    }
    
    /**
     * Get chat groups the user is a member of
     */
    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_members', 'user_id', 'group_id')
                    ->withPivot(['role', 'can_send_messages', 'is_muted', 'joined_at', 'last_read_at', 'unread_count'])
                    ->withTimestamps();
    }
    
    /**
     * Get chat group memberships
     */
    public function chatGroupMemberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class, 'user_id');
    }
}
