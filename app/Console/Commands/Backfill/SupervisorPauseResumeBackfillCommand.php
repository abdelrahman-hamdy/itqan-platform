<?php

namespace App\Console\Commands\Backfill;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Bug #1 backfill — reconcile the data corruption left behind by the raw
 * supervisor pause/resume update path. Three categories:
 *
 *   A: status=PAUSED AND paused_at IS NULL
 *      → stamp paused_at = updated_at, pause_reason = 'manual_legacy_unstamped'
 *
 *   B: status=ACTIVE AND paused_at IS NOT NULL
 *      → clear paused_at (the resume happened but never cleared the column).
 *      Note: lost ends_at-compensation is NOT auto-reconstructed — flagged for ops.
 *
 *   C: status=SUSPENDED session whose subscription is now ACTIVE and whose
 *      scheduled_at falls inside the current window
 *      → flip status back to SCHEDULED so the student sees it on their calendar.
 *
 * Each row mutated writes one BackfillLog entry, so --rollback can undo.
 *
 *   php artisan subscriptions:backfill-supervisor-pause-resume --dry-run
 *   php artisan subscriptions:backfill-supervisor-pause-resume --apply
 *   php artisan subscriptions:backfill-supervisor-pause-resume --rollback
 */
class SupervisorPauseResumeBackfillCommand extends BaseBackfillCommand
{
    protected $signature = 'subscriptions:backfill-supervisor-pause-resume
                            {--dry-run : Print categories + counts without mutating}
                            {--apply : Apply categories A, B, C}
                            {--rollback : Undo a prior --apply run}';

    protected $description = 'Bug #1: reconcile rows orphaned by the raw supervisor pause/resume path';

    protected const BUG_ID = 'bug_1';

    protected const COMMAND_NAME = 'subscriptions:backfill-supervisor-pause-resume';

    private const LEGACY_REASON = 'manual_legacy_unstamped';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollbackLogged();
        }

        $dryRun = (bool) $this->option('dry-run') || ! $this->option('apply');

        if ($dryRun) {
            $this->warn('Dry-run mode (default). Pass --apply to mutate.');
        }

        $this->categoryA($dryRun);
        $this->categoryB($dryRun);
        $this->categoryC($dryRun);

        return self::SUCCESS;
    }

    private function categoryA(bool $dryRun): void
    {
        foreach ([QuranSubscription::class, AcademicSubscription::class] as $modelClass) {
            $rows = $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::PAUSED)
                ->whereNull('paused_at')
                ->get();

            $this->info(sprintf(
                'Category A [%s]: %d row(s) with status=PAUSED AND paused_at IS NULL.',
                class_basename($modelClass),
                $rows->count(),
            ));

            if ($dryRun || $rows->isEmpty()) {
                continue;
            }

            foreach ($rows as $sub) {
                DB::transaction(function () use ($sub) {
                    $stamp = $sub->updated_at ?: now();

                    $this->logChange($sub, 'paused_at', null, $stamp);
                    $this->logChange($sub, 'pause_reason', $sub->pause_reason, self::LEGACY_REASON);

                    $sub->forceFill([
                        'paused_at' => $stamp,
                        'pause_reason' => self::LEGACY_REASON,
                    ])->saveQuietly();
                });
            }
        }
    }

    private function categoryB(bool $dryRun): void
    {
        foreach ([QuranSubscription::class, AcademicSubscription::class] as $modelClass) {
            $rows = $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereNotNull('paused_at')
                ->get();

            $this->info(sprintf(
                'Category B [%s]: %d row(s) with status=ACTIVE AND paused_at IS NOT NULL.',
                class_basename($modelClass),
                $rows->count(),
            ));

            if ($dryRun || $rows->isEmpty()) {
                continue;
            }

            foreach ($rows as $sub) {
                DB::transaction(function () use ($sub) {
                    $this->logChange($sub, 'paused_at', $sub->paused_at, null);

                    $sub->forceFill(['paused_at' => null])->saveQuietly();
                });
            }
        }
    }

    private function categoryC(bool $dryRun): void
    {
        $sessionMap = [
            QuranSession::class => 'quran_subscription_id',
            AcademicSession::class => 'academic_subscription_id',
        ];

        $subModelByForeign = [
            'quran_subscription_id' => QuranSubscription::class,
            'academic_subscription_id' => AcademicSubscription::class,
        ];

        foreach ($sessionMap as $sessionClass => $foreignKey) {
            $modelClass = $subModelByForeign[$foreignKey];
            $candidates = $sessionClass::withoutGlobalScopes()
                ->where('status', SessionStatus::SUSPENDED)
                ->whereNotNull($foreignKey)
                ->get();

            // Pre-load every parent subscription referenced by the candidates
            // in one query, keyed by id, so the per-session in-window check
            // below avoids the N+1 `find()` per row that the original loop did.
            $subscriptionIds = $candidates->pluck($foreignKey)->filter()->unique()->values();
            $subsById = $subscriptionIds->isEmpty()
                ? collect()
                : $modelClass::withoutGlobalScopes()
                    ->whereIn('id', $subscriptionIds)
                    ->get()
                    ->keyBy('id');

            $strandedSessions = collect();
            foreach ($candidates as $session) {
                $sub = $subsById->get($session->{$foreignKey});
                if (! $sub || $sub->status !== SessionSubscriptionStatus::ACTIVE) {
                    continue;
                }
                if ($sub->starts_at && $session->scheduled_at?->lt($sub->starts_at)) {
                    continue;
                }
                if ($sub->ends_at && $session->scheduled_at?->gt($sub->ends_at)) {
                    continue;
                }
                $strandedSessions->push($session);
            }

            $this->info(sprintf(
                'Category C [%s]: %d SUSPENDED session(s) under ACTIVE sub in-window.',
                class_basename($sessionClass),
                $strandedSessions->count(),
            ));

            if ($dryRun || $strandedSessions->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($sessionClass, $strandedSessions) {
                foreach ($strandedSessions as $session) {
                    $this->logChange(
                        $session,
                        'status',
                        SessionStatus::SUSPENDED->value,
                        SessionStatus::SCHEDULED->value,
                    );

                    $sessionClass::withoutGlobalScopes()
                        ->where('id', $session->id)
                        ->update(['status' => SessionStatus::SCHEDULED->value]);
                }
            });
        }
    }
}
