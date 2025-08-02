<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

// Profile Models
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\StudentProfile;
use App\Models\ParentProfile;
use App\Models\SupervisorProfile;
use App\Models\AcademicSubject;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

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
     * User status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';

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
        'status',
        // 'role' field removed - using user_type instead
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'avatar',
        'profile_completed_at',
        'is_active',
        'email_verification_token',
        'phone_verification_token',
        'password_reset_token',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
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
            'is_active' => 'boolean',
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
    public function profile()
    {
        return match($this->user_type) {
            'student' => $this->hasOne(StudentProfile::class),
            'quran_teacher' => $this->hasOne(QuranTeacherProfile::class),
            'academic_teacher' => $this->hasOne(AcademicTeacherProfile::class),
            'parent' => $this->hasOne(ParentProfile::class),
            'supervisor' => $this->hasOne(SupervisorProfile::class),
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
     * Get full name attribute
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'مستخدم غير محدد';
    }

    /**
     * Get display name based on profile data
     */
    public function getDisplayNameAttribute(): string
    {
        $profile = $this->profile;
        
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
        return !is_null($this->profile_completed_at);
    }

    /**
     * Check if user email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
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
            'academic_teacher'
        ]);
    }

    /**
     * Get dashboard route based on user type
     */
    public function getDashboardRoute(): string
    {
        return match($this->user_type) {
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
     * Scope to get active users
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_active', true);
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
                return $this->isTeacher();
                
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
        if (!in_array($panel->getId(), ['academy', 'teacher', 'supervisor'])) {
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
}
