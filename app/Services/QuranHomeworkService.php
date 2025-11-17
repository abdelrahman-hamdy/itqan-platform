<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Quran Homework Service
 *
 * Simplified service for managing Quran session homework.
 * Homework is assigned at the session level and graded orally
 * during the session via student_session_reports.
 */
class QuranHomeworkService
{
    /**
     * Create session homework
     *
     * Creates homework assignment for a session. Grading happens
     * during the session and is recorded in student_session_reports.
     */
    public function createSessionHomework(QuranSession $session, array $homeworkData): QuranSessionHomework
    {
        return DB::transaction(function () use ($session, $homeworkData) {
            $homework = QuranSessionHomework::create([
                'session_id' => $session->id,
                'created_by' => auth()->id(),
                ...$homeworkData,
            ]);

            Log::info('Session homework created', [
                'session_id' => $session->id,
                'homework_id' => $homework->id,
                'total_pages' => $homework->total_pages,
                'created_by' => auth()->id(),
            ]);

            return $homework;
        });
    }

    /**
     * Update session homework
     */
    public function updateSessionHomework(QuranSessionHomework $homework, array $homeworkData): QuranSessionHomework
    {
        return DB::transaction(function () use ($homework, $homeworkData) {
            $homework->update($homeworkData);

            Log::info('Session homework updated', [
                'homework_id' => $homework->id,
                'session_id' => $homework->session_id,
                'updated_by' => auth()->id(),
            ]);

            return $homework;
        });
    }

    /**
     * Delete session homework
     */
    public function deleteSessionHomework(QuranSessionHomework $homework): bool
    {
        return DB::transaction(function () use ($homework) {
            $deleted = $homework->delete();

            Log::info('Session homework deleted', [
                'homework_id' => $homework->id,
                'session_id' => $homework->session_id,
                'deleted_by' => auth()->id(),
            ]);

            return $deleted;
        });
    }

    /**
     * Get session homework details
     */
    public function getSessionHomeworkDetails(QuranSession $session): array
    {
        $homework = $session->sessionHomework;

        if (!$homework) {
            return [
                'has_homework' => false,
                'homework' => null,
            ];
        }

        return [
            'has_homework' => true,
            'homework' => $homework,
            'total_pages' => $homework->total_pages,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'review_pages' => $homework->review_pages,
            'new_memorization_range' => $homework->new_memorization_range,
            'review_range' => $homework->review_range,
            'comprehensive_review_surahs' => $homework->comprehensive_review_surahs_formatted,
            'difficulty_level' => $homework->difficulty_level_arabic,
            'due_date' => $homework->due_date,
            'is_overdue' => $homework->is_overdue,
            'additional_instructions' => $homework->additional_instructions,
        ];
    }
}
