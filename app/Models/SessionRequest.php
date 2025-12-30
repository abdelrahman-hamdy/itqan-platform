<?php

namespace App\Models;

use App\Enums\SessionRequestStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * SessionRequest Model
 *
 * @property int $id
 * @property int $academy_id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property int|null $subject_id
 * @property int|null $grade_level_id
 * @property string $request_code
 * @property int|null $sessions_per_week
 * @property float|null $hourly_rate
 * @property float|null $total_monthly_cost
 * @property bool $is_trial_request
 * @property string $status
 * @property array|null $proposed_schedule
 * @property array|null $current_proposal
 * @property string|null $initial_message
 * @property string|null $teacher_response
 * @property string|null $latest_message
 * @property \Carbon\Carbon|null $last_activity_at
 * @property bool $trial_session_completed
 * @property \Carbon\Carbon|null $trial_session_date
 * @property string|null $trial_session_feedback
 * @property \Carbon\Carbon|null $teacher_responded_at
 * @property \Carbon\Carbon|null $agreed_at
 * @property \Carbon\Carbon|null $payment_completed_at
 * @property \Carbon\Carbon|null $expires_at
 * @property int|null $created_subscription_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SessionRequest extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'grade_level_id',
        'request_code',
        'sessions_per_week',
        'hourly_rate',
        'total_monthly_cost',
        'is_trial_request',
        'status',
        'proposed_schedule',
        'current_proposal',
        'initial_message',
        'teacher_response',
        'latest_message',
        'last_activity_at',
        'trial_session_completed',
        'trial_session_date',
        'trial_session_feedback',
        'teacher_responded_at',
        'agreed_at',
        'payment_completed_at',
        'expires_at',
        'created_subscription_id',
    ];

    protected $casts = [
        'status' => SessionRequestStatus::class,
        'proposed_schedule' => 'array',
        'current_proposal' => 'array',
        'is_trial_request' => 'boolean',
        'trial_session_completed' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'total_monthly_cost' => 'decimal:2',
        'last_activity_at' => 'datetime',
        'trial_session_date' => 'datetime',
        'teacher_responded_at' => 'datetime',
        'agreed_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the academy that owns the session request.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the student that made the request.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher for the request.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the academic teacher profile.
     */
    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id', 'user_id');
    }

    /**
     * Get the subject for the request.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    /**
     * Get the grade level for the request.
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Get the subscription created from this request.
     */
    public function createdSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class, 'created_subscription_id');
    }

    /**
     * Get the subscription that references this request.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(AcademicSubscription::class, 'session_request_id');
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', SessionRequestStatus::PENDING->value);
    }

    /**
     * Scope for agreed requests.
     */
    public function scopeAgreed($query)
    {
        return $query->where('status', SessionRequestStatus::AGREED->value);
    }

    /**
     * Scope for paid requests.
     */
    public function scopePaid($query)
    {
        return $query->where('status', SessionRequestStatus::PAID->value);
    }

    /**
     * Scope for expired requests.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SessionRequestStatus::EXPIRED->value)
            ->orWhere(function ($q) {
                $q->whereNotIn('status', [
                    SessionRequestStatus::PAID->value,
                    SessionRequestStatus::CANCELLED->value,
                    'rejected'
                ])
                    ->where('expires_at', '<', now());
            });
    }

    /**
     * Check if the request is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === SessionRequestStatus::EXPIRED ||
            ($this->expires_at && $this->expires_at->isPast() && !in_array($this->status, [
                SessionRequestStatus::PAID,
                SessionRequestStatus::CANCELLED,
                'rejected'
            ]));
    }

    /**
     * Check if the request can be negotiated.
     */
    public function canNegotiate(): bool
    {
        return in_array($this->status, [
            SessionRequestStatus::PENDING,
            'teacher_proposed',
            'student_negotiating',
            'teacher_revising'
        ]) && !$this->isExpired();
    }

    /**
     * Generate a unique request code.
     */
    public static function generateRequestCode(int $academyId): string
    {
        $prefix = 'SR';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefix}-{$academyId}-{$timestamp}-{$random}";
    }
}
