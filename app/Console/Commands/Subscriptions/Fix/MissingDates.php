<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\BaseSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repairs INV-A6 — subscriptions with NULL `starts_at` and/or `ends_at`
 * despite `sessions_used > 0`. Surfaced on 12 historical quran subs whose
 * cycle is also NULL-dated, so the reconciler's mirrorFromCycle can't help.
 *
 * Strategy:
 *   - `starts_at`  ← sub.created_at  (the earliest meaningful anchor we
 *                                     have; sessions came later)
 *   - `ends_at`    ← max(starts_at + 30 days, paused_at, cancelled_at, now)
 *                  (collapse to the latest meaningful moment so the row
 *                  doesn't pretend its window is still in the future)
 *
 * Cycle gets the same dates (mirror). BackfillLog records both columns.
 *
 * Dry-run by default. No invariant checker call — these rows are already
 * historical and may carry other unrelated violations we don't want to
 * trip.
 */
class MissingDates extends Command
{
    protected $signature = 'subscriptions:fix-missing-dates
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of subs processed}';

    protected $description = 'Backfill NULL starts_at/ends_at on used subscriptions (INV-A6).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $classes = [QuranSubscription::class, AcademicSubscription::class];

        $candidates = collect();
        foreach ($classes as $class) {
            $candidates = $candidates->merge(
                $class::withoutGlobalScopes()
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhereNull('ends_at');
                    })
                    ->where('sessions_used', '>', 0)
                    ->get()
            );
        }

        $this->info(sprintf('Candidates: %d sub(s)', $candidates->count()));
        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }

        if ($limit !== null && $candidates->count() > $limit) {
            $candidates = $candidates->take($limit);
        }

        $bar = $this->output->createProgressBar($candidates->count());
        $bar->start();

        $touched = 0;
        $errors = 0;

        foreach ($candidates as $sub) {
            try {
                if ($apply) {
                    DB::transaction(fn () => $this->repairSub($sub));
                } else {
                    [$newStarts, $newEnds] = $this->deriveDates($sub);
                    $this->line(sprintf("\n  sub #%d (%s): starts=%s ends=%s",
                        $sub->id, $sub->getMorphClass(),
                        $newStarts?->toDateTimeString() ?? 'NULL',
                        $newEnds?->toDateTimeString() ?? 'NULL',
                    ));
                }
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\nsub #%d: %s", $sub->id, $e->getMessage()));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s %d sub(s) processed; %d error(s).',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows allow per-sub rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function deriveDates(BaseSubscription $sub): array
    {
        $created = $sub->created_at;
        $starts = $sub->starts_at ?? $created;

        if ($starts === null) {
            return [null, null];
        }

        $candidates = [$starts->copy()->addDays(30)];
        if ($sub->paused_at instanceof Carbon) {
            $candidates[] = $sub->paused_at;
        }
        if ($sub->cancelled_at instanceof Carbon) {
            $candidates[] = $sub->cancelled_at;
        }
        $candidates[] = Carbon::now();

        $ends = $sub->ends_at ?? collect($candidates)->max();

        return [$starts, $ends];
    }

    private function repairSub(BaseSubscription $sub): void
    {
        [$starts, $ends] = $this->deriveDates($sub);

        if ($starts === null || $ends === null) {
            throw new \RuntimeException('cannot derive starts_at / ends_at (no created_at)');
        }

        $parentTable = $sub->getTable();
        $originalStarts = $sub->starts_at?->toDateTimeString();
        $originalEnds = $sub->ends_at?->toDateTimeString();

        BackfillLog::create([
            'bug_id' => 'cleanup-missing-dates',
            'table_name' => $parentTable,
            'row_id' => $sub->id,
            'column_name' => 'starts_at+ends_at',
            'original_value' => json_encode(['starts_at' => $originalStarts, 'ends_at' => $originalEnds]),
            'new_value' => json_encode(['starts_at' => $starts->toDateTimeString(), 'ends_at' => $ends->toDateTimeString()]),
            'backfill_command' => 'subscriptions:fix-missing-dates',
            'ran_at' => Carbon::now(),
        ]);

        // Bypass the SubscriptionRowGuard observer (which blocks direct
        // writes to derived fields) by flipping the reconciling flag.
        $sub->reconciling = true;
        try {
            DB::table($parentTable)
                ->where('id', $sub->id)
                ->update([
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'updated_at' => Carbon::now(),
                ]);
        } finally {
            $sub->reconciling = false;
        }

        // Mirror to active cycle if it also has NULL dates.
        $cycle = SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->where('cycle_state', 'active')
            ->first();

        if ($cycle === null) {
            return;
        }

        if ($cycle->starts_at !== null && $cycle->ends_at !== null) {
            return;
        }

        BackfillLog::create([
            'bug_id' => 'cleanup-missing-dates',
            'table_name' => 'subscription_cycles',
            'row_id' => $cycle->id,
            'column_name' => 'starts_at+ends_at',
            'original_value' => json_encode([
                'starts_at' => $cycle->starts_at?->toDateTimeString(),
                'ends_at' => $cycle->ends_at?->toDateTimeString(),
            ]),
            'new_value' => json_encode([
                'starts_at' => $starts->toDateTimeString(),
                'ends_at' => $ends->toDateTimeString(),
            ]),
            'backfill_command' => 'subscriptions:fix-missing-dates',
            'ran_at' => Carbon::now(),
        ]);

        DB::table('subscription_cycles')
            ->where('id', $cycle->id)
            ->update([
                'starts_at' => $starts,
                'ends_at' => $ends,
                'updated_at' => Carbon::now(),
            ]);
    }
}
