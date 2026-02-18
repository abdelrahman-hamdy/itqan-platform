<?php

namespace App\Services\Circle;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages the lifecycle of a student's enrollment state in a circle.
 *
 * Handles leaving, status checks, fetching the enrollment record,
 * and subscription linking/creation for enrolled students.
 *
 * Extracted from CircleEnrollmentService to isolate status-management logic.
 */
class CircleEnrollmentStatusService
{
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
        // Check if student is enrolled
        if (! $this->isEnrolled($user, $circle)) {
            return [
                'success' => false,
                'error' => 'You are not enrolled in this circle',
            ];
        }

        try {
            DB::transaction(function () use ($circle, $user, $cancelSubscription) {
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
                if ($lockedCircle->enrollment_status === CircleEnrollmentStatus::FULL) {
                    $lockedCircle->update(['enrollment_status' => CircleEnrollmentStatus::OPEN]);
                }
            });

            return [
                'success' => true,
                'message' => 'تم إلغاء تسجيلك من الحلقة بنجاح',
            ];
        } catch (Exception $e) {
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
