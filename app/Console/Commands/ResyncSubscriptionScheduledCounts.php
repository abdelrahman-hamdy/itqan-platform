<?php

namespace App\Console\Commands;

use App\Enums\PurchaseSource;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resync per-cycle session counters and surface anomalies.
 *
 * Recomputes the following from actual session rows, scoped to the current
 * cycle window for each subscription:
 *  - subscription.total_sessions_scheduled
 *  - cycle.sessions_used / sessions_completed (compared against actual
 *    completed-row count; deltas surfaced for admin review)
 *
 * Also surfaces subscription cycles still carrying nonzero `carryover_sessions`
 * so the no-carryover policy unwind can be reviewed.
 *
 * Usage:
 *   php artisan subscriptions:resync-scheduled-counts            # dry-run (default)
 *   php artisan subscriptions:resync-scheduled-counts --apply    # write fixes
 *   php artisan subscriptions:resync-scheduled-counts --type=quran
 *   php artisan subscriptions:resync-scheduled-counts --sub=333
 */
class ResyncSubscriptionScheduledCounts extends Command
{
    protected $signature = 'subscriptions:resync-scheduled-counts
                            {--apply : Persist updates (default is dry-run)}
                            {--type=both : "quran", "academic", or "both"}
                            {--sub= : Only audit one subscription id (debug)}
                            {--carryover-unwind : Also unwind nonzero carryover_sessions on existing cycles}';

    protected $description = 'Recompute per-cycle session counters and surface counter anomalies for admin review.';

    private const REC_COUNTER_LEAK_INCREMENT = 'COUNTER_LEAK_INCREMENT';

    private const REC_POSSIBLE_ADMIN_PRESET = 'POSSIBLE_ADMIN_PRESET_NO_CHANGE';

    private const REC_FORCE_DELETE_LEAK_DECREMENT = 'FORCE_DELETE_LEAK_DECREMENT';

    protected array $deltas = [];

    protected array $anomalies = [];

    protected int $touched = 0;

    /**
     * Carryover cycles preloaded once per subscription type, keyed by subscribable_id.
     * Prevents an N+1 SELECT against `subscription_cycles` inside the chunked loop
     * when --carryover-unwind is enabled.
     *
     * @var array<class-string, array<int, \Illuminate\Support\Collection>>
     */
    protected array $carryoverCyclesByType = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $type = $this->option('type') ?? 'both';
        $subFilter = $this->option('sub');
        $unwindCarryover = (bool) $this->option('carryover-unwind');

        $this->newLine();
        $this->info('SUBSCRIPTION SCHEDULED-COUNT RESYNC');
        $this->info('Mode: '.($apply ? 'APPLY (writes will happen)' : 'DRY-RUN (no writes)'));
        $this->info('Type filter: '.$type);
        if ($subFilter) {
            $this->info('Subscription filter: '.$subFilter);
        }
        if ($unwindCarryover) {
            $this->info('Carryover unwind: ENABLED');
        }
        $this->newLine();

        if (in_array($type, ['quran', 'both'], true)) {
            $this->processQuranSubscriptions($apply, $subFilter, $unwindCarryover);
        }
        if (in_array($type, ['academic', 'both'], true)) {
            $this->processAcademicSubscriptions($apply, $subFilter, $unwindCarryover);
        }

        $this->displaySummary();

        return 0;
    }

    protected function processQuranSubscriptions(bool $apply, ?string $subFilter, bool $unwindCarryover): void
    {
        $this->processSubscriptions(
            QuranSubscription::query(),
            QuranSession::class,
            'quran_subscription_id',
            $apply,
            $subFilter,
            $unwindCarryover,
        );
    }

    protected function processAcademicSubscriptions(bool $apply, ?string $subFilter, bool $unwindCarryover): void
    {
        $this->processSubscriptions(
            AcademicSubscription::query(),
            AcademicSession::class,
            'academic_subscription_id',
            $apply,
            $subFilter,
            $unwindCarryover,
        );
    }

    protected function processSubscriptions(
        $query,
        string $sessionModel,
        string $fkColumn,
        bool $apply,
        ?string $subFilter,
        bool $unwindCarryover,
    ): void {
        if ($subFilter) {
            $query->where('id', $subFilter);
        }

        if ($unwindCarryover) {
            $this->preloadCarryoverCycles($query->getModel());
        }

        $query->with('currentCycle')->chunkById(100, function ($subscriptions) use ($sessionModel, $fkColumn, $apply, $unwindCarryover) {
            foreach ($subscriptions as $sub) {
                $this->auditSubscription($sub, $sessionModel, $fkColumn, $apply, $unwindCarryover);
            }
        });
    }

    /**
     * Load all nonzero-carryover cycles for this subscription type in one query and
     * key them by subscribable_id so per-subscription lookups become in-memory.
     */
    protected function preloadCarryoverCycles(BaseSubscription $modelSample): void
    {
        $morphClass = $modelSample->getMorphClass();

        if (isset($this->carryoverCyclesByType[$morphClass])) {
            return;
        }

        $this->carryoverCyclesByType[$morphClass] = SubscriptionCycle::query()
            ->where('subscribable_type', $morphClass)
            ->where('carryover_sessions', '>', 0)
            ->get()
            ->groupBy('subscribable_id')
            ->all();
    }

    protected function auditSubscription(
        BaseSubscription $sub,
        string $sessionModel,
        string $fkColumn,
        bool $apply,
        bool $unwindCarryover,
    ): void {
        try {
            $cycleStart = $sub->starts_at;

            // 1. Recompute total_sessions_scheduled (cycle-scoped non-cancelled count)
            $scheduledQuery = $sessionModel::query()
                ->where($fkColumn, $sub->id)
                ->whereNotIn('status', [SessionStatus::CANCELLED]);
            if ($cycleStart) {
                $scheduledQuery->where('scheduled_at', '>=', $cycleStart);
            }
            $newScheduledCount = $scheduledQuery->count();
            $oldScheduledCount = (int) ($sub->total_sessions_scheduled ?? 0);

            if ($newScheduledCount !== $oldScheduledCount) {
                $this->deltas[] = [
                    'sub_id' => $sub->id,
                    'sub_type' => class_basename($sub),
                    'field' => 'total_sessions_scheduled',
                    'from' => $oldScheduledCount,
                    'to' => $newScheduledCount,
                    'reason' => 'cycle-scoped recompute',
                ];

                if ($apply) {
                    $sub->updateQuietly(['total_sessions_scheduled' => $newScheduledCount]);
                    $this->touched++;
                }
            }

            // 2. Compare cycle.sessions_used vs actual_completed_in_window
            $cycle = $sub->currentCycle;
            if ($cycle) {
                $countedRowsQuery = $sessionModel::query()
                    ->where($fkColumn, $sub->id)
                    ->where('subscription_counted', true);
                if ($cycleStart) {
                    $countedRowsQuery->where('scheduled_at', '>=', $cycleStart);
                }
                if ($sub->ends_at) {
                    $countedRowsQuery->where('scheduled_at', '<=', $sub->ends_at);
                }
                $actualCounted = $countedRowsQuery->count();
                $cycleUsed = (int) ($cycle->sessions_used ?? 0);
                $delta = $actualCounted - $cycleUsed;

                if ($delta !== 0) {
                    $purchaseSource = $sub->purchase_source?->value ?? 'unknown';
                    $payment = $sub->payments()->first();
                    $paymentNotes = $payment?->notes ? mb_substr($payment->notes, 0, 60) : null;

                    if ($delta > 0) {
                        $recommendation = self::REC_COUNTER_LEAK_INCREMENT;
                    } else {
                        $recommendation = $sub->purchase_source === PurchaseSource::ADMIN
                            ? self::REC_POSSIBLE_ADMIN_PRESET
                            : self::REC_FORCE_DELETE_LEAK_DECREMENT;
                    }

                    $this->anomalies[] = [
                        'sub_id' => $sub->id,
                        'sub_type' => class_basename($sub),
                        'cycle_id' => $cycle->id,
                        'cycle_number' => $cycle->cycle_number,
                        'purchase_source' => $purchaseSource,
                        'payment_notes' => $paymentNotes,
                        'cycle_used' => $cycleUsed,
                        'actual_counted' => $actualCounted,
                        'delta' => $delta,
                        'recommendation' => $recommendation,
                    ];
                }
            }

            // 3. Carryover unwind (Phase 0 data fix)
            if ($unwindCarryover) {
                $this->unwindCarryoverForSubscription($sub, $apply);
            }
        } catch (Throwable $e) {
            $this->error("Audit failed for sub {$sub->id}: {$e->getMessage()}");
            Log::error('ResyncSubscriptionScheduledCounts: sub audit failed', [
                'subscription_id' => $sub->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function unwindCarryoverForSubscription(BaseSubscription $sub, bool $apply): void
    {
        $cycles = $this->carryoverCyclesByType[$sub->getMorphClass()][$sub->id] ?? [];

        foreach ($cycles as $cycle) {
            $packageQuota = $cycle->total_sessions - $cycle->carryover_sessions;
            $this->deltas[] = [
                'sub_id' => $sub->id,
                'sub_type' => class_basename($sub),
                'field' => "cycle[{$cycle->id}].total_sessions+carryover_sessions",
                'from' => "{$cycle->total_sessions} (carryover={$cycle->carryover_sessions})",
                'to' => "{$packageQuota} (carryover=0)",
                'reason' => 'no-carryover policy unwind',
            ];

            if ($apply) {
                $cycle->update([
                    'total_sessions' => $packageQuota,
                    'carryover_sessions' => 0,
                ]);

                // If this is the current cycle, sync subscription counters too
                if ($sub->current_cycle_id === $cycle->id) {
                    $newRemaining = max(0, $packageQuota - (int) $sub->sessions_used);
                    $sub->updateQuietly([
                        'total_sessions' => $packageQuota,
                        'sessions_remaining' => $newRemaining,
                    ]);
                }
                $this->touched++;
            }
        }
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('===================== SUMMARY =====================');
        $this->info('total_sessions_scheduled / carryover deltas: '.count($this->deltas));
        $this->info('Counter anomalies (cycle.sessions_used vs actual rows): '.count($this->anomalies));
        $this->info('Rows updated: '.$this->touched.($this->option('apply') ? '' : ' (dry-run, no writes)'));
        $this->newLine();

        if (! empty($this->deltas)) {
            $this->info('--- Deltas (showing first 50) ---');
            $rows = array_slice($this->deltas, 0, 50);
            $this->table(
                ['Sub', 'Type', 'Field', 'From', 'To', 'Reason'],
                array_map(fn ($d) => [$d['sub_id'], $d['sub_type'], $d['field'], (string) $d['from'], (string) $d['to'], $d['reason']], $rows),
            );
        }

        if (! empty($this->anomalies)) {
            $this->newLine();
            $this->warn('--- Counter Anomalies (showing first 50; admin review required) ---');
            $rows = array_slice($this->anomalies, 0, 50);
            $this->table(
                ['Sub', 'Type', 'Cycle', 'Source', 'Payment Notes', 'Cycle Used', 'Actual Counted', 'Delta', 'Recommendation'],
                array_map(fn ($a) => [
                    $a['sub_id'],
                    $a['sub_type'],
                    "{$a['cycle_id']}#{$a['cycle_number']}",
                    $a['purchase_source'],
                    (string) ($a['payment_notes'] ?? ''),
                    $a['cycle_used'],
                    $a['actual_counted'],
                    ($a['delta'] > 0 ? '+' : '').$a['delta'],
                    $a['recommendation'],
                ], $rows),
            );
        }

        $reportPath = storage_path('logs/resync-scheduled-counts-'.now()->format('Y-m-d_His').'.json');
        file_put_contents($reportPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'mode' => $this->option('apply') ? 'apply' : 'dry-run',
            'deltas' => $this->deltas,
            'anomalies' => $this->anomalies,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Full report written to: '.$reportPath);
    }
}
