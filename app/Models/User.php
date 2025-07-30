<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'role', // Keep for backwards compatibility during transition
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
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
     * Get full name attribute
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'مستخدم غير محدد';
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
        return in_array($this->user_type, ['admin', 'supervisor', 'quran_teacher', 'academic_teacher']);
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
     * Scope to filter by user type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope to filter by academy
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Scope to get active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
