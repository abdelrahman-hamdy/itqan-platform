<?php

namespace App\Models;

use App\Constants\DefaultAcademy;
use App\Enums\ReviewStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherReview extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'reviewable_type',
        'reviewable_id',
        'student_id',
        'rating',
        'comment',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Auto-approve based on academy settings
        static::creating(function (TeacherReview $review) {
            if (! isset($review->is_approved)) {
                $review->is_approved = static::shouldAutoApprove($review->academy_id);
            }

            if ($review->is_approved) {
                $review->approved_at = now();
            }
        });

        // Update teacher stats when review changes
        static::created(function (TeacherReview $review) {
            if ($review->is_approved && $review->reviewable) {
                $review->reviewable->updateReviewStats();
            }
            // Notify teacher about new review
            $review->notifyTeacherReviewReceived();
        });

        static::updated(function (TeacherReview $review) {
            if ($review->reviewable) {
                $review->reviewable->updateReviewStats();
            }
            // Notify student if review was just approved
            if ($review->isDirty('is_approved') && $review->is_approved) {
                $review->notifyStudentReviewApproved();
            }
        });

        static::deleted(function (TeacherReview $review) {
            if ($review->reviewable) {
                $review->reviewable->updateReviewStats();
            }
        });
    }

    /**
     * Check if reviews should be auto-approved for this academy
     */
    protected static function shouldAutoApprove(?int $academyId): bool
    {
        if (! $academyId) {
            return true;
        }

        $academy = Academy::find($academyId);
        if (! $academy) {
            return true;
        }

        // Check academy settings for auto_approve_reviews
        $settings = $academy->academic_settings ?? [];

        return $settings['auto_approve_reviews'] ?? true;
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeForRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeForTeacherType($query, string $type)
    {
        $typeClass = $type === 'quran'
            ? QuranTeacherProfile::class
            : AcademicTeacherProfile::class;

        return $query->where('reviewable_type', $typeClass);
    }

    /**
     * Actions
     */
    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Helper attributes
     */
    public function getTeacherTypeAttribute(): string
    {
        return $this->reviewable_type === QuranTeacherProfile::class
            ? 'quran'
            : 'academic';
    }

    public function getTeacherTypeArabicAttribute(): string
    {
        return $this->teacher_type === 'quran'
            ? 'معلم قرآن'
            : 'معلم أكاديمي';
    }

    public function getTeacherNameAttribute(): string
    {
        return $this->reviewable?->full_name ?? 'معلم';
    }

    public function getStudentNameAttribute(): string
    {
        return $this->student?->name ?? 'طالب';
    }

    public function getStatusArabicAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get the review status as enum
     */
    public function getStatusAttribute(): ReviewStatus
    {
        if ($this->is_approved === true) {
            return ReviewStatus::APPROVED;
        }

        // Note: rejected status would need a separate column, for now null/false = pending
        return ReviewStatus::PENDING;
    }

    /**
     * Notify teacher when they receive a new review
     */
    public function notifyTeacherReviewReceived(): void
    {
        try {
            if (! $this->reviewable || ! $this->reviewable->user) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $teacher = $this->reviewable->user;

            // Build teacher profile URL
            $profileUrl = $this->teacher_type === 'quran'
                ? route('teacher.profile', ['subdomain' => $teacher->academy->subdomain ?? DefaultAcademy::subdomain()])
                : route('academic-teacher.profile', ['subdomain' => $teacher->academy->subdomain ?? DefaultAcademy::subdomain()]);

            $notificationService->send(
                $teacher,
                \App\Enums\NotificationType::REVIEW_RECEIVED,
                [
                    'student_name' => $this->student_name,
                    'rating' => $this->rating,
                    'comment' => $this->comment,
                    'teacher_type' => $this->teacher_type_arabic,
                ],
                $profileUrl,
                [
                    'review_id' => $this->id,
                    'teacher_type' => $this->teacher_type,
                ],
                false
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send review received notification', [
                'review_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify student when their review is approved
     */
    public function notifyStudentReviewApproved(): void
    {
        try {
            if (! $this->student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Build teacher profile URL
            $profileUrl = $this->teacher_type === 'quran'
                ? route('student.quran-teachers.show', [
                    'subdomain' => $this->academy->subdomain ?? DefaultAcademy::subdomain(),
                    'teacherId' => $this->reviewable->id ?? '',
                ])
                : route('student.academic-teachers.show', [
                    'subdomain' => $this->academy->subdomain ?? DefaultAcademy::subdomain(),
                    'teacherId' => $this->reviewable->id ?? '',
                ]);

            $notificationService->send(
                $this->student,
                \App\Enums\NotificationType::REVIEW_APPROVED,
                [
                    'teacher_name' => $this->teacher_name,
                    'rating' => $this->rating,
                    'teacher_type' => $this->teacher_type_arabic,
                ],
                $profileUrl,
                [
                    'review_id' => $this->id,
                    'teacher_type' => $this->teacher_type,
                ],
                false
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send review approved notification', [
                'review_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
