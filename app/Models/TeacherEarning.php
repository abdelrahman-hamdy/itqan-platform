<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\ScopedToAcademy;

class TeacherEarning extends Model
{
    use HasFactory, SoftDeletes, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'teacher_type',
        'teacher_id',
        'session_type',
        'session_id',
        'amount',
        'calculation_method',
        'rate_snapshot',
        'calculation_metadata',
        'earning_month',
        'session_completed_at',
        'calculated_at',
        'payout_id',
        'is_finalized',
        'is_disputed',
        'dispute_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate_snapshot' => 'array',
        'calculation_metadata' => 'array',
        'earning_month' => 'date',
        'session_completed_at' => 'datetime',
        'calculated_at' => 'datetime',
        'is_finalized' => 'boolean',
        'is_disputed' => 'boolean',
    ];

    /**
     * Get the teacher (polymorphic relationship)
     */
    public function teacher(): MorphTo
    {
        return $this->morphTo('teacher', 'teacher_type', 'teacher_id');
    }

    /**
     * Get the session (polymorphic relationship)
     */
    public function session(): MorphTo
    {
        return $this->morphTo('session', 'session_type', 'session_id');
    }

    /**
     * Get the payout this earning belongs to
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(TeacherPayout::class, 'payout_id');
    }

    /**
     * Get the academy this earning belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Scope to filter earnings for a specific month
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);
        return $query->where('earning_month', $monthDate);
    }

    /**
     * Scope to get unpaid earnings
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('payout_id')
                     ->where('is_finalized', false)
                     ->where('is_disputed', false);
    }

    /**
     * Scope to get finalized earnings
     */
    public function scopeFinalized($query)
    {
        return $query->where('is_finalized', true);
    }

    /**
     * Scope to get disputed earnings
     */
    public function scopeDisputed($query)
    {
        return $query->where('is_disputed', true);
    }

    /**
     * Scope to filter by teacher
     */
    public function scopeForTeacher($query, string $teacherType, int $teacherId)
    {
        return $query->where('teacher_type', $teacherType)
                     ->where('teacher_id', $teacherId);
    }

    /**
     * Scope to filter by session
     */
    public function scopeForSession($query, string $sessionType, int $sessionId)
    {
        return $query->where('session_type', $sessionType)
                     ->where('session_id', $sessionId);
    }

    /**
     * Get the teacher's name (helper method)
     */
    public function getTeacherNameAttribute(): string
    {
        if (!$this->teacher) {
            return 'Unknown';
        }

        return $this->teacher->first_name . ' ' . $this->teacher->last_name;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ر.س';
    }

    /**
     * Get calculation method label in Arabic
     */
    public function getCalculationMethodLabelAttribute(): string
    {
        return match($this->calculation_method) {
            'individual_rate' => 'جلسة فردية',
            'group_rate' => 'جلسة جماعية',
            'per_session' => 'حسب الجلسة',
            'per_student' => 'حسب الطالب',
            'fixed' => 'مبلغ ثابت',
            default => $this->calculation_method,
        };
    }
}
