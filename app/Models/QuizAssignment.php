<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuizAssignment extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the route key for the model (for route model binding with UUIDs)
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected $fillable = [
        'quiz_id',
        'assignable_type',
        'assignable_id',
        'is_visible',
        'available_from',
        'available_until',
        'max_attempts',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'max_attempts' => 'integer',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the parent assignable (circle, class, course)
     * Can be: QuranCircle, QuranIndividualCircle, AcademicIndividualLesson, InteractiveCourse, RecordedCourse
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the academy through the quiz relationship
     */
    public function academy(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Academy::class,
            Quiz::class,
            'id', // Foreign key on quizzes table
            'id', // Foreign key on academies table
            'quiz_id', // Local key on quiz_assignments table
            'academy_id' // Local key on quizzes table
        );
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('available_until')
              ->orWhere('available_until', '>=', now());
        });
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Check if the quiz is currently available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_visible) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if student can take the quiz
     */
    public function canStudentAttempt(int $studentId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $attemptCount = $this->attempts()
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->count();

        return $attemptCount < $this->max_attempts;
    }

    /**
     * Get student's remaining attempts
     */
    public function getRemainingAttempts(int $studentId): int
    {
        $attemptCount = $this->attempts()
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->count();

        return max(0, $this->max_attempts - $attemptCount);
    }

    /**
     * Get student's best score for this assignment
     */
    public function getStudentBestScore(int $studentId): ?int
    {
        return $this->attempts()
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->max('score');
    }

    /**
     * Get the URL to return to after viewing quiz results
     * Based on the assignable type (circle, course, lesson, etc.)
     */
    public function getReturnUrl(?string $subdomain = null): string
    {
        $subdomain = $subdomain ?? request()->route('subdomain') ?? 'itqan-academy';
        $assignable = $this->assignable;

        if (!$assignable) {
            return route('student.profile', ['subdomain' => $subdomain]);
        }

        return match ($this->assignable_type) {
            QuranCircle::class => route('student.circles.show', [
                'subdomain' => $subdomain,
                'circleId' => $assignable->id,
            ]),
            QuranIndividualCircle::class => route('individual-circles.show', [
                'subdomain' => $subdomain,
                'circle' => $assignable->id,
            ]),
            AcademicIndividualLesson::class => route('student.academic-subscriptions.show', [
                'subdomain' => $subdomain,
                'subscriptionId' => $assignable->subscription_id ?? $assignable->id,
            ]),
            InteractiveCourse::class => route('my.interactive-course.show', [
                'subdomain' => $subdomain,
                'course' => $assignable->id,
            ]),
            RecordedCourse::class => route('public.recorded-courses.show', [
                'subdomain' => $subdomain,
                'course' => $assignable->id,
            ]),
            default => route('student.profile', ['subdomain' => $subdomain]),
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Notify students when quiz is assigned
        static::created(function ($assignment) {
            $assignment->notifyQuizAssigned();
        });
    }

    /**
     * Send notification to students when quiz is assigned
     */
    public function notifyQuizAssigned(): void
    {
        try {
            if (!$this->assignable || !$this->quiz) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $students = $this->getAffectedStudents();

            foreach ($students as $student) {
                try {
                    $notificationService->send(
                        $student,
                        \App\Enums\NotificationType::QUIZ_ASSIGNED,
                        [
                            'quiz_title' => $this->quiz->title,
                            'quiz_description' => $this->quiz->description,
                            'duration_minutes' => $this->quiz->duration_minutes,
                            'passing_score' => $this->quiz->passing_score,
                            'max_attempts' => $this->max_attempts,
                            'available_from' => $this->available_from?->format('Y-m-d H:i'),
                            'available_until' => $this->available_until?->format('Y-m-d H:i'),
                        ],
                        $this->getReturnUrl(),
                        [
                            'quiz_assignment_id' => $this->id,
                            'quiz_id' => $this->quiz_id,
                            'assignable_type' => $this->assignable_type,
                            'assignable_id' => $this->assignable_id,
                        ],
                        true  // Mark as important
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send quiz assigned notification to student', [
                        'assignment_id' => $this->id,
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send quiz assigned notifications', [
                'assignment_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all students affected by this quiz assignment
     */
    protected function getAffectedStudents(): \Illuminate\Support\Collection
    {
        $students = collect();

        if (!$this->assignable) {
            return $students;
        }

        if ($this->assignable instanceof \App\Models\QuranCircle) {
            // Group circle - get all students with active subscriptions
            $students = $this->assignable->activeSubscriptions()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter();
        } elseif ($this->assignable instanceof \App\Models\QuranIndividualCircle) {
            // Individual circle - get the student
            if ($this->assignable->student) {
                $students->push($this->assignable->student);
            }
        } elseif ($this->assignable instanceof \App\Models\InteractiveCourse) {
            // Interactive course - get enrolled students
            $students = $this->assignable->enrollments()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter();
        } elseif ($this->assignable instanceof \App\Models\RecordedCourse) {
            // Recorded course - get subscribed students
            $students = $this->assignable->subscriptions()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter();
        } elseif ($this->assignable instanceof \App\Models\AcademicIndividualLesson) {
            // Individual lesson - get the student
            if ($this->assignable->student) {
                $students->push($this->assignable->student);
            }
        }

        return $students->unique('id');
    }
}
