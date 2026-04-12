<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * ConsolidateRenewalChains Command
 *
 * One-shot data migration that collapses legacy renewal chains (rows linked
 * via `previous_subscription_id`) into a single surviving subscription thread
 * with its history represented in the `subscription_cycles` table.
 *
 * For each chain:
 *   1. Detect orphan pending-no-date rows (created by the pre-refactor
 *      "renew with pending payment" flow, which is the exact pattern that
 *      produced subscription 905 in production). Merge grace metadata back
 *      to the parent, delete the orphan.
 *   2. Walk ancestors oldest → newest. For each ancestor:
 *      - Snapshot it into a subscription_cycles row (cycle_state = archived)
 *      - Repoint sessions / payments / circles / lessons to the surviving row
 *      - Delete the ancestor row
 *   3. Snapshot the surviving row's current state as an active cycle, set
 *      current_cycle_id.
 *   4. Null out previous_subscription_id on the surviving row.
 *
 * Refuses to run in --force mode without a --backup-path pointing at an
 * existing .sql backup file. Dry-run mode by default.
 */
class ConsolidateRenewalChains extends Command
{
    protected $signature = 'subscriptions:consolidate-renewal-chains
                            {--dry-run : Preview changes without making them (default)}
                            {--force : Actually mutate data (requires --backup-path)}
                            {--backup-path= : Path to an existing pre-mutation .sql backup}';

    protected $description = 'Collapse legacy renewal chains into cycle-based history (one-shot migration)';

    public function handle(): int
    {
        $dryRun = ! $this->option('force');
        $backupPath = $this->option('backup-path');

        if (! $dryRun) {
            if (empty($backupPath) || ! File::exists($backupPath)) {
                $this->error('--force requires --backup-path to point at an existing .sql backup file.');
                $this->line('Create one first:');
                $this->line('  mysqldump --single-transaction itqan_prod > /var/backups/pre-consolidation-$(date +%F).sql');

                return Command::FAILURE;
            }
        }

        $report = [
            'chains_processed' => 0,
            'orphans_deleted' => 0,
            'ancestors_collapsed' => 0,
            'sessions_repointed' => 0,
            'payments_repointed' => 0,
            'circles_repointed' => 0,
            'errors' => 0,
        ];

        $types = [
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
        ];

        foreach ($types as $type => $modelClass) {
            $this->info("Processing {$type} chains...");

            // Find every root (no previous_subscription_id) that has descendants
            $roots = $modelClass::withoutGlobalScopes()
                ->whereNull('previous_subscription_id')
                ->whereIn('id', function ($q) use ($modelClass) {
                    $q->select('previous_subscription_id')
                        ->from((new $modelClass)->getTable())
                        ->whereNotNull('previous_subscription_id');
                })
                ->get();

            foreach ($roots as $root) {
                try {
                    $this->processChain($root, $modelClass, $dryRun, $report);
                    $report['chains_processed']++;
                } catch (\Exception $e) {
                    $report['errors']++;
                    $this->error("  Chain starting at #{$root->id}: {$e->getMessage()}");
                    Log::error('Chain consolidation failed', [
                        'root_id' => $root->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info($dryRun ? '=== DRY RUN REPORT ===' : '=== CONSOLIDATION REPORT ===');
        $this->table(
            ['Metric', 'Count'],
            collect($report)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($dryRun) {
            $this->warn('Dry run — no changes made. Re-run with --force --backup-path=... to mutate data.');
        }

        return $report['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Walk the chain from the root and collapse it into the newest surviving row.
     */
    private function processChain($root, string $modelClass, bool $dryRun, array &$report): void
    {
        // Collect full chain oldest → newest
        $chain = [$root];
        $node = $root;
        while (true) {
            $child = $modelClass::withoutGlobalScopes()
                ->where('previous_subscription_id', $node->id)
                ->first();
            if (! $child) {
                break;
            }
            $chain[] = $child;
            $node = $child;
        }

        if (count($chain) < 2) {
            return; // Nothing to collapse
        }

        $newest = end($chain);
        $ancestors = array_slice($chain, 0, -1);

        // Orphan pending-no-date check on the newest (pattern: sub 905)
        $isOrphan = $newest->status === SessionSubscriptionStatus::PENDING
            && $newest->payment_status === SubscriptionPaymentStatus::PENDING
            && $newest->starts_at === null
            && $newest->ends_at === null;

        if ($isOrphan && count($chain) >= 2) {
            // Fold the orphan back into its immediate parent
            $parent = $chain[count($chain) - 2];

            $this->line("  [chain at #{$root->id}] Orphan #{$newest->id} → folding into parent #{$parent->id}");

            if (! $dryRun) {
                DB::transaction(function () use ($parent, $newest, &$report) {
                    $this->mergeOrphanMetadataIntoParent($parent, $newest);

                    // Cancel orphan's pending payments
                    $newest->payments()->where('status', 'pending')->update(['status' => 'cancelled']);

                    $newest->delete();
                    $report['orphans_deleted']++;
                });
            } else {
                $report['orphans_deleted']++;
            }

            // The newest-surviving is now the parent; continue collapsing toward it
            $newest = $parent;
            array_pop($ancestors); // remove the parent from ancestors
        }

        // Collapse remaining ancestors into the newest
        foreach ($ancestors as $ancestor) {
            if ($dryRun) {
                $this->line("  [chain at #{$root->id}] Would archive ancestor #{$ancestor->id} → surviving #{$newest->id}");
                $report['ancestors_collapsed']++;

                continue;
            }

            DB::transaction(function () use ($ancestor, $newest, &$report) {
                // 1. Snapshot as archived cycle
                $this->snapshotAsCycle($ancestor, $newest, SubscriptionCycle::STATE_ARCHIVED);

                // 2. Repoint sessions
                $sessionCount = 0;
                if ($newest instanceof QuranSubscription) {
                    $sessionCount = \App\Models\QuranSession::where('quran_subscription_id', $ancestor->id)
                        ->update(['quran_subscription_id' => $newest->id]);
                }
                if ($newest instanceof AcademicSubscription) {
                    $sessionCount = \App\Models\AcademicSession::where('academic_subscription_id', $ancestor->id)
                        ->update(['academic_subscription_id' => $newest->id]);
                }
                $report['sessions_repointed'] += $sessionCount;

                // 3. Repoint payments (polymorphic)
                $paymentCount = \App\Models\Payment::where('payable_type', $ancestor::class)
                    ->where('payable_id', $ancestor->id)
                    ->update(['payable_id' => $newest->id]);
                $report['payments_repointed'] += $paymentCount;

                // 4. Repoint circles (legacy FK)
                if ($newest instanceof QuranSubscription) {
                    $circleCount = \App\Models\QuranIndividualCircle::where('subscription_id', $ancestor->id)
                        ->update(['subscription_id' => $newest->id]);
                    $report['circles_repointed'] += $circleCount;
                }

                // 5. Repoint academic lesson
                if ($newest instanceof AcademicSubscription) {
                    \App\Models\AcademicIndividualLesson::where('academic_subscription_id', $ancestor->id)
                        ->update(['academic_subscription_id' => $newest->id]);
                }

                // 6. Delete the ancestor
                $ancestor->delete();
                $report['ancestors_collapsed']++;
            });
        }

        // Snapshot the newest as the active cycle and set current_cycle_id
        if (! $dryRun) {
            DB::transaction(function () use ($newest) {
                // Enforce "at most one active cycle per subscription" — archive any
                // stray actives before materializing the surviving one.
                $newest->cycles()
                    ->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)
                    ->update([
                        'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
                        'archived_at' => now(),
                    ]);

                $activeCycle = $this->snapshotAsCycle($newest, $newest, SubscriptionCycle::STATE_ACTIVE);

                $cycleCount = $newest->cycles()->count();

                $newest->update([
                    'previous_subscription_id' => null,
                    'current_cycle_id' => $activeCycle->id,
                    'cycle_count' => max(1, $cycleCount),
                ]);
            });
        }
    }

    /**
     * Merge an orphan's grace_period_ends_at and extensions log into its parent's metadata.
     */
    private function mergeOrphanMetadataIntoParent($parent, $orphan): void
    {
        $parentMeta = $parent->metadata ?? [];
        $orphanMeta = $orphan->metadata ?? [];

        if (isset($orphanMeta['grace_period_ends_at'])) {
            $parentMeta['grace_period_ends_at'] = $orphanMeta['grace_period_ends_at'];
        }

        if (! empty($orphanMeta['extensions'])) {
            $parentMeta['extensions'] = array_merge(
                $parentMeta['extensions'] ?? [],
                $orphanMeta['extensions']
            );
        }

        $parent->update(['metadata' => $parentMeta ?: null]);
    }

    /**
     * Materialize a subscription's current column values as a SubscriptionCycle row.
     *
     * Used for:
     *   - archiving an ancestor before deletion (state = archived)
     *   - materializing the surviving subscription's current state (state = active)
     *
     * Delegates to `SubscriptionCycle::materializeFromSubscription()` so the
     * snapshot logic stays in one place (shared with SubscriptionRenewalService).
     */
    private function snapshotAsCycle($source, $owner, string $state): SubscriptionCycle
    {
        return SubscriptionCycle::materializeFromSubscription(
            $source,
            $owner,
            $state,
            [
                'metadata' => [
                    'collapsed_from_legacy_chain' => true,
                    'source_id' => $source->id,
                    'collapsed_at' => now()->toDateTimeString(),
                ],
            ]
        );
    }
}
