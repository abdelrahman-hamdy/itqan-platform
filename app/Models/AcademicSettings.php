<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'sessions_per_week_options',
        'default_session_duration_minutes',
        'default_booking_fee',
        'currency',
        'enable_trial_sessions',
        'trial_session_duration_minutes',
        'trial_session_fee',
        'subscription_pause_max_days',
        'auto_renewal_reminder_days',
        'allow_mid_month_cancellation',
        'enabled_payment_methods',
        'auto_create_google_meet_links',
        'google_meet_account_email',
        'courses_start_on_schedule',
        'course_enrollment_deadline_days',
        'allow_late_enrollment',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'sessions_per_week_options' => 'array',
        'default_session_duration_minutes' => 'integer',
        'default_booking_fee' => 'decimal:2',
        'enable_trial_sessions' => 'boolean',
        'trial_session_duration_minutes' => 'integer',
        'trial_session_fee' => 'decimal:2',
        'subscription_pause_max_days' => 'integer',
        'auto_renewal_reminder_days' => 'integer',
        'allow_mid_month_cancellation' => 'boolean',
        'enabled_payment_methods' => 'array',
        'auto_create_google_meet_links' => 'boolean',
        'courses_start_on_schedule' => 'boolean',
        'course_enrollment_deadline_days' => 'integer',
        'allow_late_enrollment' => 'boolean',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Creator relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater relationship
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get formatted sessions per week options for display
     */
    public function getSessionsPerWeekOptionsTextAttribute(): string
    {
        $options = $this->sessions_per_week_options ?? [1, 2, 3, 4];
        return implode(', ', array_map(fn($option) => $option . ' حصة', $options));
    }

    /**
     * Get payment methods as formatted text
     */
    public function getPaymentMethodsTextAttribute(): string
    {
        $methods = $this->enabled_payment_methods ?? ['tab_pay', 'paymob'];
        $methodNames = [
            'tab_pay' => 'Tab Pay',
            'paymob' => 'Paymob',
        ];
        
        return implode(', ', array_map(fn($method) => $methodNames[$method] ?? $method, $methods));
    }

    /**
     * Scope for academy
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Get settings for a specific academy (create if not exists)
     */
    public static function getForAcademy($academyId): self
    {
        return self::firstOrCreate(
            ['academy_id' => $academyId],
            [
                'sessions_per_week_options' => [1, 2, 3, 4],
                'enabled_payment_methods' => ['tab_pay', 'paymob'],
                'default_session_duration_minutes' => 60,
                'currency' => 'SAR',
                'enable_trial_sessions' => true,
                'trial_session_duration_minutes' => 30,
                'auto_create_google_meet_links' => true,
                'courses_start_on_schedule' => true,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * Boot method to set default values for JSON fields
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->sessions_per_week_options)) {
                $model->sessions_per_week_options = [1, 2, 3, 4];
            }
            if (empty($model->enabled_payment_methods)) {
                $model->enabled_payment_methods = ['tab_pay', 'paymob'];
            }
        });
    }
}
