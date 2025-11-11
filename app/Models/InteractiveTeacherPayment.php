<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class InteractiveTeacherPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'course_id',
        'teacher_id',
        'total_amount',
        'payment_type',
        'students_enrolled',
        'amount_per_student',
        'bonus_amount',
        'deductions',
        'payment_status',
        'payment_date',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_per_student' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'students_enrolled' => 'integer',
        'payment_date' => 'datetime',
    ];

    protected $attributes = [
        'payment_status' => 'pending',
        'students_enrolled' => 0,
        'bonus_amount' => 0,
        'deductions' => 0,
    ];

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * العلاقة مع الدورة التفاعلية
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    /**
     * العلاقة مع المعلم
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * العلاقة مع المستخدم الذي قام بالدفع
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * نطاق المدفوعات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * نطاق المدفوعات الجزئية
     */
    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    /**
     * نطاق المدفوعات المكتملة
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * نطاق المدفوعات لهذا الشهر
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * نطاق المدفوعات لهذا العام
     */
    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    /**
     * نطاق المدفوعات حسب المعلم
     */
    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * نطاق المدفوعات حسب الأكاديمية
     */
    public function scopeByAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * الحصول على حالة الدفع بالعربية
     */
    public function getPaymentStatusInArabicAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'معلق',
            'partial' => 'جزئي',
            'paid' => 'مدفوع',
            default => 'غير معروفة'
        };
    }

    /**
     * الحصول على نوع الدفع بالعربية
     */
    public function getPaymentTypeInArabicAttribute(): string
    {
        return match($this->payment_type) {
            'fixed' => 'مبلغ ثابت',
            'per_student' => 'لكل طالب',
            'per_session' => 'لكل جلسة',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على المبلغ الصافي (بعد الخصومات والمكافآت)
     */
    public function getNetAmountAttribute(): float
    {
        return $this->total_amount + $this->bonus_amount - $this->deductions;
    }

    /**
     * الحصول على المبلغ المتبقي للدفع
     */
    public function getRemainingAmountAttribute(): float
    {
        if ($this->payment_status === 'paid') {
            return 0;
        }

        return $this->net_amount;
    }

    /**
     * التحقق من أن الدفع قابل للمعالجة
     */
    public function canProcessPayment(): bool
    {
        return $this->payment_status === 'pending' && $this->net_amount > 0;
    }

    /**
     * التحقق من أن الدفع قابل للتعديل
     */
    public function canEdit(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * معالجة الدفع
     */
    public function processPayment(int $paidByUserId, ?string $notes = null): bool
    {
        if (!$this->canProcessPayment()) {
            return false;
        }

        $this->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
            'paid_by' => $paidByUserId,
            'notes' => $notes ? $this->notes . "\n" . $notes : $this->notes,
        ]);

        return true;
    }

    /**
     * تحديث عدد الطلاب المسجلين
     */
    public function updateEnrollmentCount(): void
    {
        $enrolledCount = $this->course->enrollments()
                                    ->where('enrollment_status', 'enrolled')
                                    ->count();

        $this->update(['students_enrolled' => $enrolledCount]);

        // إعادة حساب المبلغ إذا كان الدفع لكل طالب
        if ($this->payment_type === 'per_student' && $this->amount_per_student) {
            $this->update(['total_amount' => $enrolledCount * $this->amount_per_student]);
        }
    }

    /**
     * إضافة مكافأة
     */
    public function addBonus(float $amount, string $reason = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->update([
            'bonus_amount' => $this->bonus_amount + $amount,
            'notes' => $this->notes . "\n" . now()->format('Y-m-d H:i:s') . " - مكافأة: {$amount} ريال - السبب: {$reason}",
        ]);

        return true;
    }

    /**
     * إضافة خصم
     */
    public function addDeduction(float $amount, string $reason = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->update([
            'deductions' => $this->deductions + $amount,
            'notes' => $this->notes . "\n" . now()->format('Y-m-d H:i:s') . " - خصم: {$amount} ريال - السبب: {$reason}",
        ]);

        return true;
    }

    /**
     * الحصول على تفاصيل الدفع
     */
    public function getPaymentDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'academy_name' => $this->academy->name ?? 'غير محدد',
            'course_id' => $this->course_id,
            'course_title' => $this->course->title ?? 'غير محدد',
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->teacher->full_name ?? 'غير محدد',
            'total_amount' => $this->total_amount,
            'payment_type' => $this->payment_type,
            'payment_type_in_arabic' => $this->payment_type_in_arabic,
            'students_enrolled' => $this->students_enrolled,
            'amount_per_student' => $this->amount_per_student,
            'bonus_amount' => $this->bonus_amount,
            'deductions' => $this->deductions,
            'net_amount' => $this->net_amount,
            'payment_status' => $this->payment_status,
            'payment_status_in_arabic' => $this->payment_status_in_arabic,
            'payment_date' => $this->payment_date?->format('Y-m-d H:i:s'),
            'paid_by' => $this->paid_by,
            'paid_by_name' => $this->paidBy->name ?? 'غير محدد',
            'remaining_amount' => $this->remaining_amount,
            'can_process_payment' => $this->canProcessPayment(),
            'can_edit' => $this->canEdit(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * الحصول على إحصائيات الدفع للمعلم
     */
    public static function getTeacherPaymentStats(int $teacherId, int $academyId): array
    {
        $payments = static::where('teacher_id', $teacherId)
                         ->where('academy_id', $academyId);

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'total_bonus' => $payments->sum('bonus_amount'),
            'total_deductions' => $payments->sum('deductions'),
            'net_amount' => $payments->sum('total_amount') + $payments->sum('bonus_amount') - $payments->sum('deductions'),
            'pending_payments' => $payments->where('payment_status', 'pending')->count(),
            'paid_payments' => $payments->where('payment_status', 'paid')->count(),
            'this_month_amount' => $payments->thisMonth()->sum('total_amount'),
            'this_year_amount' => $payments->thisYear()->sum('total_amount'),
        ];
    }
}
