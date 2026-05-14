<?php

namespace App\Console\Commands\Sessions;

use App\Console\Commands\Backfill\BaseBackfillCommand;
use App\Jobs\RebuildFutureSessionsForCycle;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Collection;

/**
 * Phase A.6 — one-time prod sweep for INV-E1.
 *
 * Walks every active subscription cycle on every academy (or scoped via
 * --academy=<id>) and dispatches `RebuildFutureSessionsForCycle` SYNCHRONOUSLY
 * — synchronous on purpose: it lets --dry-run actually preview the diff
 * before any writes hit the DB (the dry-run mode never reaches the save).
 *
 * Once this command exits clean, InvariantChecker should prove INV-E1 holds
 * across the whole tenant set. After A.6 ships, the renewal flow itself
 * dispatches the job inline (A.5 work), so this sweep should never need to
 * run again — it's the catch-up for sessions stamped before A.5 landed.
 *
 * Usage:
 *   php artisan sessions:rebuild-future-durations --dry-run
 *   php artisan sessions:rebuild-future-durations --dry-run --academy=42
 *   php artisan sessions:rebuild-future-durations              # applies
 *
 * NOTE: dispatchSync() runs the job inside this process so the dry-run log
 * stream is contiguous and the operator sees the diff right away. Bypassing
 * the queue is intentional for a backfill of this shape.
 */
class RebuildFutureDurationsCommand extends BaseBackfillCommand
{
    protected $signature = 'sessions:rebuild-future-durations
                            {--dry-run : Print what would change without mutating}
                            {--academy= : Restrict to a single academy id}';

    protected $description = 'INV-E1 sweep — align future scheduled session durations with their anchor cycle\'s package';

    protected const BUG_ID = 'SUBV2-SESSION-DURATION-REBUILD';

    protected const COMMAND_NAME = 'sessions:rebuild-future-durations';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $cycles = $this->collectActiveCycles($academyId);
        $this->info(sprintf(
            '%s mode — found %d active cycle(s)%s.',
            $isDryRun ? 'DRY-RUN' : 'APPLY',
            $cycles->count(),
            $academyId !== null ? " (academy={$academyId})" : '',
        ));

        if ($cycles->isEmpty()) {
            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->previewDiff($cycles);

            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($cycles as $cycle) {
            RebuildFutureSessionsForCycle::dispatchSync(
                cycleId: (int) $cycle->id,
                cycleType: (string) $cycle->subscribable_type,
            );
            $dispatched++;
        }

        $this->info(sprintf('Dispatched RebuildFutureSessionsForCycle for %d cycle(s). See subscriptions log channel for per-session details.', $dispatched));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, SubscriptionCycle>
     */
    private function collectActiveCycles(?int $academyId): Collection
    {
        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->where('cycle_state', SubscriptionCycle::STATE_ACTIVE);

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        // Only Quran + Academic carry the subscription_cycle_id column on their
        // session tables. Course cycles have no future-session pointers.
        $subscriptableMorphs = [
            (new QuranSubscription)->getMorphClass(),
            (new AcademicSubscription)->getMorphClass(),
        ];
        $query->whereIn('subscribable_type', $subscriptableMorphs);

        return $query->orderBy('id')->get();
    }

    /**
     * Dry-run preview — re-implements the job's read-only path so we can
     * print every (session, old, new) before any write happens.
     */
    private function previewDiff(Collection $cycles): void
    {
        $rows = [];
        $morphMap = [
            (new QuranSubscription)->getMorphClass() => [QuranSubscription::class, \App\Models\QuranSession::class],
            (new AcademicSubscription)->getMorphClass() => [AcademicSubscription::class, \App\Models\AcademicSession::class],
        ];

        foreach ($cycles as $cycle) {
            $mapped = $morphMap[$cycle->subscribable_type] ?? null;
            if ($mapped === null) {
                continue;
            }
            [$subClass, $sessionClass] = $mapped;

            $sub = $subClass::withoutGlobalScopes()->find($cycle->subscribable_id);
            if ($sub === null) {
                continue;
            }

            $package = $sub->package()->withoutGlobalScopes()->first();
            $newDuration = (int) ($package?->session_duration_minutes ?? 0);
            if ($newDuration <= 0) {
                continue;
            }

            $sessions = $sessionClass::query()
                ->withoutGlobalScopes()
                ->where('subscription_cycle_id', $cycle->id)
                ->whereIn('status', [
                    \App\Enums\SessionStatus::SCHEDULED->value,
                    \App\Enums\SessionStatus::READY->value,
                ])
                ->where('scheduled_at', '>', now())
                ->get(['id', 'duration_minutes', 'scheduled_at']);

            foreach ($sessions as $s) {
                $old = (int) ($s->duration_minutes ?? 0);
                if ($old === $newDuration) {
                    continue;
                }
                $rows[] = [
                    'cycle_id' => $cycle->id,
                    'academy_id' => $cycle->academy_id,
                    'session_class' => class_basename($sessionClass),
                    'session_id' => $s->id,
                    'scheduled_at' => optional($s->scheduled_at)->toIso8601String(),
                    'old' => $old,
                    'new' => $newDuration,
                ];
            }
        }

        if (empty($rows)) {
            $this->info('No future sessions need a duration rewrite. INV-E1 already holds.');

            return;
        }

        $this->table(
            ['cycle_id', 'academy_id', 'class', 'session_id', 'scheduled_at', 'old_min', 'new_min'],
            $rows,
        );
        $this->warn(sprintf('%d session(s) would be rewritten. Re-run without --dry-run to apply.', count($rows)));
    }
}
