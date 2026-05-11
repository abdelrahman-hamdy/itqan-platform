<?php

namespace App\Console\Commands\Backfill;

use App\Models\SubscriptionCycle;

/**
 * Bug #16 audit — list resubscribe cycles whose starts_at appears stale
 * (offset to the cancelled sibling's end-date instead of "now"). Read-only.
 * Operator reviews per-case.
 *
 *   php artisan subscriptions:audit-skewed-resubscribe-cycles
 */
class AuditSkewedResubscribeCyclesCommand extends BaseAuditCommand
{
    protected $signature = 'subscriptions:audit-skewed-resubscribe-cycles
                            {--out= : Optional CSV path; defaults to storage/logs/bug16-audit-{timestamp}.csv}';

    protected $description = 'Bug #16: list resubscribe cycles with possibly-stale starts_at';

    public function handle(): int
    {
        $candidates = collect();

        SubscriptionCycle::withoutGlobalScopes()
            // Eager-load `subscribable` so the morphTo inside the foreach
            // doesn't trigger one query per cycle (audit can run against
            // months of corruption data).
            ->with('subscribable')
            ->where('cycle_number', '>', 1)
            ->whereNotNull('starts_at')
            ->chunkById(200, function ($cycles) use ($candidates) {
                foreach ($cycles as $cycle) {
                    $sub = $cycle->subscribable;
                    if (! $sub) {
                        continue;
                    }
                    // Flag the row if starts_at is more than 7 days before
                    // the subscription's cancelled_at or created_at — that's
                    // the smoking gun for "we minted a cycle with stale dates".
                    $reference = $sub->cancelled_at ?? $sub->created_at;
                    if (! $reference) {
                        continue;
                    }
                    if ($cycle->starts_at->lt($reference->copy()->subDays(7))) {
                        $candidates->push([
                            'cycle_id' => $cycle->id,
                            'subscribable' => sprintf('%s#%d', $cycle->subscribable_type, $cycle->subscribable_id),
                            'cycle_number' => $cycle->cycle_number,
                            'starts_at' => $cycle->starts_at?->toIso8601String(),
                            'ends_at' => $cycle->ends_at?->toIso8601String(),
                            'sub_created_at' => $sub->created_at?->toIso8601String(),
                            'sub_cancelled_at' => $sub->cancelled_at?->toIso8601String(),
                        ]);
                    }
                }
            });

        $this->info(sprintf('Found %d candidate(s) of skewed-cycle dates.', $candidates->count()));

        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }

        $path = $this->writeCsv(
            'bug16',
            ['cycle_id', 'subscribable', 'cycle_number', 'starts_at', 'ends_at', 'sub_created_at', 'sub_cancelled_at'],
            $candidates,
        );

        $this->info("CSV written to: $path");

        return self::SUCCESS;
    }
}
