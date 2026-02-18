<?php

namespace App\Services\Circle;

use App\Enums\CircleEnrollmentStatus;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\User;

/**
 * Validates whether a student is eligible to enroll in a Quran circle.
 *
 * Extracted from CircleEnrollmentService to isolate enrollment eligibility logic.
 */
class CircleEnrollmentValidator
{
    /**
     * Validate if a student is eligible for enrollment.
     *
     * @return array Contains 'eligible' (bool) and 'reason' (string) if not eligible
     */
    public function validateEnrollment(User $user, QuranCircle $circle): array
    {
        // Check if already enrolled
        if ($this->isEnrolled($user, $circle)) {
            return [
                'eligible' => false,
                'reason' => 'You are already enrolled in this circle',
            ];
        }

        // Check if circle is active
        if ($circle->status !== true) {
            return [
                'eligible' => false,
                'reason' => 'This circle is not active',
            ];
        }

        // Check if circle is open for enrollment
        if ($circle->enrollment_status !== CircleEnrollmentStatus::OPEN) {
            return [
                'eligible' => false,
                'reason' => 'This circle is not open for enrollment',
            ];
        }

        // Check if circle is full
        if ($circle->enrolled_students >= $circle->max_students) {
            return [
                'eligible' => false,
                'reason' => 'This circle is full',
            ];
        }

        return ['eligible' => true];
    }

    /**
     * Check if a student can enroll in a circle.
     */
    public function canEnroll(User $user, QuranCircle $circle): bool
    {
        return $this->validateEnrollment($user, $circle)['eligible'];
    }

    /**
     * Check if a student is enrolled in a circle.
     * Uses the new QuranCircleEnrollment model first, falls back to pivot table.
     */
    public function isEnrolled(User $user, QuranCircle $circle): bool
    {
        // Check using new enrollment model first
        $enrollment = QuranCircleEnrollment::where('circle_id', $circle->id)
            ->where('student_id', $user->id)
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->exists();

        if ($enrollment) {
            return true;
        }

        // Fallback to pivot table for legacy data
        return $circle->students()
            ->where('users.id', $user->id)
            ->wherePivot('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->exists();
    }
}
