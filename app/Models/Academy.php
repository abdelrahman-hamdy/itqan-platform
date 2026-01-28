<?php

namespace App\Models;

use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\GradientPalette;
use App\Enums\TailwindColor;
use App\Enums\Timezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Academy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_en',
        'subdomain',
        'description',
        'email',
        'phone',
        'logo',
        'favicon',
        'brand_color',
        'gradient_palette',
        'country',
        'timezone',
        'currency',
        'academic_settings',
        'quran_settings',
        'notification_settings',
        'payment_settings',
        'is_active',
        'allow_registration',
        'maintenance_mode',
        'admin_id',
        'total_revenue',
        'monthly_revenue',
        'pending_payments',
        'active_subscriptions',
        'growth_rate',
        // Design Settings
        'sections_order',
        'hero_visible', 'hero_template', 'hero_heading', 'hero_subheading', 'hero_image', 'hero_show_in_nav',
        'stats_visible', 'stats_template', 'stats_heading', 'stats_subheading', 'stats_show_in_nav',
        'reviews_visible', 'reviews_template', 'reviews_heading', 'reviews_subheading', 'reviews_show_in_nav',
        'quran_visible', 'quran_template', 'quran_heading', 'quran_subheading', 'quran_show_in_nav',
        'academic_visible', 'academic_template', 'academic_heading', 'academic_subheading', 'academic_show_in_nav',
        'courses_visible', 'courses_template', 'courses_heading', 'courses_subheading', 'courses_show_in_nav',
        'features_visible', 'features_template', 'features_heading', 'features_subheading', 'features_show_in_nav',
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
        'gradient_palette' => GradientPalette::class,
        'academic_settings' => 'array',
        'quran_settings' => 'array',
        'notification_settings' => 'array',
        'payment_settings' => 'encrypted:array',
        // Design Settings Casts (sections_order uses custom accessor/mutator)
        'hero_visible' => 'boolean',
        'hero_show_in_nav' => 'boolean',
        'stats_visible' => 'boolean',
        'stats_show_in_nav' => 'boolean',
        'reviews_visible' => 'boolean',
        'reviews_show_in_nav' => 'boolean',
        'quran_visible' => 'boolean',
        'quran_show_in_nav' => 'boolean',
        'academic_visible' => 'boolean',
        'academic_show_in_nav' => 'boolean',
        'courses_visible' => 'boolean',
        'courses_show_in_nav' => 'boolean',
        'features_visible' => 'boolean',
        'features_show_in_nav' => 'boolean',
    ];

    protected $attributes = [
        'brand_color' => TailwindColor::SKY->value,
        'gradient_palette' => GradientPalette::OCEAN_BREEZE->value,
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
     * Check if the academy has an assigned admin
     */
    public function hasAdmin(): bool
    {
        return $this->admin_id !== null;
    }

    /**
     * Assign an admin to this academy
     *
     * @throws \InvalidArgumentException If user is not an admin
     */
    public function assignAdmin(User $admin): void
    {
        if ($admin->user_type !== 'admin') {
            throw new \InvalidArgumentException('User must be an admin');
        }

        $this->update(['admin_id' => $admin->id]);
    }

    /**
     * Remove the admin from this academy
     */
    public function removeAdmin(): void
    {
        $this->update(['admin_id' => null]);
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
     * Check if email notifications are enabled for a given notification category.
     */
    public function isEmailEnabledForCategory(string $category): bool
    {
        $settings = $this->notification_settings;

        if (empty($settings) || empty($settings['email_enabled'])) {
            return false;
        }

        $categories = $settings['email_categories'] ?? [];

        return in_array($category, $categories);
    }

    /**
     * Get the email sender name from notification settings, with fallback.
     */
    public function getEmailFromName(): string
    {
        return $this->notification_settings['email_from_name']
            ?? $this->name
            ?? config('mail.from.name', 'Itqan Platform');
    }

    /**
     * Get payment settings as a DTO for easier access.
     */
    public function getPaymentSettings(): \App\Services\Payment\DTOs\AcademyPaymentSettings
    {
        return \App\Services\Payment\DTOs\AcademyPaymentSettings::fromArray(
            $this->payment_settings
        );
    }

    /**
     * Get academy status display
     */
    public function getStatusDisplayAttribute(): string
    {
        if (! $this->is_active) {
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
        if (! $this->is_active) {
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

    // ========================================
    // SUBSCRIPTION RELATIONSHIPS
    // ========================================

    /**
     * Get Quran subscriptions for this academy
     */
    public function quranSubscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class);
    }

    /**
     * Get Academic subscriptions for this academy
     */
    public function academicSubscriptions(): HasMany
    {
        return $this->hasMany(AcademicSubscription::class);
    }

    /**
     * Get Course subscriptions for this academy
     */
    public function courseSubscriptions(): HasMany
    {
        return $this->hasMany(CourseSubscription::class);
    }

    // ========================================
    // SESSION RELATIONSHIPS
    // ========================================

    /**
     * Get Quran sessions for this academy
     */
    public function quranSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class);
    }

    /**
     * Get Academic sessions for this academy
     */
    public function academicSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }

    /**
     * Get Interactive course sessions for this academy
     */
    public function interactiveCourseSessions(): HasMany
    {
        return $this->hasMany(InteractiveCourseSession::class);
    }

    /**
     * Get Quran individual circles for this academy
     */
    public function quranIndividualCircles(): HasMany
    {
        return $this->hasMany(QuranIndividualCircle::class);
    }

    // ========================================
    // COURSE RELATIONSHIPS
    // ========================================

    /**
     * Get Interactive courses for this academy
     */
    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class);
    }

    /**
     * Get Recorded courses for this academy
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class);
    }

    // ========================================
    // PAYMENT RELATIONSHIPS
    // ========================================

    /**
     * Get all payments for this academy
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

        return $this->subdomain.'.'.$baseDomain;
    }

    /**
     * Get the full URL
     */
    public function getFullUrlAttribute(): string
    {
        $protocol = app()->environment('local') ? 'http' : 'https';

        return $protocol.'://'.$this->full_domain;
    }

    /**
     * Get the localized academy name based on current locale
     * Returns name_en for English locale (if available), otherwise falls back to name
     */
    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && ! empty($this->name_en)) {
            return $this->name_en;
        }

        return $this->name ?? '';
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        // If logo already contains http/https, return as is
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        // Otherwise, prepend the app URL
        return config('app.url').'/storage/'.$this->logo;
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

    /**
     * Get sections order with default value if not set
     */
    public function getSectionsOrderAttribute($value): array
    {
        $defaultOrder = ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];

        // If value is null or empty, return default order
        if ($value === null || $value === '' || $value === '[]') {
            return $defaultOrder;
        }

        // If value is already an array (Laravel 11 casts it automatically)
        if (is_array($value)) {
            // Return default if empty array
            if (empty($value)) {
                return $defaultOrder;
            }

            // Check if it's in repeater format [['section' => 'hero'], ...]
            // Convert to flat format if needed
            if (isset($value[0]) && is_array($value[0]) && isset($value[0]['section'])) {
                return array_column($value, 'section');
            }

            return $value;
        }

        // Otherwise decode JSON
        $decoded = json_decode($value, true);

        // Return default if decoding failed or resulted in empty array
        if (! is_array($decoded) || empty($decoded)) {
            return $defaultOrder;
        }

        // Check if decoded value is in repeater format and convert if needed
        if (isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0]['section'])) {
            return array_column($decoded, 'section');
        }

        return $decoded;
    }

    /**
     * Set sections order with proper JSON encoding
     */
    public function setSectionsOrderAttribute($value): void
    {
        // If value is null or empty array, store as null (accessor will return default)
        if ($value === null || (is_array($value) && empty($value))) {
            $this->attributes['sections_order'] = null;

            return;
        }

        // If value is string (JSON), try to decode it first
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        // Ensure it's an array
        if (! is_array($value)) {
            $this->attributes['sections_order'] = null;

            return;
        }

        // Filter out empty values and reindex
        $value = array_values(array_filter($value, fn ($item) => ! empty($item)));

        // If filtering resulted in empty array, store as null
        if (empty($value)) {
            $this->attributes['sections_order'] = null;

            return;
        }

        // Store as JSON
        $this->attributes['sections_order'] = json_encode($value);
    }
}
