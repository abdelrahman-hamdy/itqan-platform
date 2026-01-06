<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface for student dashboard data service.
 *
 * Handles loading and caching of student dashboard data including
 * circles, sessions, courses, and trial requests.
 */
interface StudentDashboardServiceInterface
{
    /**
     * Load all dashboard data for a student.
     *
     * @param  User  $user  The student user
     * @return array Dashboard data with keys: circles, privateSessions, trialRequests, interactiveCourses, recordedCourses
     */
    public function loadDashboardData(User $user): array;

    /**
     * Get student's enrolled Quran circles with teacher data.
     *
     * @param  mixed  $academy
     */
    public function getQuranCircles(User $user, $academy): Collection;

    /**
     * Get student's active Quran private sessions (individual subscriptions).
     *
     * @param  mixed  $academy
     */
    public function getQuranPrivateSessions(User $user, $academy): Collection;

    /**
     * Get student's recent Quran trial requests.
     *
     * @param  mixed  $academy
     */
    public function getQuranTrialRequests(User $user, $academy): Collection;

    /**
     * Get student's enrolled interactive courses.
     *
     * @param  mixed  $studentProfile
     * @param  mixed  $academy
     */
    public function getInteractiveCourses($studentProfile, $academy): Collection;

    /**
     * Get student's enrolled recorded courses with progress.
     *
     * @param  mixed  $academy
     */
    public function getRecordedCourses(User $user, $academy): Collection;

    /**
     * Clear all dashboard caches for a student.
     */
    public function clearStudentCache(int $userId, int $academyId): void;
}
