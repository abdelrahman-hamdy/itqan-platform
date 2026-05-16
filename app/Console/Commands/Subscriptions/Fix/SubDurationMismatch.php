<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repairs `quran_subscriptions.session_duration_minutes` for a known set of
 * subs whose sub-row denormalized field drifted away from their actual
 * package (`sub.package_id`). The package linkage is authoritative because
 * each cycle's payment was verified to match the current package's price
 * before this command was authored — see the per-sub payment-truth probe
 * dated 2026-05-16.
 *
 * Three cascading writes per sub:
 *   1. `quran_subscriptions.session_duration_minutes` ← package.session_duration_minutes
 *   2. `quran_individual_circles.default_duration_minutes` ← same (if a circle exists)
 *   3. `quran_sessions.duration_minutes` ← same, for every session in status
 *      ∈ {UNSCHEDULED, SCHEDULED, READY} (i.e. not ONGOING / COMPLETED /
 *      CANCELLED / SUSPENDED — those keep their historical duration).
 *
 * Direction notes:
 *   - 4 subs (599, 659, 660, 830) are UPGRADES — student paid for longer
 *     duration than the sub-row currently claims. Fix gives them the
 *     duration they paid for.
 *   - 4 subs (706, 793, 826, 836) are DOWNGRADES — student has been getting
 *     longer sessions than they paid for. Fix aligns with payment. Admin
 *     decided not to notify.
 *   - 5 subs (370, 372, 373, 374, 669) are CANCELLED / EXPIRED with no
 *     scheduled sessions left. Only the sub-row is updated; the cascade
 *     finds no sessions to touch.
 *
 * Each write produces a `BackfillLog` row keyed `bug_id='sub-duration-mismatch'`.
 * Per-row rollback via the JSON-encoded original values.
 *
 * Dry-run by default.
 */
class SubDurationMismatch extends Command
{
    protected $signature = 'subscriptions:fix-sub-duration-mismatch
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--subs= : Comma-separated sub ids (default: the 13 identified)}';

    protected $description = 'Repair sub.session_duration_minutes for the 13 known duration-drift subs and cascade to circle + scheduled sessions.';

    /**
     * The 13 subs identified by the 2026-05-16 audit. Order: 8 active first,
     * 5 historical last (cancelled/expired — only sub-row fix applies).
     */
    private const DEFAULT_SUBS = [599, 659, 660, 706, 793, 826, 830, 836, 370, 372, 373, 374, 669];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $subIds = $this->option('subs') !== null
            ? array_map('intval', explode(',', $this->option('subs')))
            : self::DEFAULT_SUBS;

        $this->info(sprintf('%s: %d sub(s) %s', $apply ? 'APPLYING' : 'DRY-RUN', count($subIds), implode(',', $subIds)));
        $this->line('');

        $totalSubsTouched = 0;
        $totalCirclesTouched = 0;
        $totalSessionsTouched = 0;
        $errors = 0;

        foreach ($subIds as $subId) {
            try {
                $result = $this->processSub($subId, $apply);
                if ($result === null) {
                    continue;
                }
                $totalSubsTouched += $result['sub_changed'] ? 1 : 0;
                $totalCirclesTouched += $result['circles_changed'];
                $totalSessionsTouched += $result['sessions_changed'];
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('sub #%d ERROR: %s', $subId, $e->getMessage()));
            }
        }

        $this->line('');
        $this->info(sprintf(
            '%s: subs=%d circles=%d sessions=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $totalSubsTouched,
            $totalCirclesTouched,
            $totalSessionsTouched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{sub_changed: bool, circles_changed: int, sessions_changed: int}|null
     */
    private function processSub(int $subId, bool $apply): ?array
    {
        $sub = QuranSubscription::withoutGlobalScopes()->with('package')->find($subId);
        if ($sub === null) {
            $this->warn(sprintf('sub #%d not found, skipping', $subId));
            return null;
        }
        if ($sub->package === null) {
            $this->warn(sprintf('sub #%d has no live package, skipping', $subId));
            return null;
        }

        $targetDuration = (int) $sub->package->session_duration_minutes;
        $currentSubDuration = $sub->session_duration_minutes !== null ? (int) $sub->session_duration_minutes : null;

        $studentName = $sub->student?->name ?? '?';
        $pkgName = $sub->package->name;
        $direction = match (true) {
            $currentSubDuration === null => 'NULL→'.$targetDuration,
            $currentSubDuration < $targetDuration => 'UPGRADE '.$currentSubDuration.'→'.$targetDuration,
            $currentSubDuration > $targetDuration => 'DOWNGRADE '.$currentSubDuration.'→'.$targetDuration,
            default => 'no-op',
        };

        $this->line(sprintf('  sub #%d %s [pkg #%d %s] %s', $subId, $studentName, $sub->package_id, $pkgName, $direction));

        if ($currentSubDuration === $targetDuration) {
            return ['sub_changed' => false, 'circles_changed' => 0, 'sessions_changed' => 0];
        }

        $subChanged = false;
        $circlesChanged = 0;
        $sessionsChanged = 0;

        if ($apply) {
            DB::transaction(function () use ($targetDuration, $currentSubDuration, $subId, &$subChanged, &$circlesChanged, &$sessionsChanged) {
                // 1. Update sub row.
                BackfillLog::create([
                    'bug_id' => 'sub-duration-mismatch',
                    'table_name' => 'quran_subscriptions',
                    'row_id' => $subId,
                    'column_name' => 'session_duration_minutes',
                    'original_value' => $currentSubDuration === null ? 'NULL' : (string) $currentSubDuration,
                    'new_value' => (string) $targetDuration,
                    'backfill_command' => 'subscriptions:fix-sub-duration-mismatch',
                    'ran_at' => Carbon::now(),
                ]);
                DB::table('quran_subscriptions')
                    ->where('id', $subId)
                    ->update(['session_duration_minutes' => $targetDuration, 'updated_at' => Carbon::now()]);
                $subChanged = true;

                // 2. Update linked circles' default_duration_minutes.
                $circles = QuranIndividualCircle::withoutGlobalScopes()
                    ->where('subscription_id', $subId)
                    ->get();
                foreach ($circles as $circle) {
                    $origCircleDur = (int) $circle->default_duration_minutes;
                    if ($origCircleDur === $targetDuration) {
                        continue;
                    }
                    BackfillLog::create([
                        'bug_id' => 'sub-duration-mismatch',
                        'table_name' => 'quran_individual_circles',
                        'row_id' => $circle->id,
                        'column_name' => 'default_duration_minutes',
                        'original_value' => (string) $origCircleDur,
                        'new_value' => (string) $targetDuration,
                        'backfill_command' => 'subscriptions:fix-sub-duration-mismatch',
                        'ran_at' => Carbon::now(),
                    ]);
                    DB::table('quran_individual_circles')
                        ->where('id', $circle->id)
                        ->update(['default_duration_minutes' => $targetDuration, 'updated_at' => Carbon::now()]);
                    $circlesChanged++;
                }

                // 3. Update SCHEDULED/UNSCHEDULED/READY quran_sessions for this sub.
                $sessions = QuranSession::withoutGlobalScopes()
                    ->where('quran_subscription_id', $subId)
                    ->whereIn('status', [
                        SessionStatus::SCHEDULED->value,
                        SessionStatus::UNSCHEDULED->value,
                        SessionStatus::READY->value,
                    ])
                    ->get();
                foreach ($sessions as $session) {
                    $origSessDur = (int) ($session->duration_minutes ?? 0);
                    if ($origSessDur === $targetDuration) {
                        continue;
                    }
                    BackfillLog::create([
                        'bug_id' => 'sub-duration-mismatch',
                        'table_name' => 'quran_sessions',
                        'row_id' => $session->id,
                        'column_name' => 'duration_minutes',
                        'original_value' => (string) $origSessDur,
                        'new_value' => (string) $targetDuration,
                        'backfill_command' => 'subscriptions:fix-sub-duration-mismatch',
                        'ran_at' => Carbon::now(),
                    ]);
                    DB::table('quran_sessions')
                        ->where('id', $session->id)
                        ->update(['duration_minutes' => $targetDuration, 'updated_at' => Carbon::now()]);
                    $sessionsChanged++;
                }
            });

            $this->line(sprintf('     APPLIED: sub✓ circles=%d sessions=%d', $circlesChanged, $sessionsChanged));
        } else {
            // Dry-run: count what WOULD change without writes.
            $circles = QuranIndividualCircle::withoutGlobalScopes()->where('subscription_id', $subId)->get();
            foreach ($circles as $circle) {
                if ((int) $circle->default_duration_minutes !== $targetDuration) {
                    $circlesChanged++;
                }
            }
            $sessionsChanged = QuranSession::withoutGlobalScopes()
                ->where('quran_subscription_id', $subId)
                ->whereIn('status', [
                    SessionStatus::SCHEDULED->value,
                    SessionStatus::UNSCHEDULED->value,
                    SessionStatus::READY->value,
                ])
                ->where(function ($q) use ($targetDuration) {
                    $q->whereNull('duration_minutes')->orWhere('duration_minutes', '!=', $targetDuration);
                })
                ->count();
            $subChanged = true;
            $this->line(sprintf('     WOULD CHANGE: sub✓ circles=%d sessions=%d', $circlesChanged, $sessionsChanged));
        }

        return ['sub_changed' => $subChanged, 'circles_changed' => $circlesChanged, 'sessions_changed' => $sessionsChanged];
    }
}
