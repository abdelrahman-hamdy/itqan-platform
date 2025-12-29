<?php

namespace App\Models\Traits;

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
     *
     * @param string $feedback
     * @param User|null $teacher
     * @return bool
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
     *
     * @return string|null
     */
    public function getTeacherFeedback(): ?string
    {
        return $this->teacher_feedback;
    }

    /**
     * Check if session has teacher feedback
     *
     * @return bool
     */
    public function hasTeacherFeedback(): bool
    {
        return !empty($this->teacher_feedback);
    }

    /**
     * Add session notes
     *
     * @param string $notes
     * @param User|null $user
     * @return bool
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
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->session_notes;
    }

    /**
     * Check if session has notes
     *
     * @return bool
     */
    public function hasNotes(): bool
    {
        return !empty($this->session_notes);
    }

    /**
     * Append to existing feedback
     *
     * @param string $additionalFeedback
     * @param User|null $teacher
     * @return bool
     */
    public function appendTeacherFeedback(string $additionalFeedback, ?User $teacher = null): bool
    {
        $currentFeedback = $this->teacher_feedback ?? '';
        $separator = $currentFeedback ? "\n\n---\n\n" : '';
        $newFeedback = $currentFeedback . $separator . $additionalFeedback;

        return $this->addTeacherFeedback($newFeedback, $teacher);
    }

    /**
     * Append to existing notes
     *
     * @param string $additionalNotes
     * @param User|null $user
     * @return bool
     */
    public function appendNotes(string $additionalNotes, ?User $user = null): bool
    {
        $currentNotes = $this->session_notes ?? '';
        $separator = $currentNotes ? "\n\n" : '';
        $newNotes = $currentNotes . $separator . $additionalNotes;

        return $this->addNotes($newNotes, $user);
    }

    /**
     * Clear teacher feedback
     *
     * @return bool
     */
    public function clearTeacherFeedback(): bool
    {
        return $this->update(['teacher_feedback' => null]);
    }

    /**
     * Clear all notes
     *
     * @return bool
     */
    public function clearNotes(): bool
    {
        return $this->update(['session_notes' => null]);
    }

    /**
     * Get teacher feedback summary (first 100 characters)
     *
     * @return string|null
     */
    public function getTeacherFeedbackSummary(): ?string
    {
        if (!$this->teacher_feedback) {
            return null;
        }

        return \Illuminate\Support\Str::limit($this->teacher_feedback, 100);
    }

    /**
     * Get notes summary (first 100 characters)
     *
     * @return string|null
     */
    public function getNotesSummary(): ?string
    {
        if (!$this->session_notes) {
            return null;
        }

        return \Illuminate\Support\Str::limit($this->session_notes, 100);
    }
}
