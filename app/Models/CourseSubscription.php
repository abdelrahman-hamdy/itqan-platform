<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'recorded_course_id',
        'subscription_code',
        'enrollment_type',
        'payment_type',
        'price_paid',
        'original_price',
        'discount_amount',
        'discount_code',
        'currency',
        'payment_status',
        'access_type',
        'access_duration_months',
        'lifetime_access',
        'certificate_eligible',
        'certificate_issued',
        'certificate_issued_at',
        'progress_percentage',
        'completed_lessons',
        'total_lessons',
        'watch_time_minutes',
        'total_duration_minutes',
        'last_accessed_at',
        'completion_date',
        'status',
        'enrolled_at',
        'expires_at',
        'trial_ends_at',
        'paused_at',
        'pause_reason',
        'cancelled_at',
        'cancellation_reason',
        'refund_requested_at',
        'refund_reason',
        'refund_processed_at',
        'refund_amount',
        'notes_count',
        'bookmarks_count',
        'quiz_attempts',
        'quiz_passed',
        'final_score',
        'rating',
        'review_text',
        'reviewed_at',
        'completion_certificate_url',
        'metadata',
        'created_by',
        'updated_by',
        'notes'
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'final_score' => 'decimal:2',
        'lifetime_access' => 'boolean',
        'certificate_eligible' => 'boolean',
        'certificate_issued' => 'boolean',
        'quiz_passed' => 'boolean',
        'access_duration_months' => 'integer',
        'completed_lessons' => 'integer',
        'total_lessons' => 'integer',
        'watch_time_minutes' => 'integer',
        'total_duration_minutes' => 'integer',
        'notes_count' => 'integer',
        'bookmarks_count' => 'integer',
        'quiz_attempts' => 'integer',
        'rating' => 'integer',
        'refund_amount' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'completion_date' => 'datetime',
        'last_accessed_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_processed_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'recorded_course_id', 'recorded_course_id')
                   ->where('user_id', $this->student_id);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function certificate(): MorphOne
    {
        return $this->morphOne(Certificate::class, 'certificateable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->where('lifetime_access', true)
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'active')
                    ->where('progress_percentage', '>', 0)
                    ->where('progress_percentage', '<', 100);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'active')
                    ->where('progress_percentage', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('lifetime_access', false)
                    ->where('expires_at', '<=', now());
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeFree($query)
    {
        return $query->where('enrollment_type', 'free');
    }

    public function scopeWithCertificate($query)
    {
        return $query->where('certificate_issued', true);
    }

    public function scopeEligibleForCertificate($query)
    {
        return $query->where('certificate_eligible', true)
                    ->where('certificate_issued', false)
                    ->where('progress_percentage', '>=', 90);
    }

    // Accessors
    public function getEnrollmentTypeTextAttribute(): string
    {
        $types = [
            'free' => 'مجاني',
            'paid' => 'مدفوع',
            'trial' => 'تجريبي',
            'gift' => 'هدية'
        ];

        return $types[$this->enrollment_type] ?? $this->enrollment_type;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'paused' => 'متوقف',
            'expired' => 'منتهي الصلاحية',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        $colors = [
            'active' => 'success',
            'completed' => 'primary',
            'paused' => 'warning',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            'refunded' => 'info'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getAccessStatusAttribute(): string
    {
        if ($this->lifetime_access) {
            return 'وصول مدى الحياة';
        }

        if ($this->expires_at && $this->expires_at->isFuture()) {
            $days = now()->diffInDays($this->expires_at);
            return "باقي {$days} يوم";
        }

        return 'منتهي الصلاحية';
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->enrollment_type === 'free') {
            return 'مجاني';
        }

        $formatted = number_format($this->price_paid, 2) . ' ' . $this->currency;

        if ($this->discount_amount > 0) {
            $originalFormatted = number_format($this->original_price, 2) . ' ' . $this->currency;
            return "{$formatted} (بدلاً من {$originalFormatted})";
        }

        return $formatted;
    }

    public function getProgressStatusAttribute(): string
    {
        if ($this->progress_percentage == 0) {
            return 'لم يبدأ';
        }

        if ($this->progress_percentage >= 100) {
            return 'مكتمل';
        }

        return 'قيد المشاهدة';
    }

    public function getWatchTimeFormattedAttribute(): string
    {
        return $this->formatDuration($this->watch_time_minutes * 60);
    }

    public function getTotalDurationFormattedAttribute(): string
    {
        return $this->formatDuration($this->total_duration_minutes * 60);
    }

    public function getCompletionRateAttribute(): float
    {
        if ($this->total_lessons == 0) {
            return 0;
        }

        return ($this->completed_lessons / $this->total_lessons) * 100;
    }

    public function getIsExpiredAttribute(): bool
    {
        return !$this->lifetime_access && 
               $this->expires_at && 
               $this->expires_at->isPast();
    }

    public function getCanAccessAttribute(): bool
    {
        return $this->status === 'active' && 
               ($this->lifetime_access || ($this->expires_at && $this->expires_at->isFuture()));
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->lifetime_access || !$this->expires_at) {
            return -1; // Unlimited
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function getCanEarnCertificateAttribute(): bool
    {
        return $this->certificate_eligible && 
               !$this->certificate_issued && 
               $this->progress_percentage >= 90;
    }

    // Methods
    public function updateProgress(): self
    {
        $courseProgress = $this->progress()->get();
        
        $completedLessons = $courseProgress->where('is_completed', true)->count();
        $totalWatchTime = $courseProgress->sum('watch_time_seconds');
        $totalDuration = $courseProgress->sum('total_time_seconds');
        
        $progressPercentage = 0;
        if ($this->recordedCourse) {
            $totalLessons = $this->recordedCourse->total_lessons;
            if ($totalLessons > 0) {
                $progressPercentage = ($completedLessons / $totalLessons) * 100;
            }
        }

        $this->update([
            'completed_lessons' => $completedLessons,
            'total_lessons' => $this->recordedCourse->total_lessons ?? 0,
            'progress_percentage' => $progressPercentage,
            'watch_time_minutes' => ceil($totalWatchTime / 60),
            'total_duration_minutes' => ceil($totalDuration / 60),
            'last_accessed_at' => now()
        ]);

        // Check for completion
        if ($progressPercentage >= 100 && $this->status === 'active') {
            $this->markAsCompleted();
        }

        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => now(),
            'progress_percentage' => 100
        ]);

        // Issue certificate if eligible
        if ($this->can_earn_certificate) {
            $this->issueCertificate();
        }

        return $this;
    }

    public function issueCertificate(): self
    {
        if (!$this->can_earn_certificate) {
            return $this;
        }

        try {
            // Use CertificateService to generate the certificate
            $certificateService = app(\App\Services\CertificateService::class);
            $certificate = $certificateService->issueCertificateForRecordedCourse($this);

            // The service already updates certificate_issued and certificate_issued_at
            // Just refresh the model to get the updated values
            $this->refresh();
        } catch (\Exception $e) {
            // Log the error but don't fail the completion
            \Log::error('Failed to issue certificate for CourseSubscription ' . $this->id . ': ' . $e->getMessage());
        }

        return $this;
    }

    public function pause(string $reason = null): self
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
            'pause_reason' => $reason
        ]);

        return $this;
    }

    public function resume(): self
    {
        $pausedDays = $this->paused_at ? now()->diffInDays($this->paused_at) : 0;

        $this->update([
            'status' => 'active',
            'paused_at' => null,
            'pause_reason' => null,
            'expires_at' => !$this->lifetime_access && $this->expires_at ? 
                $this->expires_at->addDays($pausedDays) : $this->expires_at
        ]);

        return $this;
    }

    public function cancel(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        return $this;
    }

    public function requestRefund(string $reason): self
    {
        $this->update([
            'refund_requested_at' => now(),
            'refund_reason' => $reason
        ]);

        return $this;
    }

    public function processRefund(float $amount): self
    {
        $this->update([
            'status' => 'refunded',
            'refund_processed_at' => now(),
            'refund_amount' => $amount
        ]);

        return $this;
    }

    public function extend(int $months): self
    {
        if ($this->lifetime_access) {
            return $this; // Already has lifetime access
        }

        $newExpiry = $this->expires_at && $this->expires_at->isFuture() 
            ? $this->expires_at->addMonths($months)
            : now()->addMonths($months);

        $this->update([
            'expires_at' => $newExpiry,
            'status' => 'active'
        ]);

        return $this;
    }

    public function grantLifetimeAccess(): self
    {
        $this->update([
            'lifetime_access' => true,
            'expires_at' => null,
            'status' => 'active'
        ]);

        return $this;
    }

    public function addRating(int $rating, string $reviewText = null): self
    {
        $this->update([
            'rating' => $rating,
            'review_text' => $reviewText,
            'reviewed_at' => now()
        ]);

        return $this;
    }

    public function getNextLesson(): ?Lesson
    {
        return $this->recordedCourse
            ->lessons()
            ->whereDoesntHave('progress', function($query) {
                $query->where('user_id', $this->student_id)
                      ->where('is_completed', true);
            })
            ->orderBy('order')
            ->first();
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' ثانية';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . ' دقيقة' . ($remainingSeconds > 0 ? ' و ' . $remainingSeconds . ' ثانية' : '');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . ' ساعة' . 
               ($remainingMinutes > 0 ? ' و ' . $remainingMinutes . ' دقيقة' : '') .
               ($remainingSeconds > 0 ? ' و ' . $remainingSeconds . ' ثانية' : '');
    }

    private function generateCertificateUrl(): string
    {
        // This would integrate with a certificate generation service
        $certificateId = 'CERT-' . $this->academy_id . '-' . $this->id . '-' . time();
        return config('app.url') . '/certificates/' . $certificateId . '.pdf';
    }

    // Static methods
    public static function createEnrollment(array $data): self
    {
        $enrollment = self::create($data);
        
        // Initialize course data
        if ($enrollment->recordedCourse) {
            $enrollment->update([
                'total_lessons' => $enrollment->recordedCourse->total_lessons,
                'total_duration_minutes' => $enrollment->recordedCourse->total_duration_minutes
            ]);
        }

        return $enrollment;
    }
} 