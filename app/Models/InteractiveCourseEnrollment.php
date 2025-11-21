<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class InteractiveCourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'course_id',
        'student_id',
        'enrolled_by',
        'enrollment_date',
        'payment_status',
        'payment_amount',
        'discount_applied',
        'enrollment_status',
        'completion_percentage',
        'final_grade',
        'attendance_count',
        'total_possible_attendance',
        'certificate_issued',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'payment_amount' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'attendance_count' => 'integer',
        'total_possible_attendance' => 'integer',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function certificate(): MorphOne
    {
        return $this->morphOne(Certificate::class, 'certificateable');
    }

    /**
     * Helper methods
     */
    public function getPaymentStatusInArabicAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'في الانتظار',
            'paid' => 'مدفوع',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    public function getEnrollmentStatusInArabicAttribute(): string
    {
        return match($this->enrollment_status) {
            'enrolled' => 'مسجل',
            'dropped' => 'منسحب',
            'completed' => 'مكتمل',
            'expelled' => 'مفصول',
            default => $this->enrollment_status,
        };
    }

    public function getNetPaymentAmount(): float
    {
        return $this->payment_amount - $this->discount_applied;
    }

    public function getAttendancePercentage(): float
    {
        if ($this->total_possible_attendance == 0) {
            return 0;
        }
        return ($this->attendance_count / $this->total_possible_attendance) * 100;
    }

    public function isActive(): bool
    {
        return $this->enrollment_status === 'enrolled';
    }

    public function hasPassedCourse(): bool
    {
        return $this->final_grade && $this->final_grade >= 60; // Assuming 60% is passing grade
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeActive($query)
    {
        return $query->where('enrollment_status', 'enrolled');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('enrollment_status', 'completed');
    }
}
