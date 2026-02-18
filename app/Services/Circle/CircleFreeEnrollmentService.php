<?php

namespace App\Services\Circle;

use Exception;
use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\EnrollmentCapacityException;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles immediate (free) circle enrollment.
 *
 * Creates the enrollment record, pivot entry, and free subscription
 * atomically within a single database transaction.
 *
 * Extracted from CircleEnrollmentService to isolate free enrollment logic.
 */
class CircleFreeEnrollmentService
{
    /**
     * Enroll student immediately (for free circles).
     */
    public function enrollImmediately(User $user, QuranCircle $circle, $academy, bool $createSubscription): array
    {
        try {
            $result = DB::transaction(function () use ($circle, $user, $academy, $createSubscription) {
                // Lock the circle row to prevent race conditions during enrollment
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

                // Create free subscription
                if ($createSubscription) {
                    $subscription = QuranSubscription::create([
                        'academy_id' => $academy->id,
                        'student_id' => $user->id,
                        'quran_teacher_id' => $lockedCircle->quran_teacher_id,
                        'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                        'subscription_type' => 'group',
                        'education_unit_id' => $lockedCircle->id,
                        'education_unit_type' => QuranCircle::class,
                        'total_sessions' => $lockedCircle->monthly_sessions_count ?? 8,
                        'sessions_used' => 0,
                        'sessions_remaining' => $lockedCircle->monthly_sessions_count ?? 8,
                        'total_price' => 0,
                        'discount_amount' => 0,
                        'final_price' => 0,
                        'currency' => getCurrencyCode(null, $academy),
                        'billing_cycle' => 'monthly',
                        'payment_status' => SubscriptionPaymentStatus::PAID,
                        'status' => SessionSubscriptionStatus::ACTIVE,
                        'memorization_level' => $lockedCircle->memorization_level ?? 'beginner',
                        'starts_at' => now(),
                        'auto_renew' => false,
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
                    $lockedCircle->update(['enrollment_status' => CircleEnrollmentStatus::FULL]);
                }

                Log::info('[CircleEnrollment] Free circle enrollment completed', [
                    'user_id' => $user->id,
                    'circle_id' => $lockedCircle->id,
                    'enrollment_id' => $enrollment->id,
                    'subscription_id' => $subscription?->id,
                ]);

                return [
                    'enrollment' => $enrollment,
                    'subscription' => $subscription,
                ];
            });

            return [
                'success' => true,
                'requires_payment' => false,
                'message' => __('circles.enrollment_success'),
                'enrollment' => $result['enrollment'],
                'subscription' => $result['subscription'],
            ];
        } catch (EnrollmentCapacityException $e) {
            $e->report();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'capacity_exceeded',
                'available_slots' => $e->getAvailableSlots(),
            ];
        } catch (Exception $e) {
            Log::error('[CircleEnrollment] Error enrolling student in free circle', [
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
