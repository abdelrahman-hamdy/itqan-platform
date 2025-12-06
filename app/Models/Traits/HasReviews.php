<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for models that can be reviewed (teachers and courses)
 */
trait HasReviews
{
    /**
     * Get all reviews for this model
     */
    abstract public function reviews(): MorphMany;

    /**
     * Get only approved reviews
     */
    public function approvedReviews(): MorphMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get pending reviews
     */
    public function pendingReviews(): MorphMany
    {
        return $this->reviews()->where('is_approved', false);
    }

    /**
     * Check if a student has already reviewed this model
     */
    public function hasReviewFrom(int $studentId): bool
    {
        return $this->reviews()->where('student_id', $studentId)->exists();
    }

    /**
     * Get the review from a specific student
     */
    public function getReviewFrom(int $studentId)
    {
        return $this->reviews()->where('student_id', $studentId)->first();
    }

    /**
     * Update the rating and total_reviews stats
     */
    public function updateReviewStats(): void
    {
        $stats = $this->approvedReviews()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $this->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
            'total_reviews' => $stats->total_reviews ?? 0,
        ]);
    }

    /**
     * Get average rating as a formatted string
     */
    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating ?? 0, 1);
    }

    /**
     * Get rating percentage for display (out of 5)
     */
    public function getRatingPercentageAttribute(): float
    {
        return (($this->rating ?? 0) / 5) * 100;
    }
}
