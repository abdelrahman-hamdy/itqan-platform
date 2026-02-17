<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;
use App\Models\User;

/**
 * Trait HasSessionFeedback
 *
 * Provides feedback and notes functionality for session models:
 * - Teacher feedback management
 * - Session notes
 * - Feedback retrieval
 */
trait HasSessionFeedback
{
    /**
     * Add teacher feedback to the session
     */
    public function addTeacherFeedback(string $feedback, ?User $teacher = null): bool
    {
        $data = ['teacher_feedback' => $feedback];

        if ($teacher) {
            $data['updated_by'] = $teacher->id;
        }

        return $this->update($data);
    }

    /**
     * Get teacher feedback
     */
    public function getTeacherFeedback(): ?string
    {
        return $this->teacher_feedback;
    }

    /**
     * Check if session has teacher feedback
     */
    public function hasTeacherFeedback(): bool
    {
        return ! empty($this->teacher_feedback);
    }

    /**
     * Add session notes
     */
    public function addNotes(string $notes, ?User $user = null): bool
    {
        $data = ['session_notes' => $notes];

        if ($user) {
            $data['updated_by'] = $user->id;
        }

        return $this->update($data);
    }

    /**
     * Get session notes
     */
    public function getNotes(): ?string
    {
        return $this->session_notes;
    }

    /**
     * Check if session has notes
     */
    public function hasNotes(): bool
    {
        return ! empty($this->session_notes);
    }

    /**
     * Append to existing feedback
     */
    public function appendTeacherFeedback(string $additionalFeedback, ?User $teacher = null): bool
    {
        $currentFeedback = $this->teacher_feedback ?? '';
        $separator = $currentFeedback ? "\n\n---\n\n" : '';
        $newFeedback = $currentFeedback.$separator.$additionalFeedback;

        return $this->addTeacherFeedback($newFeedback, $teacher);
    }

    /**
     * Append to existing notes
     */
    public function appendNotes(string $additionalNotes, ?User $user = null): bool
    {
        $currentNotes = $this->session_notes ?? '';
        $separator = $currentNotes ? "\n\n" : '';
        $newNotes = $currentNotes.$separator.$additionalNotes;

        return $this->addNotes($newNotes, $user);
    }

    /**
     * Clear teacher feedback
     */
    public function clearTeacherFeedback(): bool
    {
        return $this->update(['teacher_feedback' => null]);
    }

    /**
     * Clear all notes
     */
    public function clearNotes(): bool
    {
        return $this->update(['session_notes' => null]);
    }

    /**
     * Get teacher feedback summary (first 100 characters)
     */
    public function getTeacherFeedbackSummary(): ?string
    {
        if (! $this->teacher_feedback) {
            return null;
        }

        return Str::limit($this->teacher_feedback, 100);
    }

    /**
     * Get notes summary (first 100 characters)
     */
    public function getNotesSummary(): ?string
    {
        if (! $this->session_notes) {
            return null;
        }

        return Str::limit($this->session_notes, 100);
    }
}
