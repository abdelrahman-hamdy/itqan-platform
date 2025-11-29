<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'options',
        'correct_option',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_option' => 'integer',
        'order' => 'integer',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Check if the given answer is correct
     */
    public function isCorrect(int $answerIndex): bool
    {
        return $this->correct_option === $answerIndex;
    }

    /**
     * Get the correct answer text
     */
    public function getCorrectAnswerText(): ?string
    {
        return $this->options[$this->correct_option] ?? null;
    }

    /**
     * Get options count
     */
    public function getOptionsCountAttribute(): int
    {
        return count($this->options ?? []);
    }
}
