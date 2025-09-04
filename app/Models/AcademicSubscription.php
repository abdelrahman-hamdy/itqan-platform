<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'grade_level_id',
        'subject_name',
        'grade_level_name',
        'session_request_id',
        'academic_package_id',
        'subscription_code',
        'subscription_type',
        'sessions_per_week',
        'session_duration_minutes',
        'hourly_rate',
        'sessions_per_month',
        'monthly_amount',
        'discount_amount',
        'final_monthly_amount',
        'currency',
        'billing_cycle',
        'start_date',
        'end_date',
        'next_billing_date',
        'last_payment_date',
        'last_payment_amount',
        'weekly_schedule',
        'timezone',
        'auto_create_google_meet',
        'status',
        'payment_status',
        'has_trial_session',
        'trial_session_used',
        'trial_session_date',
        'trial_session_status',
        'paused_at',
        'resume_date',
        'pause_reason',
        'pause_days_remaining',
        'auto_renewal',
        'renewal_reminder_days',
        'last_reminder_sent',
        'notes',
        'student_notes',
        'teacher_notes',

        'total_sessions_scheduled',
        'total_sessions_completed',
        'total_sessions_missed',
        'completion_rate',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'sessions_per_month' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_monthly_amount' => 'decimal:2',
        'last_payment_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'last_payment_date' => 'date',
        'weekly_schedule' => 'array',
        'auto_create_google_meet' => 'boolean',
        'has_trial_session' => 'boolean',
        'trial_session_used' => 'boolean',
        'trial_session_date' => 'datetime',
        'paused_at' => 'datetime',
        'resume_date' => 'datetime',
        'auto_renewal' => 'boolean',
        'last_reminder_sent' => 'datetime',
        'completion_rate' => 'decimal:2',
    ];

    // Relationships
    public function academicPackage(): BelongsTo
    {
        return $this->belongsTo(AcademicPackage::class, 'academic_package_id');
    }

    protected $attributes = [
        'subscription_type' => 'private',
        'session_duration_minutes' => 60,
        'currency' => 'SAR',
        'billing_cycle' => 'monthly',
        'auto_create_google_meet' => true,
        'status' => 'active',
        'payment_status' => 'current',
        'has_trial_session' => false,
        'trial_session_used' => false,
        'pause_days_remaining' => 0,
        'auto_renewal' => true,
        'renewal_reminder_days' => 7,
        'total_sessions_scheduled' => 0,
        'total_sessions_completed' => 0,
        'total_sessions_missed' => 0,
        'completion_rate' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->subscription_code)) {
                $model->subscription_code = $model->generateSubscriptionCode();
            }

            // Calculate sessions per month and amounts
            if ($model->sessions_per_week && $model->hourly_rate) {
                $model->sessions_per_month = $model->sessions_per_week * 4.33; // Average weeks per month
                $model->monthly_amount = $model->sessions_per_month * $model->hourly_rate;
                $model->final_monthly_amount = $model->monthly_amount - $model->discount_amount;
            }

            // Set next billing date if not set
            if (empty($model->next_billing_date)) {
                $model->next_billing_date = $model->calculateNextBillingDate($model->start_date);
            }
        });
    }

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * العلاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * العلاقة مع المعلم الأكاديمي
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * العلاقة مع بروفايل المعلم الأكاديمي (alternative name to avoid caching issues)
     */
    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * العلاقة مع المادة الدراسية
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * العلاقة مع المرحلة الدراسية
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class);
    }

    /**
     * العلاقة مع طلب الجلسة الأصلي
     */
    public function sessionRequest(): BelongsTo
    {
        return $this->belongsTo(SessionRequest::class);
    }

    /**
     * العلاقة مع سجل التقدم الأكاديمي
     */
    public function progress(): HasOne
    {
        return $this->hasOne(AcademicProgress::class, 'subscription_id');
    }

    /**
     * العلاقة مع المدفوعات
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    /**
     * Get the academic sessions for this subscription
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(\App\Models\AcademicSession::class, 'academic_subscription_id');
    }

    /**
     * نطاق الاشتراكات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * نطاق الاشتراكات المعلقة
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * نطاق الاشتراكات المتأخرة في الدفع
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue');
    }

    /**
     * نطاق الاشتراكات المستحقة للتجديد
     */
    public function scopeDueForRenewal($query, $days = 7)
    {
        return $query->where('next_billing_date', '<=', Carbon::now()->addDays($days))
            ->where('status', 'active')
            ->where('auto_renewal', true);
    }

    /**
     * نطاق الاشتراكات حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * توليد رمز الاشتراك
     */
    private function generateSubscriptionCode(): string
    {
        $academyId = $this->academy_id;
        $count = static::where('academy_id', $academyId)->count() + 1;

        return 'SUB-'.$academyId.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * حساب تاريخ الفوترة التالي
     */
    private function calculateNextBillingDate(Carbon $startDate): Carbon
    {
        return match ($this->billing_cycle) {
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * الحصول على حالة الاشتراك بالعربية
     */
    public function getStatusInArabicAttribute(): string
    {
        return match ($this->status) {
            'active' => 'نشط',
            'paused' => 'معلق',
            'suspended' => 'موقوف',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي',
            'completed' => 'مكتمل',
            default => $this->status,
        };
    }

    /**
     * الحصول على حالة الدفع بالعربية
     */
    public function getPaymentStatusInArabicAttribute(): string
    {
        return match ($this->payment_status) {
            'current' => 'محدث',
            'pending' => 'في الانتظار',
            'overdue' => 'متأخر',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    /**
     * تحديد ما إذا كان الاشتراك نشط
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->payment_status === 'current';
    }

    /**
     * تحديد ما إذا كان الاشتراك متأخر في الدفع
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->next_billing_date->isPast() && $this->payment_status !== 'current';
    }

    /**
     * الحصول على عدد الأيام حتى الفوترة التالية
     */
    public function getDaysUntilNextBillingAttribute(): int
    {
        return max(0, Carbon::now()->diffInDays($this->next_billing_date, false));
    }

    /**
     * إيقاف الاشتراك مؤقتاً
     */
    public function pause(?string $reason = null, ?Carbon $resumeDate = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'paused',
            'paused_at' => Carbon::now(),
            'resume_date' => $resumeDate,
            'pause_reason' => $reason,
        ]);

        return true;
    }

    /**
     * استئناف الاشتراك
     */
    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        // Calculate new billing date based on pause duration
        $pauseDuration = Carbon::now()->diffInDays($this->paused_at);
        $newBillingDate = $this->next_billing_date->addDays($pauseDuration);

        $this->update([
            'status' => 'active',
            'next_billing_date' => $newBillingDate,
            'paused_at' => null,
            'resume_date' => null,
            'pause_reason' => null,
        ]);

        return true;
    }

    /**
     * إلغاء الاشتراك
     */
    public function cancel(?string $reason = null): bool
    {
        if (in_array($this->status, ['cancelled', 'expired'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'end_date' => Carbon::now(),
            'notes' => $reason ? "ملغي: {$reason}" : 'ملغي',
        ]);

        return true;
    }

    /**
     * تجديد الاشتراك وتسجيل الدفع
     */
    public function renew(float $amount): bool
    {
        if (! $this->is_active && $this->status !== 'paused') {
            return false;
        }

        $nextBillingDate = $this->calculateNextBillingDate(Carbon::now());

        $this->update([
            'status' => 'active',
            'payment_status' => 'current',
            'last_payment_date' => Carbon::now(),
            'last_payment_amount' => $amount,
            'next_billing_date' => $nextBillingDate,
            'last_reminder_sent' => null,
        ]);

        return true;
    }

    /**
     * تحديث معدل الإنجاز
     */
    public function updateCompletionRate(): void
    {
        if ($this->total_sessions_scheduled > 0) {
            $this->completion_rate = ($this->total_sessions_completed / $this->total_sessions_scheduled) * 100;
            $this->save();
        }
    }

    /**
     * تسجيل حضور جلسة
     */
    public function recordSessionAttendance(bool $attended): void
    {
        if ($attended) {
            $this->increment('total_sessions_completed');
        } else {
            $this->increment('total_sessions_missed');
        }

        $this->updateCompletionRate();
    }

    /**
     * إضافة جلسة مجدولة
     */
    public function scheduleSession(): void
    {
        $this->increment('total_sessions_scheduled');
        $this->updateCompletionRate();
    }

    /**
     * تحديث الحصة التجريبية
     */
    public function updateTrialSession(Carbon $date, string $status): bool
    {
        if (! $this->has_trial_session) {
            return false;
        }

        $this->update([
            'trial_session_date' => $date,
            'trial_session_status' => $status,
            'trial_session_used' => in_array($status, ['completed', 'missed']),
        ]);

        return true;
    }

    /**
     * الحصول على تفاصيل الاشتراك للعرض
     */
    public function getSubscriptionDetailsAttribute(): array
    {
        return [
            'subscription_code' => $this->subscription_code,
            'student_name' => $this->student->name,
            'teacher_name' => $this->teacher->user->name,
            'subject_name' => $this->subject->name,
            'grade_level' => $this->gradeLevel->name,
            'sessions_per_week' => $this->sessions_per_week,
            'session_duration' => $this->session_duration_minutes,
            'monthly_amount' => $this->final_monthly_amount,
            'currency' => $this->currency,
            'status' => $this->status_in_arabic,
            'payment_status' => $this->payment_status_in_arabic,
            'next_billing_date' => $this->next_billing_date,
            'days_until_billing' => $this->days_until_next_billing,
            'completion_rate' => $this->completion_rate,
            'total_sessions' => $this->total_sessions_scheduled,
            'completed_sessions' => $this->total_sessions_completed,
            'missed_sessions' => $this->total_sessions_missed,
        ];
    }
}
