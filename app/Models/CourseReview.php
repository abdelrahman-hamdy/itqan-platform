<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseReview extends Model
{
    use HasFactory, SoftDeletes, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'reviewable_type',
        'reviewable_id',
        'user_id',
        'rating',
        'review',
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
        static::creating(function (CourseReview $review) {
            if (!isset($review->is_approved)) {
                $review->is_approved = static::shouldAutoApprove($review->academy_id);
            }

            if ($review->is_approved) {
                $review->approved_at = now();
            }
        });

        // Update course stats when review changes
        static::created(function (CourseReview $review) {
            if ($review->is_approved && $review->reviewable) {
                $review->reviewable->updateReviewStats();
            }
            // Notify course instructor about new review
            $review->notifyCourseReviewReceived();
        });

        static::updated(function (CourseReview $review) {
            if ($review->reviewable) {
                $review->reviewable->updateReviewStats();
            }
            // Notify student if review was just approved
            if ($review->isDirty('is_approved') && $review->is_approved) {
                $review->notifyStudentReviewApproved();
            }
        });

        static::deleted(function (CourseReview $review) {
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
        if (!$academyId) {
            return true;
        }

        $academy = Academy::find($academyId);
        if (!$academy) {
            return true;
        }

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

    // Backward compatibility - alias for old code
    public function course(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class, 'reviewable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeForCourseType($query, string $type)
    {
        $typeClass = $type === 'recorded'
            ? RecordedCourse::class
            : InteractiveCourse::class;

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
    public function getCourseTypeAttribute(): string
    {
        return $this->reviewable_type === RecordedCourse::class
            ? 'recorded'
            : 'interactive';
    }

    public function getCourseTypeArabicAttribute(): string
    {
        return $this->course_type === 'recorded'
            ? 'دورة مسجلة'
            : 'دورة تفاعلية';
    }

    public function getCourseNameAttribute(): string
    {
        return $this->reviewable?->title ?? 'دورة';
    }

    public function getStudentNameAttribute(): string
    {
        return $this->user?->name ?? 'طالب';
    }

    public function getStatusArabicAttribute(): string
    {
        return $this->is_approved ? 'معتمد' : 'قيد المراجعة';
    }

    /**
     * Notify course instructor when they receive a new review
     */
    public function notifyCourseReviewReceived(): void
    {
        try {
            if (!$this->reviewable) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get course instructor/owner
            $instructor = null;
            if ($this->reviewable instanceof \App\Models\InteractiveCourse && $this->reviewable->assignedTeacher) {
                $instructor = $this->reviewable->assignedTeacher->user;
            } elseif ($this->reviewable instanceof \App\Models\RecordedCourse && $this->reviewable->instructor) {
                $instructor = $this->reviewable->instructor;
            }

            if (!$instructor) {
                return;
            }

            // Build course URL
            $courseUrl = $this->course_type === 'interactive'
                ? route('teacher.interactive-courses.show', [
                    'subdomain' => $instructor->academy->subdomain ?? 'itqan-academy',
                    'course' => $this->reviewable->id,
                ])
                : route('public.recorded-courses.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'course' => $this->reviewable->id,
                ]);

            $notificationService->send(
                $instructor,
                \App\Enums\NotificationType::REVIEW_RECEIVED,
                [
                    'student_name' => $this->student_name,
                    'rating' => $this->rating,
                    'comment' => $this->review,
                    'course_name' => $this->course_name,
                    'course_type' => $this->course_type_arabic,
                ],
                $courseUrl,
                [
                    'review_id' => $this->id,
                    'course_type' => $this->course_type,
                ],
                false
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send course review received notification', [
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
            if (!$this->user || !$this->reviewable) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Build course URL
            $courseUrl = $this->course_type === 'interactive'
                ? route('my.interactive-course.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'course' => $this->reviewable->id,
                ])
                : route('public.recorded-courses.show', [
                    'subdomain' => $this->academy->subdomain ?? 'itqan-academy',
                    'course' => $this->reviewable->id,
                ]);

            $notificationService->send(
                $this->user,
                \App\Enums\NotificationType::REVIEW_APPROVED,
                [
                    'course_name' => $this->course_name,
                    'rating' => $this->rating,
                    'course_type' => $this->course_type_arabic,
                ],
                $courseUrl,
                [
                    'review_id' => $this->id,
                    'course_type' => $this->course_type,
                ],
                false
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send course review approved notification', [
                'review_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
