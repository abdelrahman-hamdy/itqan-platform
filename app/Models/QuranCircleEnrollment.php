<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\SessionSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QuranCircleEnrollment Model
 *
 * Represents a student's enrollment in a group Quran circle.
 * Replaces the old pivot table approach with a proper model for better tracking.
 *
 * DECOUPLED ARCHITECTURE:
 * - This model can exist independently from subscriptions
 * - subscription_id is NULLABLE (enrollments don't require a subscription)
 * - Students can be enrolled in circles for trials before having a subscription
 * - Subscription links payment/access tracking to the educational enrollment
 *
 * Table: quran_circle_students (legacy name, kept for backward compatibility)
 *
 * @property int $id
 * @property int $circle_id
 * @property int $student_id
 * @property int|null $subscription_id
 * @property Carbon|null $enrolled_at
 * @property string $status
 * @property int $attendance_count
 * @property int $missed_sessions
 * @property int $makeup_sessions_used
 * @property string|null $current_level
 * @property string|null $progress_notes
 * @property float|null $parent_rating
 * @property float|null $student_rating
 * @property Carbon|null $completion_date
 * @property bool $certificate_issued
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class QuranCircleEnrollment extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     * Using legacy table name for backward compatibility.
     */
    protected $table = 'quran_circle_students';

    protected $fillable = [
        'circle_id',
        'student_id',
        'subscription_id',
        'enrolled_at',
        'status',
        'attendance_count',
        'missed_sessions',
        'makeup_sessions_used',
        'current_level',
        'progress_notes',
        'parent_rating',
        'student_rating',
        'completion_date',
        'certificate_issued',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completion_date' => 'datetime',
        'attendance_count' => 'integer',
        'missed_sessions' => 'integer',
        'makeup_sessions_used' => 'integer',
        'parent_rating' => 'decimal:1',
        'student_rating' => 'decimal:1',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Enrollment status constants
     */
    const STATUS_ENROLLED = 'enrolled';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DROPPED = 'dropped';

    const STATUS_SUSPENDED = 'suspended';

    const STATUSES = [
        self::STATUS_ENROLLED => 'مسجل',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_DROPPED => 'منسحب',
        self::STATUS_SUSPENDED => 'معلق',
    ];

    // Relationships

    /**
     * Get the circle for this enrollment
     */
    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'circle_id');
    }

    /**
     * Get the student user for this enrollment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the subscription linked to this enrollment (if any)
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'subscription_id');
    }

    /**
     * Get the academy through the circle
     */
    public function getAcademyAttribute(): ?Academy
    {
        return $this->circle?->academy;
    }

    /**
     * Get the teacher through the circle
     */
    public function getTeacherAttribute(): ?User
    {
        return $this->circle?->teacher;
    }

    // Accessors

    /**
     * Get status text in Arabic
     */
    public function getStatusTextAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Check if enrollment has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_id !== null &&
               $this->subscription?->status === SessionSubscriptionStatus::ACTIVE;
    }

    /**
     * Check if enrollment is independent (no subscription linked)
     */
    public function isIndependent(): bool
    {
        return $this->subscription_id === null;
    }

    /**
     * Get the active subscription for this enrollment
     */
    public function getActiveSubscriptionAttribute(): ?QuranSubscription
    {
        if ($this->subscription_id && $this->subscription?->status === SessionSubscriptionStatus::ACTIVE) {
            return $this->subscription;
        }

        return null;
    }

    /**
     * Check if the enrollment is currently active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    // Scopes

    /**
     * Scope to only enrolled (active) students
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ENROLLED);
    }

    /**
     * Scope to students with active subscriptions
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscription', function ($q) {
            $q->where('status', SessionSubscriptionStatus::ACTIVE);
        });
    }

    /**
     * Scope to independent enrollments (no subscription)
     */
    public function scopeIndependent($query)
    {
        return $query->whereNull('subscription_id');
    }

    /**
     * Scope by circle
     */
    public function scopeForCircle($query, $circleId)
    {
        return $query->where('circle_id', $circleId);
    }

    /**
     * Scope by student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // Methods

    /**
     * Link this enrollment to a subscription
     */
    public function linkToSubscription(QuranSubscription $subscription): self
    {
        $this->update(['subscription_id' => $subscription->id]);

        return $this;
    }

    /**
     * Unlink subscription from this enrollment
     */
    public function unlinkSubscription(): self
    {
        $this->update(['subscription_id' => null]);

        return $this;
    }

    /**
     * Record attendance for this student
     */
    public function recordAttendance(): self
    {
        $this->increment('attendance_count');

        return $this;
    }

    /**
     * Record missed session for this student
     */
    public function recordMissedSession(): self
    {
        $this->increment('missed_sessions');

        return $this;
    }

    /**
     * Mark enrollment as completed
     */
    public function complete(): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completion_date' => now(),
        ]);

        return $this;
    }

    /**
     * Drop enrollment (student withdrew)
     */
    public function drop(): self
    {
        $this->update(['status' => self::STATUS_DROPPED]);

        return $this;
    }

    /**
     * Suspend enrollment
     */
    public function suspend(): self
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);

        return $this;
    }

    /**
     * Reactivate enrollment
     */
    public function reactivate(): self
    {
        $this->update(['status' => self::STATUS_ENROLLED]);

        return $this;
    }

    // Boot methods

    protected static function booted()
    {
        static::creating(function ($enrollment) {
            if (empty($enrollment->enrolled_at)) {
                $enrollment->enrolled_at = now();
            }
            if (empty($enrollment->status)) {
                $enrollment->status = self::STATUS_ENROLLED;
            }
            if (empty($enrollment->attendance_count)) {
                $enrollment->attendance_count = 0;
            }
            if (empty($enrollment->missed_sessions)) {
                $enrollment->missed_sessions = 0;
            }
            if (empty($enrollment->makeup_sessions_used)) {
                $enrollment->makeup_sessions_used = 0;
            }
            if (! isset($enrollment->certificate_issued)) {
                $enrollment->certificate_issued = false;
            }
        });
    }
}
