<?php

namespace App\Models;

use App\Constants\DefaultAcademy;
use App\Enums\QuizAssignableType;
use App\Services\AcademyContextService;
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
        if (! $this->is_visible) {
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
        if (! $this->isAvailable()) {
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
     * Get the assignable type as enum
     */
    public function getAssignableTypeEnum(): ?QuizAssignableType
    {
        return QuizAssignableType::tryFrom($this->assignable_type);
    }

    /**
     * Get the URL to return to after viewing quiz results
     * Based on the assignable type (circle, course, lesson, etc.)
     */
    public function getReturnUrl(?string $subdomain = null): string
    {
        $subdomain = $subdomain ?? request()->route('subdomain') ?? DefaultAcademy::subdomain();
        $assignable = $this->assignable;

        if (! $assignable) {
            return route('student.profile', ['subdomain' => $subdomain]);
        }

        $type = $this->getAssignableTypeEnum();

        return match ($type) {
            QuizAssignableType::QURAN_CIRCLE => route('student.circles.show', [
                'subdomain' => $subdomain,
                'circleId' => $assignable->id,
            ]),
            QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE => route('individual-circles.show', [
                'subdomain' => $subdomain,
                'circle' => $assignable->id,
            ]),
            QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON => route('student.academic-subscriptions.show', [
                'subdomain' => $subdomain,
                'subscriptionId' => $assignable->subscription_id ?? $assignable->id,
            ]),
            QuizAssignableType::INTERACTIVE_COURSE => route('my.interactive-course.show', [
                'subdomain' => $subdomain,
                'course' => $assignable->id,
            ]),
            QuizAssignableType::RECORDED_COURSE => route('public.recorded-courses.show', [
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
            if (! $this->assignable || ! $this->quiz) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $students = $this->getAffectedStudents();

            // Convert times to academy timezone for display
            $timezone = AcademyContextService::getTimezone();
            $availableFromFormatted = $this->available_from?->copy()->setTimezone($timezone)->format('Y-m-d h:i A');
            $availableUntilFormatted = $this->available_until?->copy()->setTimezone($timezone)->format('Y-m-d h:i A');

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
                            'available_from' => $availableFromFormatted,
                            'available_until' => $availableUntilFormatted,
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

        if (! $this->assignable) {
            return $students;
        }

        $type = $this->getAssignableTypeEnum();

        $students = match ($type) {
            QuizAssignableType::QURAN_CIRCLE => $this->assignable->activeSubscriptions()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter(),

            QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE => $this->assignable->student
                ? collect([$this->assignable->student])
                : collect(),

            QuizAssignableType::INTERACTIVE_COURSE => $this->assignable->enrollments()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter(),

            QuizAssignableType::RECORDED_COURSE => $this->assignable->subscriptions()
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter(),

            QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON => $this->assignable->student
                ? collect([$this->assignable->student])
                : collect(),

            default => collect(),
        };

        return $students->unique('id');
    }

    /**
     * Get students who haven't completed (passed) the quiz yet
     */
    public function getStudentsWithoutCompletedAttempts(): \Illuminate\Support\Collection
    {
        $allStudents = $this->getAffectedStudents();

        // Get student IDs who have passed the quiz
        $passedStudentIds = $this->attempts()
            ->where('passed', true)
            ->whereNotNull('submitted_at')
            ->pluck('student_id')
            ->toArray();

        // Filter out students who have already passed
        return $allStudents->filter(function ($student) use ($passedStudentIds) {
            return ! in_array($student->id, $passedStudentIds);
        });
    }

    /**
     * Send deadline reminder notification to students and parents
     *
     * @param  string  $type  '24h' or '1h'
     */
    public function notifyDeadlineApproaching(string $type): int
    {
        $notifiedCount = 0;

        try {
            if (! $this->quiz || ! $this->available_until) {
                return 0;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $students = $this->getStudentsWithoutCompletedAttempts();

            $notificationType = $type === '1h'
                ? \App\Enums\NotificationType::QUIZ_DEADLINE_1H
                : \App\Enums\NotificationType::QUIZ_DEADLINE_24H;

            $isUrgent = $type === '1h';

            // Convert deadline to academy timezone for display
            $timezone = AcademyContextService::getTimezone();
            $deadlineFormatted = $this->available_until->copy()->setTimezone($timezone)->format('Y-m-d h:i A');

            foreach ($students as $student) {
                try {
                    // Notify student
                    $notificationService->send(
                        $student,
                        $notificationType,
                        [
                            'quiz_title' => $this->quiz->title,
                            'deadline' => $deadlineFormatted,
                        ],
                        $this->getReturnUrl(),
                        [
                            'quiz_assignment_id' => $this->id,
                            'quiz_id' => $this->quiz_id,
                            'reminder_type' => $type,
                        ],
                        $isUrgent
                    );
                    $notifiedCount++;

                    // Notify parent if linked
                    if ($student->user && $student->user->parent) {
                        $notificationService->send(
                            $student->user->parent,
                            $notificationType,
                            [
                                'quiz_title' => $this->quiz->title,
                                'student_name' => $student->user->full_name,
                                'deadline' => $deadlineFormatted,
                            ],
                            $this->getReturnUrl(),
                            [
                                'quiz_assignment_id' => $this->id,
                                'quiz_id' => $this->quiz_id,
                                'student_id' => $student->id,
                                'reminder_type' => $type,
                            ],
                            $isUrgent
                        );
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send quiz deadline reminder', [
                        'assignment_id' => $this->id,
                        'student_id' => $student->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send quiz deadline reminders', [
                'assignment_id' => $this->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return $notifiedCount;
    }

    /**
     * Check if deadline reminder should be sent for this assignment
     *
     * @param  string  $type  '24h' or '1h'
     */
    public function shouldSendDeadlineReminder(string $type): bool
    {
        // Must have a deadline set
        if (! $this->available_until) {
            return false;
        }

        // Must be visible
        if (! $this->is_visible) {
            return false;
        }

        $now = now();
        $deadline = $this->available_until;

        // Deadline must be in the future
        if ($deadline->lte($now)) {
            return false;
        }

        $hoursUntilDeadline = $now->diffInHours($deadline, false);

        if ($type === '24h') {
            // Send 24h reminder when deadline is between 23-25 hours away
            return $hoursUntilDeadline >= 23 && $hoursUntilDeadline <= 25;
        }

        if ($type === '1h') {
            // Send 1h reminder when deadline is between 0.5-1.5 hours away
            $minutesUntilDeadline = $now->diffInMinutes($deadline, false);

            return $minutesUntilDeadline >= 30 && $minutesUntilDeadline <= 90;
        }

        return false;
    }
}
