<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
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
        'quiz_assignment_id',
        'student_id',
        'answers',
        'score',
        'passed',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(QuizAssignment::class, 'quiz_assignment_id');
    }

    /**
     * Alias for assignment() relationship
     */
    public function quizAssignment(): BelongsTo
    {
        return $this->assignment();
    }

    /**
     * Get the quiz through the assignment (accessor)
     */
    public function getQuizAttribute(): ?Quiz
    {
        return $this->assignment?->quiz;
    }

    /**
     * Eager load quiz through assignment
     * Note: This is a "fake" relationship for eager loading support
     */
    public function quiz(): BelongsTo
    {
        // Return a relationship that will work with eager loading
        // The actual quiz is accessed via assignment.quiz
        return $this->belongsTo(Quiz::class, 'id', 'id')
            ->whereRaw('0 = 1'); // Never matches - use accessor instead
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('submitted_at');
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('submitted_at')->where('passed', false);
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Check if the attempt is in progress
     */
    public function isInProgress(): bool
    {
        return is_null($this->submitted_at);
    }

    /**
     * Check if the attempt is completed
     */
    public function isCompleted(): bool
    {
        return ! is_null($this->submitted_at);
    }

    /**
     * Check if time has expired (for timed quizzes)
     */
    public function hasTimeExpired(): bool
    {
        $quiz = $this->assignment->quiz;

        if (! $quiz->duration_minutes) {
            return false;
        }

        $expiresAt = $this->started_at->addMinutes($quiz->duration_minutes);

        return now()->gt($expiresAt);
    }

    /**
     * Get remaining time in seconds (for timed quizzes)
     */
    public function getRemainingTimeInSeconds(): ?int
    {
        $quiz = $this->assignment->quiz;

        if (! $quiz->duration_minutes) {
            return null;
        }

        $expiresAt = $this->started_at->addMinutes($quiz->duration_minutes);
        $remaining = now()->diffInSeconds($expiresAt, false);

        return max(0, $remaining);
    }

    /**
     * Submit the attempt and calculate score
     */
    public function submit(array $answers): void
    {
        $quiz = $this->assignment->quiz;
        $questions = $quiz->questions;
        $totalQuestions = $questions->count();

        if ($totalQuestions === 0) {
            $this->update([
                'answers' => $answers,
                'score' => 0,
                'passed' => false,
                'submitted_at' => now(),
            ]);

            return;
        }

        $correctCount = 0;
        foreach ($questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;
            if ($studentAnswer !== null && $question->isCorrect((int) $studentAnswer)) {
                $correctCount++;
            }
        }

        $score = (int) round(($correctCount / $totalQuestions) * 100);
        $passed = $score >= $quiz->passing_score;

        $this->update([
            'answers' => $answers,
            'score' => $score,
            'passed' => $passed,
            'submitted_at' => now(),
        ]);

        // Send notification after submission
        $this->notifyQuizCompleted();
    }

    /**
     * Send notification when quiz is completed
     */
    public function notifyQuizCompleted(): void
    {
        try {
            if (! $this->student || ! $this->assignment || ! $this->assignment->quiz) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $quiz = $this->assignment->quiz;
            $student = $this->student;

            // Get student user from student profile
            if (! $student->user) {
                return;
            }

            $notificationType = $this->passed
                ? \App\Enums\NotificationType::QUIZ_PASSED
                : \App\Enums\NotificationType::QUIZ_FAILED;

            $notificationService->send(
                $student->user,
                $notificationType,
                [
                    'quiz_title' => $quiz->title,
                    'score' => $this->score,
                    'passing_score' => $quiz->passing_score,
                    'passed' => $this->passed,
                    'submitted_at' => $this->submitted_at->format('Y-m-d H:i'),
                ],
                $this->assignment->getReturnUrl(),
                [
                    'quiz_attempt_id' => $this->id,
                    'quiz_assignment_id' => $this->quiz_assignment_id,
                    'quiz_id' => $quiz->id,
                ],
                ! $this->passed  // Mark as important if failed
            );

            // Also notify parent if exists
            if ($student->parent && $student->parent->user) {
                $notificationService->send(
                    $student->parent->user,
                    $notificationType,
                    [
                        'quiz_title' => $quiz->title,
                        'student_name' => $student->user->full_name,
                        'score' => $this->score,
                        'passing_score' => $quiz->passing_score,
                        'passed' => $this->passed,
                        'submitted_at' => $this->submitted_at->format('Y-m-d H:i'),
                    ],
                    $this->assignment->getReturnUrl(),
                    [
                        'quiz_attempt_id' => $this->id,
                        'quiz_assignment_id' => $this->quiz_assignment_id,
                        'quiz_id' => $quiz->id,
                    ],
                    ! $this->passed
                );
            }

            // Notify teacher about quiz completion
            $teacher = $this->assignment?->teacher?->user ?? $quiz->teacher?->user;
            if ($teacher) {
                $notificationService->send(
                    $teacher,
                    \App\Enums\NotificationType::QUIZ_COMPLETED_TEACHER,
                    [
                        'quiz_title' => $quiz->title,
                        'student_name' => $student->user->full_name,
                        'score' => $this->score,
                        'passing_score' => $quiz->passing_score,
                        'passed' => $this->passed,
                    ],
                    $this->assignment->getReturnUrl(),
                    [
                        'quiz_attempt_id' => $this->id,
                        'quiz_assignment_id' => $this->quiz_assignment_id,
                        'quiz_id' => $quiz->id,
                        'student_id' => $student->id,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send quiz completion notification', [
                'attempt_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
