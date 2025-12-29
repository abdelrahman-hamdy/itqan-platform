<?php

namespace App\Contracts;

use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;

/**
 * Interface for Quran circle enrollment service.
 *
 * Handles student enrollment and withdrawal from Quran circles
 * with capacity management and subscription creation.
 */
interface CircleEnrollmentServiceInterface
{
    /**
     * Enroll a student in a Quran circle.
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to enroll in
     * @return array Result with success status and message/error
     *
     * @throws \Exception If enrollment fails
     */
    public function enroll(User $user, QuranCircle $circle): array;

    /**
     * Remove a student from a Quran circle.
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to leave
     * @return array Result with success status and message/error
     *
     * @throws \Exception If leave fails
     */
    public function leave(User $user, QuranCircle $circle): array;

    /**
     * Check if a student is enrolled in a circle.
     *
     * @param  User  $user
     * @param  QuranCircle  $circle
     * @return bool True if student is enrolled
     */
    public function isEnrolled(User $user, QuranCircle $circle): bool;

    /**
     * Check if a student can enroll in a circle.
     *
     * @param  User  $user
     * @param  QuranCircle  $circle
     * @return bool True if student can enroll
     */
    public function canEnroll(User $user, QuranCircle $circle): bool;

    /**
     * Get or create subscription for an enrolled student.
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle
     * @return QuranSubscription|null The subscription or null if not enrolled
     */
    public function getOrCreateSubscription(User $user, QuranCircle $circle): ?QuranSubscription;
}
