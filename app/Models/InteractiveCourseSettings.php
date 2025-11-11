<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractiveCourseSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'default_teacher_payment_type',
        'min_teacher_payment',
        'max_discount_percentage',
        'min_course_duration_weeks',
        'max_students_per_course',
        'auto_create_sessions',
        'require_attendance_minimum',
        'auto_create_google_meet',
        'send_reminder_notifications',
        'certificate_auto_generation',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_teacher_payment' => 'decimal:2',
        'max_discount_percentage' => 'decimal:2',
        'min_course_duration_weeks' => 'integer',
        'max_students_per_course' => 'integer',
        'auto_create_sessions' => 'boolean',
        'require_attendance_minimum' => 'decimal:2',
        'auto_create_google_meet' => 'boolean',
        'send_reminder_notifications' => 'boolean',
        'certificate_auto_generation' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Helper methods
     */
    public function getDefaultTeacherPaymentTypeInArabicAttribute(): string
    {
        return match($this->default_teacher_payment_type) {
            'fixed' => 'مبلغ ثابت',
            'per_student' => 'لكل طالب',
            'per_session' => 'لكل جلسة',
            default => $this->default_teacher_payment_type,
        };
    }

    /**
     * Static method to get settings for an academy
     */
    public static function getForAcademy(int $academyId): self
    {
        return static::firstOrCreate(
            ['academy_id' => $academyId],
            [
                'default_teacher_payment_type' => 'fixed',
                'min_teacher_payment' => 100,
                'max_discount_percentage' => 20,
                'min_course_duration_weeks' => 4,
                'max_students_per_course' => 30,
                'auto_create_sessions' => true,
                'require_attendance_minimum' => 75,
                'auto_create_google_meet' => true,
                'send_reminder_notifications' => true,
                'certificate_auto_generation' => false,
                'created_by' => auth()->id() ?? 1,
            ]
        );
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }
}
