<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Academy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'subdomain',
        'description',
        'email',
        'phone',
        'website',
        'logo',
        'brand_color',
        'secondary_color',
        'theme',
        'timezone',
        'currency',
        'is_active',
        'allow_registration',
        'maintenance_mode',
        'admin_id',
        'total_revenue',
        'monthly_revenue',
        'pending_payments',
        'active_subscriptions',
        'growth_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_registration' => 'boolean',
        'maintenance_mode' => 'boolean',
        'total_revenue' => 'decimal:2',
        'monthly_revenue' => 'decimal:2',
        'pending_payments' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'active_subscriptions' => 'integer',
    ];

    protected $attributes = [
        'brand_color' => '#0ea5e9',
        'secondary_color' => '#10B981',
        'theme' => 'light',
        'timezone' => 'Asia/Riyadh',
        'currency' => 'SAR',
        'is_active' => true,
        'allow_registration' => true,
        'maintenance_mode' => false,
    ];

    /**
     * Get all users belonging to this academy
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the academy admin
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get academy status display
     */
    public function getStatusDisplayAttribute(): string
    {
        if (!$this->is_active) {
            return 'غير نشطة';
        }
        
        if ($this->maintenance_mode) {
            return 'تحت الصيانة';
        }
        
        return 'نشطة';
    }

    /**
     * Get academy status for admin display
     */
    public function getAdminStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->maintenance_mode) {
            return 'maintenance';
        }
        
        return 'active';
    }

    /**
     * Get teachers in this academy
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'teacher');
    }

    /**
     * Get students in this academy
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'student');
    }

    /**
     * Scope for active academies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive academies
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for academies in maintenance mode
     */
    public function scopeMaintenance($query)
    {
        return $query->where('maintenance_mode', true);
    }

    /**
     * Get parents in this academy
     */
    public function parents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'parent');
    }

    /**
     * Get supervisors in this academy
     */
    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'supervisor');
    }

    /**
     * Get the full domain URL
     */
    public function getFullDomainAttribute(): string
    {
        $baseDomain = config('app.domain', 'itqan-platform.test');
        
        // For development, use .test domain
        if (app()->environment('local')) {
            $baseDomain = 'itqan-platform.test';
        }
        
        // If subdomain is empty or 'itqan-academy' (default), return base domain
        if (empty($this->subdomain) || $this->subdomain === 'itqan-academy') {
            return $baseDomain;
        }
        
        return $this->subdomain . '.' . $baseDomain;
    }

    /**
     * Get the full URL
     */
    public function getFullUrlAttribute(): string
    {
        $protocol = app()->environment('local') ? 'http' : 'https';
        return $protocol . '://' . $this->full_domain;
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // If logo already contains http/https, return as is
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        // Otherwise, prepend the app URL
        return config('app.url') . '/storage/' . $this->logo;
    }

    /**
     * Scope to get active academies only (alternative to the one above)
     */
    public function scopeActiveAndAvailable($query)
    {
        return $query->where('is_active', true)->where('maintenance_mode', false);
    }

    /**
     * Get teachers count
     */
    public function getTeachersCountAttribute(): int
    {
        return $this->users()->where('role', 'teacher')->count();
    }

    /**
     * Get students count
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->users()->where('role', 'student')->count();
    }

    /**
     * Get users count
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    // Filament v4 Tenant methods
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): string|int
    {
        return $this->getKey();
    }
}
