<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SessionRequest extends Model
{
    use HasFactory;

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
        return $query->where('status', 'pending');
    }

    /**
     * Scope for agreed requests.
     */
    public function scopeAgreed($query)
    {
        return $query->where('status', 'agreed');
    }

    /**
     * Scope for paid requests.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for expired requests.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotIn('status', ['paid', 'cancelled', 'rejected'])
                    ->where('expires_at', '<', now());
            });
    }

    /**
     * Check if the request is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->expires_at && $this->expires_at->isPast() && !in_array($this->status, ['paid', 'cancelled', 'rejected']));
    }

    /**
     * Check if the request can be negotiated.
     */
    public function canNegotiate(): bool
    {
        return in_array($this->status, ['pending', 'teacher_proposed', 'student_negotiating', 'teacher_revising'])
            && !$this->isExpired();
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
