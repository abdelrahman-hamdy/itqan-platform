<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety-net command that finds completed/absent sessions where
 * subscription_counted = false and processes them.
 *
 * This catches sessions that were completed via paths that previously
 * didn't dispatch SessionCompletedEvent (e.g., LiveKit room_finished,
 * autoCompleteIfExpired) or where the queued listener failed.
 */
class ReconcileSubscriptionCounts extends Command
{
    protected $signature = 'subscriptions:reconcile-missed
                            {--dry-run : Show what would be reconciled without making changes}
                            {--minutes=10 : Only process sessions ended at least this many minutes ago}';

    protected $description = 'Safety net: count subscriptions for completed sessions that were missed';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $minutesAgo = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutesAgo);

        // Never reconcile sessions from before the cutoff — preserves the
        // historical counting state recorded under the previous rules.
        $matrixCutoff = config('business.attendance.matrix_cutoff_at');

        $reconciled = 0;
        $errors = 0;

        // No counts_for_teacher pre-filter: the per-student counts_for_subscription
        // flag is now the source of truth, and updateSubscriptionUsage() consults
        // it before decrementing.
        $quranQuery = QuranSession::where('status', SessionStatus::COMPLETED)
            ->where('subscription_counted', false)
            ->where('ended_at', '<', $cutoff);

        if ($matrixCutoff) {
            $quranQuery->where('scheduled_at', '>=', $matrixCutoff);
        }

        $quranSessions = $quranQuery->get();

        foreach ($quranSessions as $session) {
            if ($dryRun) {
                $this->info("Would reconcile QuranSession {$session->id} (status: {$session->status->value})");
                $reconciled++;

                continue;
            }

            try {
                $session->updateSubscriptionUsage();
                $reconciled++;

                Log::info('ReconcileSubscriptionCounts: QuranSession counted', [
                    'session_id' => $session->id,
                    'status' => $session->status->value,
                ]);
            } catch (\Exception $e) {
                $errors++;
                Log::warning('ReconcileSubscriptionCounts: Failed to count QuranSession', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Process Academic sessions with the same guards.
        $academicQuery = AcademicSession::where('status', SessionStatus::COMPLETED)
            ->where('subscription_counted', false)
            ->where('ended_at', '<', $cutoff);

        if ($matrixCutoff) {
            $academicQuery->where('scheduled_at', '>=', $matrixCutoff);
        }

        $academicSessions = $academicQuery->get();

        foreach ($academicSessions as $session) {
            if ($dryRun) {
                $this->info("Would reconcile AcademicSession {$session->id} (status: {$session->status->value})");
                $reconciled++;

                continue;
            }

            try {
                $session->updateSubscriptionUsage();
                $reconciled++;

                Log::info('ReconcileSubscriptionCounts: AcademicSession counted', [
                    'session_id' => $session->id,
                    'status' => $session->status->value,
                ]);
            } catch (\Exception $e) {
                $errors++;
                Log::warning('ReconcileSubscriptionCounts: Failed to count AcademicSession', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Reconciled: {$reconciled}, Errors: {$errors}");

        if ($reconciled > 0 || $errors > 0) {
            Log::info("ReconcileSubscriptionCounts: {$prefix}Reconciled: {$reconciled}, Errors: {$errors}");
        }

        return self::SUCCESS;
    }
}
