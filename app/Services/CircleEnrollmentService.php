<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\EnrollmentStatus;
use App\Exceptions\EnrollmentCapacityException;
use App\Contracts\CircleEnrollmentServiceInterface;

/**
 * Service for handling student enrollment in Quran circles.
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
    public function enroll(User $user, QuranCircle $circle): array
    {
        // Validate enrollment eligibility
        $validation = $this->validateEnrollment($user, $circle);
        if (!$validation['eligible']) {
            return [
                'success' => false,
                'error' => $validation['reason'],
            ];
        }

        try {
            DB::transaction(function () use ($circle, $user) {
                $academy = $user->academy;

                // Lock the circle row to prevent race conditions during enrollment
                // This ensures only one enrollment can happen at a time for this circle
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Double-check capacity after acquiring lock
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    throw EnrollmentCapacityException::circleFull(
                        circleId: (string) $lockedCircle->id,
                        currentCount: $lockedCircle->enrolled_students,
                        maxCapacity: $lockedCircle->max_students,
                        circleName: $lockedCircle->name_ar ?? $lockedCircle->name_en ?? null
                    );
                }

                // Enroll student in circle
                $lockedCircle->students()->attach($user->id, [
                    'enrolled_at' => now(),
                    'status' => EnrollmentStatus::ENROLLED->value,
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                    'makeup_sessions_used' => 0,
                    'current_level' => 'beginner',
                ]);

                // Create a group subscription for this enrollment
                QuranSubscription::create([
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'quran_teacher_id' => $lockedCircle->quran_teacher_id,
                    'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                    'subscription_type' => 'group',
                    'total_sessions' => $lockedCircle->sessions_per_month ?? 8,
                    'sessions_used' => 0,
                    'sessions_remaining' => $lockedCircle->sessions_per_month ?? 8,
                    'total_price' => $lockedCircle->monthly_fee ?? 0,
                    'discount_amount' => 0,
                    'final_price' => $lockedCircle->monthly_fee ?? 0,
                    'currency' => $lockedCircle->currency ?? 'SAR',
                    'billing_cycle' => 'monthly',
                    'payment_status' => ($lockedCircle->monthly_fee && $lockedCircle->monthly_fee > 0) ? 'pending' : 'paid',
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'memorization_level' => $lockedCircle->memorization_level ?? 'beginner',
                    'starts_at' => now(),
                    'next_payment_at' => ($lockedCircle->monthly_fee && $lockedCircle->monthly_fee > 0) ? now()->addMonth() : null,
                    'auto_renew' => true,
                ]);

                // Update circle enrollment count atomically
                $lockedCircle->increment('enrolled_students');

                // Refresh the locked instance to get updated count
                // Note: We refresh the locked instance, not fetch fresh from DB
                $lockedCircle->refresh();

                // Check if circle is now full using refreshed count
                if ($lockedCircle->enrolled_students >= $lockedCircle->max_students) {
                    $lockedCircle->update(['enrollment_status' => 'full']);
                }
            });

            return [
                'success' => true,
                'message' => 'تم تسجيلك في الحلقة بنجاح!',
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
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle to leave
     * @return array Result with success status and message
     *
     * @throws \Exception If leave fails
     */
    public function leave(User $user, QuranCircle $circle): array
    {
        // Check if student is enrolled
        if (!$this->isEnrolled($user, $circle)) {
            return [
                'success' => false,
                'error' => 'You are not enrolled in this circle',
            ];
        }

        try {
            DB::transaction(function () use ($circle, $user) {
                $academy = $user->academy;

                // Lock the circle row to prevent race conditions
                $lockedCircle = QuranCircle::lockForUpdate()->find($circle->id);

                // Remove student from circle
                $lockedCircle->students()->detach($user->id);

                // Cancel the group subscription if it exists
                $subscription = QuranSubscription::where('student_id', $user->id)
                    ->where('academy_id', $academy->id)
                    ->where('quran_teacher_id', $lockedCircle->quran_teacher_id)
                    ->where('subscription_type', 'group')
                    ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value])
                    ->first();

                if ($subscription) {
                    $subscription->cancel('Student left the circle');
                }

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
     */
    public function isEnrolled(User $user, QuranCircle $circle): bool
    {
        return $circle->students()->where('users.id', $user->id)->exists();
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
        if ($circle->enrollment_status !== 'open') {
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
     * @param  User  $user  The student user
     * @param  QuranCircle  $circle  The circle
     * @return QuranSubscription|null The subscription or null if not enrolled
     */
    public function getOrCreateSubscription(User $user, QuranCircle $circle): ?QuranSubscription
    {
        if (!$this->isEnrolled($user, $circle)) {
            return null;
        }

        $academy = $user->academy;

        // Try to find existing subscription
        $subscription = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $circle->quran_teacher_id)
            ->where('subscription_type', 'group')
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value])
            ->with(['package', 'quranTeacherUser'])
            ->first();

        // If no subscription exists, create one
        if (!$subscription) {
            $subscription = QuranSubscription::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'quran_teacher_id' => $circle->quran_teacher_id,
                'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                'subscription_type' => 'group',
                'total_sessions' => $circle->sessions_per_month ?? 8,
                'sessions_used' => 0,
                'sessions_remaining' => $circle->sessions_per_month ?? 8,
                'total_price' => $circle->monthly_fee ?? 0,
                'discount_amount' => 0,
                'final_price' => $circle->monthly_fee ?? 0,
                'currency' => $circle->currency ?? 'SAR',
                'billing_cycle' => 'monthly',
                'payment_status' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? 'pending' : 'paid',
                'status' => SubscriptionStatus::ACTIVE->value,
                'memorization_level' => $circle->memorization_level ?? 'beginner',
                'starts_at' => now(),
                'next_payment_at' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? now()->addMonth() : null,
                'auto_renew' => true,
            ]);

            $subscription->load(['package', 'quranTeacherUser']);
        }

        return $subscription;
    }
}
