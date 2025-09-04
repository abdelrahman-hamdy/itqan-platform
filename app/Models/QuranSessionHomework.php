<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class QuranSessionHomework extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'created_by',
        'has_new_memorization',
        'has_review',
        'has_comprehensive_review',
        'new_memorization_pages',
        'new_memorization_surah',
        'new_memorization_from_verse',
        'new_memorization_to_verse',
        'review_pages',
        'review_surah',
        'review_from_verse',
        'review_to_verse',
        'comprehensive_review_surahs',
        'additional_instructions',
        'due_date',
        'difficulty_level',
        'is_active',
    ];

    protected $casts = [
        'has_new_memorization' => 'boolean',
        'has_review' => 'boolean',
        'has_comprehensive_review' => 'boolean',
        'new_memorization_pages' => 'decimal:2',
        'review_pages' => 'decimal:2',
        'comprehensive_review_surahs' => 'array',
        'due_date' => 'date',
        'is_active' => 'boolean',
        'new_memorization_from_verse' => 'integer',
        'new_memorization_to_verse' => 'integer',
        'review_from_verse' => 'integer',
        'review_to_verse' => 'integer',
    ];

    /**
     * Relationships
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(QuranHomeworkAssignment::class, 'session_homework_id');
    }

    /**
     * Accessor for total pages
     */
    public function getTotalPagesAttribute(): float
    {
        return $this->new_memorization_pages + $this->review_pages;
    }

    /**
     * Accessor for completion statistics
     */
    public function getCompletionStatsAttribute(): array
    {
        $assignments = $this->assignments()->get();

        return [
            'total_students' => $assignments->count(),
            'completed' => $assignments->where('completion_status', 'completed')->count(),
            'in_progress' => $assignments->where('completion_status', 'in_progress')->count(),
            'partially_completed' => $assignments->where('completion_status', 'partially_completed')->count(),
            'not_started' => $assignments->where('completion_status', 'not_started')->count(),
            'average_completion' => $assignments->avg('completion_percentage') ?? 0,
            'average_score' => $assignments->whereNotNull('overall_score')->avg('overall_score') ?? 0,
        ];
    }

    /**
     * Get formatted new memorization range
     */
    public function getNewMemorizationRangeAttribute(): ?string
    {
        if (! $this->new_memorization_surah) {
            return null;
        }

        $range = $this->new_memorization_surah;

        if ($this->new_memorization_from_verse && $this->new_memorization_to_verse) {
            $range .= " (الآيات {$this->new_memorization_from_verse} - {$this->new_memorization_to_verse})";
        } elseif ($this->new_memorization_from_verse) {
            $range .= " (من الآية {$this->new_memorization_from_verse})";
        }

        return $range;
    }

    /**
     * Get formatted review range
     */
    public function getReviewRangeAttribute(): ?string
    {
        if (! $this->review_surah) {
            return null;
        }

        $range = $this->review_surah;

        if ($this->review_from_verse && $this->review_to_verse) {
            $range .= " (الآيات {$this->review_from_verse} - {$this->review_to_verse})";
        } elseif ($this->review_from_verse) {
            $range .= " (من الآية {$this->review_from_verse})";
        }

        return $range;
    }

    /**
     * Get difficulty level in Arabic
     */
    public function getDifficultyLevelArabicAttribute(): string
    {
        return match ($this->difficulty_level) {
            'easy' => 'سهل',
            'medium' => 'متوسط',
            'hard' => 'صعب',
            default => 'متوسط'
        };
    }

    /**
     * Get comprehensive review surahs as formatted string
     */
    public function getComprehensiveReviewSurahsFormattedAttribute(): ?string
    {
        if (! $this->comprehensive_review_surahs || empty($this->comprehensive_review_surahs)) {
            return null;
        }

        return implode('، ', $this->comprehensive_review_surahs);
    }

    /**
     * Get total homework types count
     */
    public function getHomeworkTypesCountAttribute(): int
    {
        return collect([
            $this->has_new_memorization,
            $this->has_review,
            $this->has_comprehensive_review,
        ])->filter()->count();
    }

    /**
     * Check if homework has any content
     */
    public function getHasAnyHomeworkAttribute(): bool
    {
        return $this->has_new_memorization || $this->has_review || $this->has_comprehensive_review;
    }

    /**
     * Check if homework is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Scope for active homework
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for homework by session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Create assignments for all students in the session
     */
    public function createAssignmentsForAllStudents(): void
    {
        // Ensure this homework record exists in the database
        if (! $this->exists || ! $this->id) {
            Log::error('Cannot create assignments: homework not saved', [
                'homework_id' => $this->id,
                'session_id' => $this->session_id,
            ]);

            return;
        }

        $students = $this->getSessionStudents();

        if ($students->isEmpty()) {
            Log::warning('No students found for session', [
                'session_id' => $this->session_id,
                'session_type' => $this->session->session_type ?? 'unknown',
            ]);

            return;
        }

        foreach ($students as $student) {
            if (! $student || ! $student->id) {
                Log::warning('Skipping invalid student', ['student' => $student]);

                continue;
            }

            try {
                QuranHomeworkAssignment::firstOrCreate([
                    'session_homework_id' => $this->id,
                    'student_id' => $student->id,
                    'session_id' => $this->session_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create homework assignment', [
                    'homework_id' => $this->id,
                    'student_id' => $student->id,
                    'session_id' => $this->session_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get students for this homework session
     */
    private function getSessionStudents()
    {
        $session = $this->session;

        if ($session->session_type === 'group' && $session->circle) {
            return $session->circle->students;
        } elseif ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)]);
        }

        return collect();
    }
}
