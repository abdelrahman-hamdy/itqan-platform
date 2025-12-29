<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Interface for student statistics calculation service.
 *
 * Provides comprehensive statistics for student dashboard including
 * attendance, learning time, homework, quizzes, and Quran progress.
 */
interface StudentStatisticsServiceInterface
{
    /**
     * Calculate all statistics for a student.
     *
     * @param  User  $user  The student user
     * @return array Complete statistics data including:
     *               - nextSessionText, nextSessionIcon, nextSessionDate
     *               - pendingHomework, pendingQuizzes
     *               - todayLearningHours, todayLearningMinutes
     *               - attendanceRate, totalCompletedSessions
     *               - activeCourses, activeInteractiveCourses, activeRecordedCourses
     *               - quranProgress, quranPages, quranTrialRequestsCount, activeQuranSubscriptions, quranCirclesCount
     */
    public function calculate(User $user): array;

    /**
     * Clear all statistics caches for a student.
     *
     * @param  int  $userId
     * @param  int  $academyId
     * @return void
     */
    public function clearStudentStatsCache(int $userId, int $academyId): void;
}
