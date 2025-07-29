<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'role',
        'status',
        'bio',
        'teacher_type',
        'qualification_degree',
        'qualification_text',
        'university',
        'years_experience',
        'has_ijazah',
        'student_session_price',
        'teacher_session_price',
        'parent_phone',
        'parent_id',
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
            'password' => 'hashed',
            'has_ijazah' => 'boolean',
            'student_session_price' => 'decimal:2',
            'teacher_session_price' => 'decimal:2',
            'years_experience' => 'integer',
        ];
    }

    protected $attributes = [
        'role' => 'student',
        'status' => 'pending',
        'has_ijazah' => false,
    ];

    /**
     * Get the academy this user belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the parent user (for students)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get children users (for parents)
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get academies where this user is admin
     */
    public function managedAcademies(): HasMany
    {
        return $this->hasMany(Academy::class, 'admin_id');
    }

    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the display name (uses full_name or falls back to name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->name;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is academy admin
     */
    public function isAcademyAdmin(): bool
    {
        return $this->role === 'academy_admin';
    }

    /**
     * Check if user is teacher
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user is parent
     */
    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    /**
     * Check if user is supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }



    /**
     * Scope to get users by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get users by academy
     */
    public function scopeByAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Get avatar URL with fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->display_name) . '&background=0ea5e9&color=fff';
    }
}
