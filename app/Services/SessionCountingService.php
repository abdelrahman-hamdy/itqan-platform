<?php

namespace App\Services;

use App\Jobs\CalculateSessionEarningsJob;
use App\Models\BaseSession;
use App\Models\BaseSessionAttendance;
use App\Models\TeacherEarning;
use Exception;
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
                TeacherEarning::where('session_type', get_class($session))
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
     * Side effects:
     * - true + not yet counted: apply subscription usage
     * - false + already counted: reverse subscription usage
     */
    public function setCountsForSubscription(
        BaseSessionAttendance $attendance,
        bool $counts,
        int $setBy
    ): void {
        DB::transaction(function () use ($attendance, $counts, $setBy) {
            $attendance->update([
                'counts_for_subscription' => $counts,
                'counts_for_subscription_set_by' => $setBy,
                'counts_for_subscription_set_at' => now(),
            ]);

            if ($counts && ! $attendance->subscription_counted_at) {
                $this->applySubscriptionForStudent($attendance);
            } elseif (! $counts && $attendance->subscription_counted_at) {
                $this->reverseSubscriptionForStudent($attendance);
            }

            Log::info('SessionCountingService: counts_for_subscription updated', [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id,
                'counts' => $counts,
                'set_by' => $setBy,
            ]);
        });
    }

    /**
     * Apply subscription usage for a specific student.
     */
    private function applySubscriptionForStudent(BaseSessionAttendance $attendance): void
    {
        $session = $attendance->session;
        if (! $session) {
            return;
        }

        $subscription = $this->findSubscriptionForStudent($session, $attendance->student_id);
        if (! $subscription) {
            Log::warning('SessionCountingService: No subscription found for student', [
                'session_id' => $session->id,
                'student_id' => $attendance->student_id,
            ]);

            return;
        }

        try {
            $subscription->useSession();
            $attendance->update(['subscription_counted_at' => now()]);

            Log::info('SessionCountingService: Subscription decremented', [
                'subscription_id' => $subscription->id,
                'student_id' => $attendance->student_id,
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
    private function reverseSubscriptionForStudent(BaseSessionAttendance $attendance): void
    {
        $session = $attendance->session;
        if (! $session) {
            return;
        }

        $subscription = $this->findSubscriptionForStudent($session, $attendance->student_id);
        if (! $subscription) {
            return;
        }

        try {
            $subscription->returnSession();
            $attendance->update(['subscription_counted_at' => null]);

            Log::info('SessionCountingService: Subscription reversed', [
                'subscription_id' => $subscription->id,
                'student_id' => $attendance->student_id,
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
        // Use session's existing subscription resolution logic
        if (method_exists($session, 'getSubscriptionForStudent')) {
            return $session->getSubscriptionForStudent($studentId);
        }

        // Fallback: use the session-level subscription (individual sessions)
        if (method_exists($session, 'getSubscriptionForCounting')) {
            return $session->getSubscriptionForCounting();
        }

        return null;
    }
}
