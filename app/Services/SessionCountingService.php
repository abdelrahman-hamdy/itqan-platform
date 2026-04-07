<?php

namespace App\Services;

use App\Jobs\CalculateSessionEarningsJob;
use App\Models\BaseSession;
use App\Models\TeacherEarning;
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
     * Accepts both BaseSessionAttendance and MeetingAttendance models
     * (both have counts_for_subscription, subscription_counted_at fields).
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

            // Check both tracking systems: MeetingAttendance.subscription_counted_at
            // (group sessions) and session.subscription_counted (individual sessions).
            // Individual sessions set subscription_counted on the session, NOT
            // subscription_counted_at on MeetingAttendance.
            $wasCountedOnAttendance = (bool) $attendance->subscription_counted_at;
            $wasCountedOnSession = (bool) $session->subscription_counted;
            $wasCounted = $wasCountedOnAttendance || $wasCountedOnSession;

            if ($counts && ! $wasCounted) {
                $this->applySubscriptionForStudent($attendance, $session, $studentId);
            } elseif (! $counts && $wasCounted) {
                $this->reverseSubscriptionForStudent($attendance, $session, $studentId);
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
            $subscription->useSession();
            $attendance->update(['subscription_counted_at' => now()]);

            // Also set session-level flag (individual sessions track here)
            if (! $session->subscription_counted) {
                $session->update(['subscription_counted' => true]);
            }

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
    private function reverseSubscriptionForStudent(Model $attendance, BaseSession $session, int $studentId): void
    {
        $subscription = $this->findSubscriptionForStudent($session, $studentId);
        if (! $subscription) {
            return;
        }

        try {
            $subscription->returnSession();
            $attendance->update(['subscription_counted_at' => null]);

            // Also reset session-level flag (individual sessions track here)
            if ($session->subscription_counted) {
                $session->update(['subscription_counted' => false]);
            }

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

        // Fallback: individual sessions have a single subscription
        if (method_exists($session, 'getSubscriptionForCountingPublic')) {
            return $session->getSubscriptionForCountingPublic();
        }

        return null;
    }
}
