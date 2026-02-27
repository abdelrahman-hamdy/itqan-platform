<?php

namespace App\Services;

use Exception;
use App\Contracts\CircleEnrollmentServiceInterface;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\Circle\CircleEnrollmentStatusService;
use App\Services\Circle\CircleEnrollmentValidator;
use App\Services\Circle\CircleFreeEnrollmentService;
use App\Services\Circle\CirclePaidEnrollmentService;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling student enrollment in Quran circles.
 *
 * DECOUPLED ARCHITECTURE:
 * - Uses QuranCircleEnrollment model instead of pivot table for better tracking
 * - Subscriptions are created with polymorphic education_unit linking to the circle
 * - Enrollments can exist independently from subscriptions (for trials, free circles)
 * - Cancelling subscription doesn't remove enrollment or affect circle data
 *
 * Extracted from StudentProfileController to reduce controller size.
 * Handles enrollInCircle() and leaveCircle() logic (~200 lines).
 *
 * This class is a thin delegate — all logic lives in the Circle sub-services:
 * @see CircleEnrollmentValidator
 * @see CirclePaidEnrollmentService
 * @see CircleFreeEnrollmentService
 * @see CircleEnrollmentStatusService
 */
class CircleEnrollmentService implements CircleEnrollmentServiceInterface
{
    public function __construct(
        private readonly CircleEnrollmentValidator $validator,
        private readonly CirclePaidEnrollmentService $paidEnrollment,
        private readonly CircleFreeEnrollmentService $freeEnrollment,
        private readonly CircleEnrollmentStatusService $statusService,
    ) {}

    /**
     * Enroll a student in a Quran circle.
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to enroll in
     * @return array Result with success status and message
     *
     * @throws Exception If enrollment fails
     */
    public function enroll(User $user, QuranCircle $circle, bool $createSubscription = true, ?string $paymentGateway = null): array
    {
        // Validate enrollment eligibility
        $validation = $this->validateEnrollment($user, $circle);
        if (! $validation['eligible']) {
            return [
                'success' => false,
                'error' => $validation['reason'],
            ];
        }

        $academy = $user->academy;

        // Validate user has an academy association
        if (! $academy) {
            return [
                'success' => false,
                'error' => __('User must belong to an academy to enroll in a circle.'),
            ];
        }

        // Check if circle has a fee - if so, redirect to payment flow
        $hasFee = $circle->monthly_fee && $circle->monthly_fee > 0;

        Log::info('[CircleEnrollment] Starting enrollment', [
            'user_id' => $user->id,
            'circle_id' => $circle->id,
            'has_fee' => $hasFee,
            'monthly_fee' => $circle->monthly_fee,
        ]);

        if ($hasFee) {
            // PAID CIRCLE: Create pending subscription and redirect to payment
            // DO NOT enroll yet - enrollment happens after successful payment
            return $this->createPendingSubscriptionForPayment($user, $circle, $academy, $paymentGateway);
        }

        // FREE CIRCLE: Enroll immediately
        return $this->enrollImmediately($user, $circle, $academy, $createSubscription);
    }

    /**
     * Create a pending subscription for a paid circle and return Paymob payment redirect URL.
     * The student is NOT enrolled until payment is successful.
     * Uses the same flow as individual subscriptions - directly redirects to Paymob.
     */
    protected function createPendingSubscriptionForPayment(User $user, QuranCircle $circle, $academy, ?string $paymentGateway = null): array
    {
        return $this->paidEnrollment->createPendingSubscriptionForPayment($user, $circle, $academy, $paymentGateway);
    }

    /**
     * Enroll student immediately (for free circles).
     */
    protected function enrollImmediately(User $user, QuranCircle $circle, $academy, bool $createSubscription): array
    {
        return $this->freeEnrollment->enrollImmediately($user, $circle, $academy, $createSubscription);
    }

    /**
     * Complete enrollment after successful payment.
     * Called by the payment webhook when payment is successful.
     */
    public function completeEnrollmentAfterPayment(QuranSubscription $subscription): array
    {
        if ($subscription->subscription_type !== 'group' || ! $subscription->education_unit_id) {
            return [
                'success' => false,
                'error' => 'Invalid subscription for circle enrollment',
            ];
        }

        $circle = QuranCircle::find($subscription->education_unit_id);
        if (! $circle) {
            return [
                'success' => false,
                'error' => 'Circle not found',
            ];
        }

        $user = $subscription->student;
        if (! $user) {
            return [
                'success' => false,
                'error' => 'Student not found',
            ];
        }

        // Check if already enrolled
        if ($this->isEnrolled($user, $circle)) {
            Log::info('[CircleEnrollment] Student already enrolled after payment', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'circle_id' => $circle->id,
            ]);

            return [
                'success' => true,
                'message' => 'Already enrolled',
            ];
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($circle, $user, $subscription) {
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Create enrollment
                $enrollment = QuranCircleEnrollment::create([
                    'circle_id' => $lockedCircle->id,
                    'student_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'enrolled_at' => now(),
                    'status' => QuranCircleEnrollment::STATUS_ENROLLED,
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                    'makeup_sessions_used' => 0,
                    'current_level' => $lockedCircle->memorization_level ?? 'beginner',
                ]);

                // Update circle enrollment count
                $lockedCircle->increment('enrolled_students');

                // Check if circle is now full
                $lockedCircle->refresh();
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    $lockedCircle->update(['enrollment_status' => \App\Enums\CircleEnrollmentStatus::FULL]);
                }

                Log::info('[CircleEnrollment] Enrollment completed after payment', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'circle_id' => $lockedCircle->id,
                    'enrollment_id' => $enrollment->id,
                ]);
            });

            return [
                'success' => true,
                'message' => __('circles.enrollment_success'),
            ];
        } catch (Exception $e) {
            Log::error('[CircleEnrollment] Error completing enrollment after payment', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove a student from a Quran circle.
     *
     * DECOUPLED ARCHITECTURE:
     * - Marks enrollment as dropped (soft removes from circle)
     * - Cancels linked subscription but doesn't delete it
     * - Circle and subscription data remain intact for records
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to leave
     * @param  bool  $cancelSubscription  Whether to also cancel linked subscription (default: true)
     * @return array Result with success status and message
     *
     * @throws Exception If leave fails
     */
    public function leave(User $user, QuranCircle $circle, bool $cancelSubscription = true): array
    {
        return $this->statusService->leave($user, $circle, $cancelSubscription);
    }

    /**
     * Check if a student is enrolled in a circle.
     * Uses the new QuranCircleEnrollment model first, falls back to pivot table.
     */
    public function isEnrolled(User $user, QuranCircle $circle): bool
    {
        return $this->statusService->isEnrolled($user, $circle);
    }

    /**
     * Get the enrollment record for a student in a circle.
     */
    public function getEnrollment(User $user, QuranCircle $circle): ?QuranCircleEnrollment
    {
        return $this->statusService->getEnrollment($user, $circle);
    }

    /**
     * Check if a student can enroll in a circle.
     */
    public function canEnroll(User $user, QuranCircle $circle): bool
    {
        return $this->validator->canEnroll($user, $circle);
    }

    /**
     * Validate if a student is eligible for enrollment.
     *
     * @return array Contains 'eligible' (bool) and 'reason' (string) if not eligible
     */
    protected function validateEnrollment(User $user, QuranCircle $circle): array
    {
        return $this->validator->validateEnrollment($user, $circle);
    }

    /**
     * Get or create subscription for an enrolled student.
     *
     * DECOUPLED ARCHITECTURE:
     * - First checks the enrollment's linked subscription
     * - Falls back to finding via legacy queries
     * - Creates new subscription and links to enrollment if needed
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle
     * @return QuranSubscription|null The subscription or null if not enrolled
     */
    public function getOrCreateSubscription(User $user, QuranCircle $circle): ?QuranSubscription
    {
        return $this->statusService->getOrCreateSubscription($user, $circle);
    }

    /**
     * Link an existing subscription to an enrollment.
     *
     * @param  QuranCircleEnrollment  $enrollment  The enrollment to link
     * @param  QuranSubscription  $subscription  The subscription to link
     */
    public function linkSubscriptionToEnrollment(QuranCircleEnrollment $enrollment, QuranSubscription $subscription): QuranCircleEnrollment
    {
        return $this->statusService->linkSubscriptionToEnrollment($enrollment, $subscription);
    }
}
