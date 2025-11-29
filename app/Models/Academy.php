<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\Timezone;
use App\Enums\TailwindColor;

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
        'country',
        'timezone',
        'currency',
        'academic_settings',
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
        'country' => Country::class,
        'currency' => Currency::class,
        'timezone' => Timezone::class,
        'brand_color' => TailwindColor::class,
        'secondary_color' => TailwindColor::class,
        'academic_settings' => 'array',
    ];

    protected $attributes = [
        'brand_color' => TailwindColor::SKY->value,
        'secondary_color' => TailwindColor::EMERALD->value,
        'theme' => 'light',
        'country' => Country::SAUDI_ARABIA->value,
        'timezone' => Timezone::RIYADH->value,
        'currency' => Currency::SAR->value,
        'is_active' => true,
        'allow_registration' => true,
        'maintenance_mode' => false,
    ];

    /**
     * Get the route key for the model (use subdomain for tenant routing)
     */
    public function getRouteKeyName(): string
    {
        return 'subdomain';
    }

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
     * Get the academy settings
     */
    public function settings(): HasOne
    {
        return $this->hasOne(AcademySettings::class);
    }

    /**
     * Get or create academy settings
     */
    public function getOrCreateSettings(): AcademySettings
    {
        return AcademySettings::firstOrCreate(
            ['academy_id' => $this->id],
            []
        );
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
        return $this->hasMany(User::class)->whereIn('user_type', ['quran_teacher', 'academic_teacher']);
    }

    /**
     * Get students in this academy
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('user_type', 'student');
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
        return $this->hasMany(User::class)->where('user_type', 'parent');
    }

    /**
     * Get supervisors in this academy
     */
    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class)->where('user_type', 'supervisor');
    }

    /**
     * Get subjects in this academy
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(AcademicSubject::class);
    }

    /**
     * Get grade levels in this academy
     */
    public function gradeLevels(): HasMany
    {
        return $this->hasMany(AcademicGradeLevel::class);
    }

    /**
     * Get Quran circles in this academy
     */
    public function quranCircles(): HasMany
    {
        return $this->hasMany(QuranCircle::class);
    }

    /**
     * Get academic teacher profiles in this academy
     */
    public function academicTeacherProfiles(): HasMany
    {
        return $this->hasMany(AcademicTeacherProfile::class);
    }

    /**
     * Get Quran teacher profiles in this academy
     */
    public function quranTeacherProfiles(): HasMany
    {
        return $this->hasMany(QuranTeacherProfile::class);
    }

    /**
     * Get all quizzes for this academy
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
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
        return $this->users()->whereIn('user_type', ['quran_teacher', 'academic_teacher'])->count();
    }

    /**
     * Get students count
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->users()->where('user_type', 'student')->count();
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
