<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\SessionCountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reconcile session counting state across the platform.
 *
 * Two distinct fixes:
 *   1. Sessions where the per-student attendance row says
 *      `counts_for_subscription = false` but the session is still flagged
 *      as `subscription_counted = true`. The reverse-toggle path used to
 *      auto-create UNSCHEDULED replacement sessions; that's been removed,
 *      so we just need to call `returnSession()` to free the quota slot.
 *   2. Subscriptions whose `progress_percentage` reads 100% even though
 *      `sessions_remaining > 0` — `useSession()` peg used to stick after
 *      a refund. Now `returnSession()` recomputes it, so we recompute
 *      historical rows here too.
 *
 * Idempotent. Run with `--dry-run` to preview, then again without to apply.
 */
class ReconcileSessionCounting extends Command
{
    protected $signature = 'sessions:reconcile-counting
                            {--dry-run : Print findings without applying any changes}';

    protected $description = 'Reverse stale session-counted flags and recompute progress_percentage on subscriptions.';

    public function handle(SessionCountingService $countingService): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info($dry
            ? 'DRY RUN — no changes will be persisted.'
            : 'Applying reconciliation changes.');
        $this->newLine();

        // 1. Sessions counted but the per-student row says otherwise.
        $this->info('Step 1: Stale subscription_counted flags');
        $stale = $this->findStaleSubscriptionCountedSessions();
        $this->line(sprintf('  Found %d session(s) needing reverse.', $stale->count()));

        $reversed = 0;
        foreach ($stale as $row) {
            $session = $this->loadSession($row->session_type, $row->session_id);
            $attendance = MeetingAttendance::find($row->id);
            if (! $session || ! $attendance) {
                $this->warn("  Skipping attendance #{$row->id} — session or attendance row missing");

                continue;
            }

            if ($dry) {
                $this->line(sprintf(
                    '  [DRY] Would reverse session #%d (%s) for user #%d',
                    $session->id,
                    $row->session_type,
                    $attendance->user_id,
                ));

                continue;
            }

            try {
                // SetCountsForSubscription(false) handles the reversal AND
                // unstamps the idempotency flags so the next reconcile run
                // doesn't pick the same row again.
                $countingService->setCountsForSubscription(
                    $attendance,
                    $session,
                    false,
                    0, // 0 = system reconcile, not a real user
                );
                $reversed++;
            } catch (Throwable $e) {
                $this->error(sprintf('  Failed to reverse #%d: %s', $session->id, $e->getMessage()));
            }
        }
        if (! $dry) {
            $this->info("  Reversed {$reversed} session(s).");
        }
        $this->newLine();

        // 2. Subscriptions with stale progress_percentage.
        // BaseSubscription is abstract — iterate per concrete child class.
        $this->info('Step 2: Stale progress_percentage on subscriptions');
        $recomputed = 0;
        $stalePercent = 0;
        foreach ([QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class] as $cls) {
            $cls::query()->chunkById(200, function ($chunk) use (&$recomputed, &$stalePercent, $dry) {
                foreach ($chunk as $sub) {
                    $total = (int) ($sub->sessions_used + $sub->sessions_remaining);
                    if ($total <= 0) {
                        continue;
                    }
                    $expected = round(($sub->sessions_used / $total) * 100, 2);
                    $current = (float) ($sub->progress_percentage ?? 0);
                    if (abs($current - $expected) <= 0.01) {
                        continue;
                    }

                    $stalePercent++;
                    if ($dry) {
                        $this->line(sprintf(
                            '  [DRY] Subscription #%d (%s): %.2f%% → %.2f%%',
                            $sub->id,
                            class_basename($sub),
                            $current,
                            $expected,
                        ));

                        continue;
                    }

                    $sub->forceFill(['progress_percentage' => $expected])->save();
                    $recomputed++;
                }
            });
        }

        if ($dry) {
            $this->info("  Found {$stalePercent} subscription(s) with drifted progress_percentage.");
        } else {
            $this->info("  Recomputed {$recomputed} subscription(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Find MeetingAttendance rows where the session is still counted but the
     * student row says it shouldn't be.
     */
    private function findStaleSubscriptionCountedSessions()
    {
        return DB::table('meeting_attendances')
            ->select('id', 'session_id', 'session_type', 'user_id')
            ->where('user_type', 'student')
            ->where('counts_for_subscription', false)
            ->whereNotNull('subscription_counted_at')
            ->get();
    }

    private function loadSession(string $type, int $id)
    {
        return match ($type) {
            QuranSession::class, 'quran_session' => QuranSession::find($id),
            AcademicSession::class, 'academic_session' => AcademicSession::find($id),
            InteractiveCourseSession::class, 'interactive_course_session' => InteractiveCourseSession::find($id),
            default => null,
        };
    }
}
