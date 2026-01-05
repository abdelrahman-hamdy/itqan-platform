<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Academy Settings Model
 *
 * Stores academy-level default configurations for sessions, attendance, and trials
 */
class AcademySettings extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        // Note: timezone is NOT here - use Academy->timezone instead (authoritative source)
        // AcademyContextService::getTimezone() reads from Academy model
        'default_session_duration',
        'default_preparation_minutes',
        'default_buffer_minutes',
        'default_late_tolerance_minutes',
        'default_attendance_threshold_percentage',
        'trial_session_duration',
        'trial_expiration_days',
        'settings',
    ];

    protected $casts = [
        'default_session_duration' => 'integer',
        'default_preparation_minutes' => 'integer',
        'default_buffer_minutes' => 'integer',
        'default_late_tolerance_minutes' => 'integer',
        'default_attendance_threshold_percentage' => 'decimal:2',
        'trial_session_duration' => 'integer',
        'trial_expiration_days' => 'integer',
        'settings' => 'array',
    ];

    protected $attributes = [
        // Note: timezone removed - use Academy->timezone (Timezone enum) instead
        'default_session_duration' => 60,
        'default_preparation_minutes' => 10,
        'default_buffer_minutes' => 5,
        'default_late_tolerance_minutes' => 15,
        'default_attendance_threshold_percentage' => 80.00,
        'trial_session_duration' => 30,
        'trial_expiration_days' => 7,
    ];

    // ========================================
    // Relationships
    // ========================================

    /**
     * Get the academy these settings belong to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Get a specific setting value with fallback to defaults
     */
    public function getSetting(string $key, $default = null)
    {
        // Check if it's a direct attribute
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        // Check in JSON settings
        if ($this->settings && isset($this->settings[$key])) {
            return $this->settings[$key];
        }

        return $default;
    }

    /**
     * Set a custom setting in the JSON field
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Get all attendance-related settings as an array
     */
    public function getAttendanceSettings(): array
    {
        return [
            'preparation_minutes' => $this->default_preparation_minutes,
            'buffer_minutes' => $this->default_buffer_minutes,
            'late_tolerance_minutes' => $this->default_late_tolerance_minutes,
            'attendance_threshold_percentage' => $this->default_attendance_threshold_percentage,
        ];
    }

    /**
     * Get trial session settings as an array
     */
    public function getTrialSettings(): array
    {
        return [
            'duration' => $this->trial_session_duration,
            'expiration_days' => $this->trial_expiration_days,
        ];
    }

    // ========================================
    // Static Factory Methods
    // ========================================

    /**
     * Get or create settings for an academy
     */
    public static function getForAcademy(Academy $academy): self
    {
        return static::firstOrCreate(
            ['academy_id' => $academy->id],
            []
        );
    }
}
