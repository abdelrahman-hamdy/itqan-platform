<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Delete abandoned unpaid queued cycles older than the configured threshold,
 * along with their pending payment rows. Safety net for the renewal flow that
 * creates a queued cycle when a student starts a renewal but never completes
 * payment — without cleanup these block all future renewal attempts.
 *
 * Usage:
 *   php artisan subscriptions:cleanup-abandoned-queued
 *   php artisan subscriptions:cleanup-abandoned-queued --hours=24 --dry-run
 */
class CleanupAbandonedQueuedCycles extends Command
{
    protected $signature = 'subscriptions:cleanup-abandoned-queued
                            {--hours=24 : Age threshold in hours; cycles older than this are eligible}
                            {--dry-run : Print what would be deleted without making changes}';

    protected $description = 'Delete abandoned unpaid queued subscription cycles and their pending payments';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        $stale = SubscriptionCycle::query()
            ->where('cycle_state', SubscriptionCycle::STATE_QUEUED)
            ->where('payment_status', SubscriptionCycle::PAYMENT_PENDING)
            ->where('created_at', '<', $cutoff)
            ->get(['id', 'subscribable_type', 'subscribable_id', 'academy_id', 'payment_id', 'created_at']);

        $this->info(sprintf(
            'Found %d abandoned queued cycle(s) older than %d hour(s) (cutoff: %s).',
            $stale->count(),
            $hours,
            $cutoff->toIso8601String(),
        ));

        if ($stale->isEmpty()) {
            return self::SUCCESS;
        }

        $this->table(
            ['cycle_id', 'subscribable', 'academy_id', 'payment_id', 'created_at'],
            $stale->map(fn ($c) => [
                $c->id,
                "{$c->subscribable_type}#{$c->subscribable_id}",
                $c->academy_id,
                $c->payment_id ?? '—',
                $c->created_at?->toIso8601String(),
            ])->all(),
        );

        if ($dryRun) {
            $this->warn('Dry-run: no rows deleted.');

            return self::SUCCESS;
        }

        $deletedCycles = 0;
        $deletedPayments = 0;

        DB::transaction(function () use ($stale, &$deletedCycles, &$deletedPayments) {
            foreach ($stale as $cycle) {
                if ($cycle->payment_id) {
                    $deletedPayments += Payment::where('id', $cycle->payment_id)
                        ->where('status', PaymentStatus::PENDING)
                        ->delete();
                }
                $deletedCycles += SubscriptionCycle::destroy($cycle->id);
            }
        });

        Log::info('Cleanup of abandoned queued cycles', [
            'cycles_deleted' => $deletedCycles,
            'payments_deleted' => $deletedPayments,
            'cutoff_hours' => $hours,
        ]);

        $this->info("Deleted {$deletedCycles} cycle(s) and {$deletedPayments} pending payment row(s).");

        return self::SUCCESS;
    }
}
