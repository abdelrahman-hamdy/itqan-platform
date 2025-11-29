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
        return !is_null($this->submitted_at);
    }

    /**
     * Check if time has expired (for timed quizzes)
     */
    public function hasTimeExpired(): bool
    {
        $quiz = $this->assignment->quiz;

        if (!$quiz->duration_minutes) {
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

        if (!$quiz->duration_minutes) {
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
    }
}
