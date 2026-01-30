<?php

namespace App\Services;

use App\Contracts\CircleEnrollmentServiceInterface;
use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Exceptions\EnrollmentCapacityException;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
 */
class CircleEnrollmentService implements CircleEnrollmentServiceInterface
{
    /**
     * Enroll a student in a Quran circle.
     *
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to enroll in
     * @return array Result with success status and message
     *
     * @throws \Exception If enrollment fails
     */
    public function enroll(User $user, QuranCircle $circle, bool $createSubscription = true): array
    {
        // Validate enrollment eligibility
        $validation = $this->validateEnrollment($user, $circle);
        if (! $validation['eligible']) {
            return [
                'success' => false,
                'error' => $validation['reason'],
            ];
        }

        try {
            $result = DB::transaction(function () use ($circle, $user, $createSubscription) {
                $academy = $user->academy;

                // Validate user has an academy association
                if (! $academy) {
                    throw new \InvalidArgumentException(__('User must belong to an academy to enroll in a circle.'));
                }

                // Lock the circle row to prevent race conditions during enrollment
                // This ensures only one enrollment can happen at a time for this circle
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Double-check capacity after acquiring lock
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    throw EnrollmentCapacityException::circleFull(
                        circleId: (string) $lockedCircle->id,
                        currentCount: $lockedCircle->enrolled_students,
                        maxCapacity: $lockedCircle->max_students,
                        circleName: $lockedCircle->name ?? null
                    );
                }

                // Create enrollment using the new QuranCircleEnrollment model
                $enrollment = QuranCircleEnrollment::create([
                    'circle_id' => $lockedCircle->id,
                    'student_id' => $user->id,
                    'enrolled_at' => now(),
                    'status' => QuranCircleEnrollment::STATUS_ENROLLED,
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                    'makeup_sessions_used' => 0,
                    'current_level' => $lockedCircle->memorization_level ?? 'beginner',
                ]);

                // Also maintain backward compatibility with pivot table
                // This ensures existing code using students() relationship still works
                if (! $lockedCircle->students()->where('users.id', $user->id)->exists()) {
                    $lockedCircle->students()->attach($user->id, [
                        'enrolled_at' => now(),
                        'status' => QuranCircleEnrollment::STATUS_ENROLLED,
                        'attendance_count' => 0,
                        'missed_sessions' => 0,
                        'makeup_sessions_used' => 0,
                        'current_level' => $lockedCircle->memorization_level ?? 'beginner',
                    ]);
                }

                $subscription = null;

                // Create subscription if requested (not for free trials)
                if ($createSubscription && ($lockedCircle->monthly_fee === null || $lockedCircle->monthly_fee > 0)) {
                    // Create subscription with polymorphic education_unit linking to circle
                    $subscription = QuranSubscription::create([
                        'academy_id' => $academy->id,
                        'student_id' => $user->id,
                        'quran_teacher_id' => $lockedCircle->quran_teacher_id,
                        'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                        'subscription_type' => 'group',
                        // Link to education unit (polymorphic relationship)
                        'education_unit_id' => $lockedCircle->id,
                        'education_unit_type' => QuranCircle::class,
                        'total_sessions' => $lockedCircle->monthly_sessions_count ?? 8,
                        'sessions_used' => 0,
                        'sessions_remaining' => $lockedCircle->monthly_sessions_count ?? 8,
                        'total_price' => $lockedCircle->monthly_fee ?? 0,
                        'discount_amount' => 0,
                        'final_price' => $lockedCircle->monthly_fee ?? 0,
                        'currency' => getCurrencyCode(null, $academy),
                        'billing_cycle' => 'monthly',
                        'payment_status' => ($lockedCircle->monthly_fee && $lockedCircle->monthly_fee > 0) ? 'pending' : 'paid',
                        'status' => SessionSubscriptionStatus::ACTIVE->value,
                        'memorization_level' => $lockedCircle->memorization_level ?? 'beginner',
                        'starts_at' => now(),
                        'next_payment_at' => ($lockedCircle->monthly_fee && $lockedCircle->monthly_fee > 0) ? now()->addMonth() : null,
                        'auto_renew' => true,
                    ]);

                    // Link the enrollment to the subscription
                    $enrollment->update(['subscription_id' => $subscription->id]);
                }

                // Update circle enrollment count atomically
                $lockedCircle->increment('enrolled_students');

                // Refresh the locked instance to get updated count
                $lockedCircle->refresh();

                // Check if circle is now full using refreshed count
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    $lockedCircle->update(['enrollment_status' => 'full']);
                }

                return [
                    'enrollment' => $enrollment,
                    'subscription' => $subscription,
                ];
            });

            return [
                'success' => true,
                'message' => 'تم تسجيلك في الحلقة بنجاح!',
                'enrollment' => $result['enrollment'],
                'subscription' => $result['subscription'],
            ];
        } catch (EnrollmentCapacityException $e) {
            // Report the exception (uses built-in report method)
            $e->report();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'capacity_exceeded',
                'available_slots' => $e->getAvailableSlots(),
            ];
        } catch (\Exception $e) {
            Log::error('Error enrolling student in circle', [
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
     * @throws \Exception If leave fails
     */
    public function leave(User $user, QuranCircle $circle, bool $cancelSubscription = true): array
    {
        // Check if student is enrolled
        if (! $this->isEnrolled($user, $circle)) {
            return [
                'success' => false,
                'error' => 'You are not enrolled in this circle',
            ];
        }

        try {
            DB::transaction(function () use ($circle, $user, $cancelSubscription) {
                $academy = $user->academy;

                // Lock the circle row to prevent race conditions
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Find the enrollment record using new model
                $enrollment = QuranCircleEnrollment::where('circle_id', $lockedCircle->id)
                    ->where('student_id', $user->id)
                    ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
                    ->first();

                // Mark enrollment as dropped (not deleted - preserves history)
                if ($enrollment) {
                    $enrollment->drop();

                    // Cancel linked subscription if requested
                    if ($cancelSubscription && $enrollment->subscription_id) {
                        $subscription = $enrollment->subscription;
                        if ($subscription && in_array($subscription->status, [SessionSubscriptionStatus::ACTIVE, SessionSubscriptionStatus::PENDING])) {
                            $subscription->cancel('Student left the circle');
                        }
                        // Unlink subscription from enrollment
                        $enrollment->unlinkSubscription();
                    }
                }

                // Also update pivot table for backward compatibility
                $lockedCircle->students()->updateExistingPivot($user->id, [
                    'status' => QuranCircleEnrollment::STATUS_DROPPED,
                ]);

                // Update circle enrollment count atomically
                $lockedCircle->decrement('enrolled_students');

                // If circle was full, open it for enrollment
                if ($lockedCircle->enrollment_status === 'full') {
                    $lockedCircle->update(['enrollment_status' => 'open']);
                }
            });

            return [
                'success' => true,
                'message' => 'تم إلغاء تسجيلك من الحلقة بنجاح',
            ];
        } catch (\Exception $e) {
            Log::error('Error removing student from circle', [
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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

    /**
     * Get the enrollment record for a student in a circle.
     */
    public function getEnrollment(User $user, QuranCircle $circle): ?QuranCircleEnrollment
    {
        return QuranCircleEnrollment::where('circle_id', $circle->id)
            ->where('student_id', $user->id)
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->first();
    }

    /**
     * Check if a student can enroll in a circle.
     */
    public function canEnroll(User $user, QuranCircle $circle): bool
    {
        return $this->validateEnrollment($user, $circle)['eligible'];
    }

    /**
     * Validate if a student is eligible for enrollment.
     *
     * @return array Contains 'eligible' (bool) and 'reason' (string) if not eligible
     */
    protected function validateEnrollment(User $user, QuranCircle $circle): array
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
        // Get enrollment using new model
        $enrollment = $this->getEnrollment($user, $circle);

        if (! $enrollment) {
            // Fallback check with legacy method
            if (! $this->isEnrolled($user, $circle)) {
                return null;
            }
        }

        $academy = $user->academy;

        // Validate user has an academy association for subscription creation
        if (! $academy) {
            return null; // Cannot create subscription without academy context
        }

        // If we have an enrollment with linked subscription, return it
        if ($enrollment && $enrollment->subscription_id) {
            $subscription = $enrollment->subscription;
            if ($subscription && in_array($subscription->status, [SessionSubscriptionStatus::ACTIVE, SessionSubscriptionStatus::PENDING])) {
                $subscription->load(['package', 'quranTeacherUser']);

                return $subscription;
            }
        }

        // Try to find existing subscription via polymorphic relationship (new architecture)
        $subscription = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('education_unit_id', $circle->id)
            ->where('education_unit_type', QuranCircle::class)
            ->whereIn('status', [SessionSubscriptionStatus::ACTIVE->value, SessionSubscriptionStatus::PENDING->value])
            ->with(['package', 'quranTeacherUser'])
            ->first();

        // Fallback to legacy query
        if (! $subscription) {
            $subscription = QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('quran_teacher_id', $circle->quran_teacher_id)
                ->where('subscription_type', 'group')
                ->whereIn('status', [SessionSubscriptionStatus::ACTIVE->value, SessionSubscriptionStatus::PENDING->value])
                ->with(['package', 'quranTeacherUser'])
                ->first();
        }

        // If no subscription exists, create one with polymorphic linking
        if (! $subscription) {
            $subscription = QuranSubscription::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'quran_teacher_id' => $circle->quran_teacher_id,
                'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                'subscription_type' => 'group',
                // Link to education unit (polymorphic relationship)
                'education_unit_id' => $circle->id,
                'education_unit_type' => QuranCircle::class,
                'total_sessions' => $circle->monthly_sessions_count ?? 8,
                'sessions_used' => 0,
                'sessions_remaining' => $circle->monthly_sessions_count ?? 8,
                'total_price' => $circle->monthly_fee ?? 0,
                'discount_amount' => 0,
                'final_price' => $circle->monthly_fee ?? 0,
                'currency' => getCurrencyCode(null, $academy),
                'billing_cycle' => 'monthly',
                'payment_status' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? 'pending' : 'paid',
                'status' => SessionSubscriptionStatus::ACTIVE->value,
                'memorization_level' => $circle->memorization_level ?? 'beginner',
                'starts_at' => now(),
                'next_payment_at' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? now()->addMonth() : null,
                'auto_renew' => true,
            ]);

            // Link subscription to enrollment
            if ($enrollment) {
                $enrollment->update(['subscription_id' => $subscription->id]);
            }

            $subscription->load(['package', 'quranTeacherUser']);
        }

        return $subscription;
    }

    /**
     * Link an existing subscription to an enrollment.
     *
     * @param  QuranCircleEnrollment  $enrollment  The enrollment to link
     * @param  QuranSubscription  $subscription  The subscription to link
     */
    public function linkSubscriptionToEnrollment(QuranCircleEnrollment $enrollment, QuranSubscription $subscription): QuranCircleEnrollment
    {
        $enrollment->update(['subscription_id' => $subscription->id]);

        // Also update subscription's education unit if not set
        if (! $subscription->education_unit_id) {
            $subscription->update([
                'education_unit_id' => $enrollment->circle_id,
                'education_unit_type' => QuranCircle::class,
            ]);
        }

        return $enrollment;
    }
}
