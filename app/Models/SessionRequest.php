<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'hourly_rate' => 'decimal:2',
        'total_monthly_cost' => 'decimal:2',
        'is_trial_request' => 'boolean',
        'proposed_schedule' => 'array',
        'current_proposal' => 'array',
        'last_activity_at' => 'datetime',
        'trial_session_completed' => 'boolean',
        'trial_session_date' => 'datetime',
        'teacher_responded_at' => 'datetime',
        'agreed_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_trial_request' => false,
        'trial_session_completed' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_code)) {
                $model->request_code = $model->generateRequestCode();
            }
            
            if (empty($model->expires_at)) {
                $model->expires_at = Carbon::now()->addDays(7); // Expire after 7 days
            }
            
            $model->last_activity_at = Carbon::now();
            
            // Calculate total monthly cost
            if ($model->hourly_rate && $model->sessions_per_week) {
                $sessionsPerMonth = ($model->sessions_per_week * 4.33); // Average weeks per month
                $model->total_monthly_cost = $model->hourly_rate * $sessionsPerMonth;
            }
        });

        static::updating(function ($model) {
            $model->last_activity_at = Carbon::now();
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
        return $this->belongsTo(AcademicTeacher::class, 'teacher_id');
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
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * العلاقة مع الاشتراك المُنشأ
     */
    public function createdSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class, 'created_subscription_id');
    }

    /**
     * نطاق الطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * نطاق الطلبات المقترحة من المعلم
     */
    public function scopeTeacherProposed($query)
    {
        return $query->where('status', 'teacher_proposed');
    }

    /**
     * نطاق الطلبات المتفق عليها
     */
    public function scopeAgreed($query)
    {
        return $query->where('status', 'agreed');
    }

    /**
     * نطاق الطلبات التجريبية
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial_request', true);
    }

    /**
     * نطاق الطلبات المنتهية الصلاحية
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', Carbon::now())
                    ->whereNotIn('status', ['paid', 'agreed', 'rejected', 'cancelled']);
    }

    /**
     * نطاق الطلبات حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * نطاق الطلبات النشطة
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'expired', 'rejected']);
    }

    /**
     * توليد رمز الطلب
     */
    private function generateRequestCode(): string
    {
        $academyId = $this->academy_id;
        $count = static::where('academy_id', $academyId)->count() + 1;
        return 'REQ-' . $academyId . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على حالة الطلب بالعربية
     */
    public function getStatusInArabicAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'teacher_proposed' => 'اقترح المعلم مواعيد',
            'student_negotiating' => 'الطالب يتفاوض',
            'teacher_revising' => 'المعلم يراجع',
            'agreed' => 'تم الاتفاق',
            'paid' => 'تم الدفع',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي الصلاحية',
            default => $this->status,
        };
    }

    /**
     * تحديد ما إذا كان الطلب منتهي الصلاحية
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && 
               !in_array($this->status, ['paid', 'agreed', 'rejected', 'cancelled']);
    }

    /**
     * تحديد ما إذا كان الطلب يحتاج رد من المعلم
     */
    public function getAwaitingTeacherResponseAttribute(): bool
    {
        return in_array($this->status, ['pending', 'student_negotiating']);
    }

    /**
     * تحديد ما إذا كان الطلب يحتاج رد من الطالب
     */
    public function getAwaitingStudentResponseAttribute(): bool
    {
        return in_array($this->status, ['teacher_proposed', 'teacher_revising']);
    }

    /**
     * الحصول على عدد الأيام المتبقية قبل انتهاء الصلاحية
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->expires_at, false));
    }

    /**
     * اقتراح مواعيد من المعلم
     */
    public function proposeSchedule(array $schedule, ?string $message = null): bool
    {
        if (!$this->awaiting_teacher_response) {
            return false;
        }

        $this->update([
            'status' => 'teacher_proposed',
            'current_proposal' => $schedule,
            'teacher_response' => $message,
            'teacher_responded_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * رد الطالب على الاقتراح
     */
    public function studentResponse(bool $accepted, ?string $message = null, ?array $counterProposal = null): bool
    {
        if (!$this->awaiting_student_response) {
            return false;
        }

        if ($accepted) {
            $this->update([
                'status' => 'agreed',
                'agreed_at' => Carbon::now(),
                'latest_message' => $message,
            ]);
        } else {
            $this->update([
                'status' => 'student_negotiating',
                'latest_message' => $message,
                'current_proposal' => $counterProposal ?? $this->current_proposal,
            ]);
        }

        return true;
    }

    /**
     * رفض الطلب من المعلم
     */
    public function reject(?string $reason = null): bool
    {
        if (in_array($this->status, ['paid', 'rejected', 'cancelled'])) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'teacher_response' => $reason,
            'teacher_responded_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * إلغاء الطلب من الطالب
     */
    public function cancel(?string $reason = null): bool
    {
        if (in_array($this->status, ['paid', 'rejected', 'cancelled'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'latest_message' => $reason,
        ]);

        return true;
    }

    /**
     * تأكيد الدفع وإنشاء الاشتراك
     */
    public function confirmPayment(int $subscriptionId): bool
    {
        if ($this->status !== 'agreed') {
            return false;
        }

        $this->update([
            'status' => 'paid',
            'payment_completed_at' => Carbon::now(),
            'created_subscription_id' => $subscriptionId,
        ]);

        return true;
    }

    /**
     * تمديد انتهاء الصلاحية
     */
    public function extendExpiry(int $days = 7): bool
    {
        if (in_array($this->status, ['paid', 'rejected', 'cancelled'])) {
            return false;
        }

        $this->update([
            'expires_at' => Carbon::now()->addDays($days),
        ]);

        return true;
    }

    /**
     * تحديث إعدادات الحصة التجريبية
     */
    public function updateTrialSession(Carbon $date, bool $completed = false, ?string $feedback = null): bool
    {
        $this->update([
            'trial_session_date' => $date,
            'trial_session_completed' => $completed,
            'trial_session_feedback' => $feedback,
        ]);

        return true;
    }

    /**
     * الحصول على تفاصيل الطلب للعرض
     */
    public function getRequestDetailsAttribute(): array
    {
        return [
            'request_code' => $this->request_code,
            'student_name' => $this->student->name,
            'teacher_name' => $this->teacher->user->name,
            'subject_name' => $this->subject->name,
            'grade_level' => $this->gradeLevel->name,
            'sessions_per_week' => $this->sessions_per_week,
            'hourly_rate' => $this->hourly_rate,
            'total_monthly_cost' => $this->total_monthly_cost,
            'is_trial' => $this->is_trial_request,
            'status' => $this->status_in_arabic,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
            'days_until_expiry' => $this->days_until_expiry,
        ];
    }
}
