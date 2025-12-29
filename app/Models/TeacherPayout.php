<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Support\Facades\DB;

class TeacherPayout extends Model
{
    use HasFactory, SoftDeletes, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'teacher_type',
        'teacher_id',
        'payout_code',
        'payout_month',
        'total_amount',
        'sessions_count',
        'breakdown',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'paid_by',
        'paid_at',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => PayoutStatus::class,
        'payout_month' => 'date',
        'total_amount' => 'decimal:2',
        'sessions_count' => 'integer',
        'breakdown' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate payout code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payout_code)) {
                $model->payout_code = static::generatePayoutCode(
                    $model->academy_id,
                    $model->payout_month
                );
            }
        });
    }

    /**
     * Generate unique payout code: PO-{academyId}-{YYYYMM}-{sequence}
     */
    public static function generatePayoutCode(int $academyId, $payoutMonth): string
    {
        $monthStr = \Carbon\Carbon::parse($payoutMonth)->format('Ym');
        $prefix = sprintf('PO-%02d-%s-', $academyId, $monthStr);

        return DB::transaction(function () use ($prefix, $academyId, $payoutMonth) {
            // Get count of payouts for this month to generate sequence
            $count = static::where('academy_id', $academyId)
                ->whereYear('payout_month', '=', \Carbon\Carbon::parse($payoutMonth)->year)
                ->whereMonth('payout_month', '=', \Carbon\Carbon::parse($payoutMonth)->month)
                ->lockForUpdate()
                ->count();

            return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the teacher (polymorphic relationship)
     */
    public function teacher(): MorphTo
    {
        return $this->morphTo('teacher', 'teacher_type', 'teacher_id');
    }

    /**
     * Get the earnings included in this payout
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(TeacherEarning::class, 'payout_id');
    }

    /**
     * Get the academy this payout belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the user who approved this payout
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who marked this payout as paid
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the user who rejected this payout
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Check if payout can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === PayoutStatus::PENDING;
    }

    /**
     * Check if payout can be rejected
     */
    public function canReject(): bool
    {
        return in_array($this->status, [PayoutStatus::PENDING, PayoutStatus::APPROVED]);
    }

    /**
     * Check if payout can be marked as paid
     */
    public function canMarkPaid(): bool
    {
        return $this->status === PayoutStatus::APPROVED;
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by month
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);
        return $query->where('payout_month', $monthDate);
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
     * Scope for pending payouts
     */
    public function scopePending($query)
    {
        return $query->where('status', PayoutStatus::PENDING->value);
    }

    /**
     * Scope for approved payouts
     */
    public function scopeApproved($query)
    {
        return $query->where('status', PayoutStatus::APPROVED->value);
    }

    /**
     * Scope for paid payouts
     */
    public function scopePaid($query)
    {
        return $query->where('status', PayoutStatus::PAID->value);
    }

    /**
     * Scope for rejected payouts
     */
    public function scopeRejected($query)
    {
        return $query->where('status', PayoutStatus::REJECTED->value);
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
        return number_format($this->total_amount, 2) . ' ر.س';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في انتظار الموافقة',
            'approved' => 'تمت الموافقة',
            'paid' => 'تم الدفع',
            'rejected' => 'مرفوض',
            default => $this->status,
        };
    }

    /**
     * Get month name in Arabic
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];

        $month = \Carbon\Carbon::parse($this->payout_month)->month;
        $year = \Carbon\Carbon::parse($this->payout_month)->year;

        return $months[$month] . ' ' . $year;
    }
}
