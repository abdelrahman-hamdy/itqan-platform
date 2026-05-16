<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\SessionConsumption;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup for quran sessions stuck in `scheduled` status past their
 * scheduled_at. Root cause was the 2026-04-20 cron lock-timeout incident +
 * the 24-hour processing window filter on UpdateSessionStatusesCommand;
 * sessions that drifted outside the window were never re-examined and have
 * been sitting stuck ever since.
 *
 * The forward-only protection (cron mutex now caps at 5 minutes) prevents
 * new stuck sessions from accumulating; this command cleans the historical
 * residue (464 rows on prod as of 2026-05-16).
 *
 * **Outcome:** all eligible stuck rows transition to `cancelled`.
 *
 * **6-gate pre-apply safety check per row** (skip + warn if ANY fails):
 *   1. status = 'scheduled' AND scheduled_at < now() - 5 minutes
 *   2. Zero `session_consumption` rows (any source, ignore reversed_at)
 *   3. Zero `meeting_attendances` rows
 *   4. `subscription_counted` legacy flag is NULL or false
 *   5. Zero `teacher_earnings` rows referencing this session
 *   6. No recent (<30d) `backfill_log` row touching cycle.sessions_used
 *      for this session's cycle (admin recently adjusted counters manually)
 *
 * BackfillLog per row for rollback. Dry-run by default.
 */
class StuckScheduledSessions extends Command
{
    protected $signature = 'subscriptions:fix-stuck-scheduled-sessions
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Cancel quran sessions stuck in `scheduled` status past their scheduled_at (historical residue from the 2026-04-20 cron lock-timeout).';

    private const BUG_ID = 'stuck-scheduled-2026-05-16';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $scanned = 0;
        $eligible = 0;
        $cancelled = 0;
        $skipped = [
            'consumption_exists' => 0,
            'attendance_exists' => 0,
            'legacy_counted' => 0,
            'earnings_exists' => 0,
            'recent_cycle_adjustment' => 0,
        ];
        $errors = 0;

        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        QuranSession::query()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '<', now()->subMinutes(5))
            ->orderBy('id')
            ->chunkById(200, function ($sessions) use ($apply, &$scanned, &$eligible, &$cancelled, &$skipped, &$errors) {
                foreach ($sessions as $s) {
                    $scanned++;
                    try {
                        $skipReason = $this->safetyCheck($s);
                        if ($skipReason !== null) {
                            $skipped[$skipReason]++;
                            continue;
                        }
                        $eligible++;

                        if (! $apply) {
                            continue;
                        }

                        DB::transaction(function () use ($s, &$cancelled) {
                            BackfillLog::create([
                                'bug_id' => self::BUG_ID,
                                'table_name' => 'quran_sessions',
                                'row_id' => $s->id,
                                'column_name' => 'status',
                                'original_value' => $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status,
                                'new_value' => SessionStatus::CANCELLED->value,
                                'backfill_command' => 'subscriptions:fix-stuck-scheduled-sessions',
                                'ran_at' => now(),
                            ]);

                            QuranSession::query()
                                ->whereKey($s->id)
                                ->update(['status' => SessionStatus::CANCELLED->value]);

                            $cancelled++;
                        });
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->warn(sprintf('session #%d ERROR: %s', $s->id, $e->getMessage()));
                    }
                }
            });

        $this->newLine();
        $this->info(sprintf(
            '%s: scanned=%d eligible=%d cancelled=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $scanned,
            $eligible,
            $cancelled,
            $errors,
        ));
        if (array_sum($skipped) > 0) {
            $this->newLine();
            $this->warn('Skipped rows (safety gates failed):');
            foreach ($skipped as $reason => $n) {
                if ($n > 0) {
                    $this->line(sprintf('  %s: %d', $reason, $n));
                }
            }
            $this->line('  → these rows need manual review before any cleanup.');
        }

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Returns the failing gate's slug, or null if all 6 gates pass.
     */
    private function safetyCheck(QuranSession $session): ?string
    {
        $sessionType = $session->getMorphClass();

        if (SessionConsumption::query()
            ->where('session_id', $session->id)
            ->where('session_type', $sessionType)
            ->exists()) {
            return 'consumption_exists';
        }

        if (MeetingAttendance::query()
            ->where('session_id', $session->id)
            ->where('session_type', $sessionType)
            ->exists()) {
            return 'attendance_exists';
        }

        if ($session->subscription_counted === true) {
            return 'legacy_counted';
        }

        if (TeacherEarning::query()
            ->where('session_id', $session->id)
            ->where('session_type', TeacherEarning::normalizeSessionType($sessionType))
            ->exists()) {
            return 'earnings_exists';
        }

        if ($session->subscription_cycle_id !== null) {
            $recentAdjust = BackfillLog::query()
                ->where('table_name', 'subscription_cycles')
                ->where('row_id', $session->subscription_cycle_id)
                ->where('column_name', 'LIKE', '%sessions_used%')
                ->where('ran_at', '>=', now()->subDays(30))
                ->exists();
            if ($recentAdjust) {
                return 'recent_cycle_adjustment';
            }
        }

        return null;
    }
}
