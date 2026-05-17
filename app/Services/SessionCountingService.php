<?php

namespace App\Services;

use App\Jobs\CalculateSessionEarningsJob;
use App\Models\BaseSession;
use App\Models\SessionConsumption;
use App\Models\TeacherEarning;
use App\Models\User;
use App\Services\Subscription\SubscriptionConsumption;
use App\Support\Subscriptions\AttendanceConsumptionMapper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Session Counting Service
 *
 * Handles admin overrides for session counting flags with proper side effects:
 * - counts_for_teacher: controls teacher earnings
 * - counts_for_subscription: controls per-student subscription counting
 *
 * When admin toggles a flag, this service applies or reverses the
 * financial side effects (subscription usage, teacher earnings).
 */
class SessionCountingService
{
    /**
     * Set whether a session counts for teacher earnings.
     *
     * Side effects:
     * - true: re-dispatch earnings calculation job
     * - false: delete existing teacher earning record
     */
    public function setCountsForTeacher(BaseSession $session, bool $counts, int $setBy): void
    {
        DB::transaction(function () use ($session, $counts, $setBy) {
            $session->update([
                'counts_for_teacher' => $counts,
                'counts_for_teacher_set_by' => $setBy,
                'counts_for_teacher_set_at' => now(),
            ]);

            if ($counts) {
                dispatch(new CalculateSessionEarningsJob($session));
            } else {
                // Use both morph alias and FQCN to cover legacy data
                TeacherEarning::whereIn('session_type', [
                    $session->getMorphClass(),
                    get_class($session),
                ])
                    ->where('session_id', $session->id)
                    ->delete();
            }

            Log::info('SessionCountingService: counts_for_teacher updated', [
                'session_id' => $session->id,
                'counts' => $counts,
                'set_by' => $setBy,
            ]);
        });
    }

    /**
     * Set whether a session counts for a student's subscription.
     *
     * Accepts a MeetingAttendance model (the single source of truth for
     * per-student attendance counting flags).
     *
     * Side effects:
     * - true + not yet counted: apply subscription usage
     * - false + already counted: reverse subscription usage
     */
    public function setCountsForSubscription(
        Model $attendance,
        BaseSession $session,
        bool $counts,
        int $setBy
    ): void {
        DB::transaction(function () use ($attendance, $session, $counts, $setBy) {
            $attendance->update([
                'counts_for_subscription' => $counts,
                'counts_for_subscription_set_by' => $setBy,
                'counts_for_subscription_set_at' => now(),
            ]);

            $studentId = $attendance->student_id ?? $attendance->user_id;

            $wasCounted = SessionConsumption::query()
                ->where('session_id', $session->getKey())
                ->where('session_type', $session->getMorphClass())
                ->where('student_user_id', $studentId)
                ->whereNull('reversed_at')
                ->exists();

            if ($counts && ! $wasCounted) {
                $this->applySubscriptionForStudent($attendance, $session, $studentId);
            } elseif (! $counts && $wasCounted) {
                $this->reverseSubscriptionForStudent($session, $studentId);
            }

            Log::info('SessionCountingService: counts_for_subscription updated', [
                'attendance_id' => $attendance->id,
                'student_id' => $studentId,
                'counts' => $counts,
                'set_by' => $setBy,
            ]);
        });
    }

    /**
     * Apply subscription usage for a specific student.
     */
    private function applySubscriptionForStudent(Model $attendance, BaseSession $session, int $studentId): void
    {
        $subscription = $this->findSubscriptionForStudent($session, $studentId);
        if (! $subscription) {
            Log::warning('SessionCountingService: No subscription found for student', [
                'session_id' => $session->id,
                'student_id' => $studentId,
            ]);

            return;
        }

        try {
            $student = User::find($studentId);
            if (! $student instanceof User) {
                Log::warning('SessionCountingService: Student user missing for consumption', [
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                ]);

                return;
            }

            $consumptionType = AttendanceConsumptionMapper::consumptionTypeFor(
                $attendance->attendance_status,
                countsForSubscription: true,
            ) ?? SessionConsumption::TYPE_ATTENDED;

            app(SubscriptionConsumption::class)->record(
                $session,
                $student,
                $subscription,
                source: SessionConsumption::SOURCE_ADMIN_MANUAL,
                sourceUser: auth()->user(),
                consumptionType: $consumptionType,
            );

            Log::info('SessionCountingService: Subscription decremented', [
                'subscription_id' => $subscription->id,
                'student_id' => $studentId,
            ]);
        } catch (Exception $e) {
            Log::error('SessionCountingService: Failed to apply subscription', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse subscription usage for a specific student.
     */
    private function reverseSubscriptionForStudent(BaseSession $session, int $studentId): void
    {
        $subscription = $this->findSubscriptionForStudent($session, $studentId);
        if (! $subscription) {
            return;
        }

        try {
            // INV-B1: the consumption row is uniquely identified by
            // (session_id, session_type, subscription_id, subscription_type)
            // — cycle_id is NOT part of the unique key.
            $existing = SessionConsumption::query()
                ->where('session_id', $session->getKey())
                ->where('session_type', $session->getMorphClass())
                ->where('subscription_id', $subscription->getKey())
                ->where('subscription_type', $subscription->getMorphClass())
                ->whereNull('reversed_at')
                ->first();

            if (! $existing instanceof SessionConsumption) {
                return;
            }

            $reverser = auth()->user();
            if (! $reverser instanceof User) {
                Log::warning('SessionCountingService: No actor available to reverse consumption', [
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                ]);

                return;
            }

            app(SubscriptionConsumption::class)->reverse(
                $existing,
                'admin_manual_override',
                $reverser,
            );

            Log::info('SessionCountingService: Subscription reversed', [
                'subscription_id' => $subscription->id,
                'student_id' => $studentId,
            ]);
        } catch (Exception $e) {
            Log::error('SessionCountingService: Failed to reverse subscription', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find the active subscription for a student in a session.
     */
    private function findSubscriptionForStudent(BaseSession $session, int $studentId)
    {
        if (method_exists($session, 'getSubscriptionForStudent')) {
            return $session->getSubscriptionForStudent($studentId);
        }

        if (method_exists($session, 'getSubscriptionForCounting')) {
            return $session->getSubscriptionForCounting();
        }

        return null;
    }
}
