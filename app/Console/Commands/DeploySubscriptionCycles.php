<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot deployment command for the cycle-based subscription model.
 *
 * Handles the full production migration:
 * 1. Backfill ALL existing subscriptions with their initial cycle row
 * 2. Clean up orphan renewal branches (cancelled/pending with 0 sessions)
 * 3. Consolidate remaining parent→child chains into the survivor
 * 4. Verify data integrity
 *
 * Run with --dry-run first (default) to preview changes, then --force to apply.
 *
 * Production workflow:
 *   mysqldump --single-transaction itqan_platform > /var/backups/pre-cycles.sql
 *   php artisan migrate --force
 *   php artisan subscriptions:deploy-cycles --dry-run
 *   php artisan subscriptions:deploy-cycles --force
 */
class DeploySubscriptionCycles extends Command
{
    protected $signature = 'subscriptions:deploy-cycles
                            {--dry-run : Preview changes without making them (default)}
                            {--force : Actually mutate data}
                            {--step= : Run only a specific step (backfill|cleanup|consolidate|verify)}';

    protected $description = 'Deploy the cycle-based subscription model (one-shot production migration)';

    private array $report = [
        'backfilled' => 0,
        'orphans_deleted' => 0,
        'orphan_payments_repointed' => 0,
        'chains_consolidated' => 0,
        'sessions_repointed' => 0,
        'payments_repointed' => 0,
        'circles_repointed' => 0,
        'errors' => 0,
    ];

    public function handle(): int
    {
        $dryRun = ! $this->option('force');
        $step = $this->option('step');

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no changes will be made.');
        } else {
            $this->info('FORCE MODE — changes will be committed.');
        }

        $this->newLine();

        $steps = $step ? [$step] : ['backfill', 'cleanup', 'consolidate', 'verify'];

        foreach ($steps as $s) {
            match ($s) {
                'backfill' => $this->stepBackfill($dryRun),
                'cleanup' => $this->stepCleanupOrphans($dryRun),
                'consolidate' => $this->stepConsolidate($dryRun),
                'verify' => $this->stepVerify(),
                default => $this->error("Unknown step: {$s}"),
            };
        }

        $this->newLine();
        $this->info('=== REPORT ===');
        $this->table(
            ['Metric', 'Count'],
            collect($this->report)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($dryRun) {
            $this->warn('Dry run complete — re-run with --force to apply.');
        }

        return $this->report['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // ========================================================================
    // STEP 1: Backfill all subscriptions with initial cycle
    // ========================================================================

    private function stepBackfill(bool $dryRun): void
    {
        $this->info('--- STEP 1: Backfill all subscriptions with initial cycle ---');

        foreach (['quran' => QuranSubscription::class, 'academic' => AcademicSubscription::class] as $type => $model) {
            $model::withoutGlobalScopes()
                ->whereNull('current_cycle_id')
                ->chunkById(100, function ($subs) use ($dryRun, $type) {
                    foreach ($subs as $sub) {
                        if ($dryRun) {
                            $this->line("  [DRY] Would backfill {$type} #{$sub->id} ({$sub->status->value})");
                            $this->report['backfilled']++;

                            continue;
                        }

                        try {
                            DB::transaction(function () use ($sub) {
                                $sub->ensureCurrentCycle();
                            });
                            $this->report['backfilled']++;
                        } catch (\Exception $e) {
                            $this->report['errors']++;
                            $this->error("  ERROR backfilling #{$sub->id}: {$e->getMessage()}");
                        }
                    }
                });
        }

        $this->info("  Backfilled: {$this->report['backfilled']}");
    }

    // ========================================================================
    // STEP 2: Clean up orphan renewal branches
    // ========================================================================

    private function stepCleanupOrphans(bool $dryRun): void
    {
        $this->info('--- STEP 2: Clean up orphan renewal branches ---');

        // Find all subscriptions that have previous_subscription_id set,
        // are cancelled/pending, and have ZERO sessions
        $orphans = QuranSubscription::withoutGlobalScopes()
            ->whereNotNull('previous_subscription_id')
            ->whereIn('status', [
                SessionSubscriptionStatus::CANCELLED->value,
                SessionSubscriptionStatus::PENDING->value,
            ])
            ->get()
            ->filter(function ($sub) {
                // Only delete if zero sessions attached
                return \App\Models\QuranSession::withoutGlobalScopes()
                    ->where('quran_subscription_id', $sub->id)
                    ->doesntExist();
            });

        foreach ($orphans as $orphan) {
            $parent = QuranSubscription::withoutGlobalScopes()->find($orphan->previous_subscription_id);
            $parentCode = $parent ? $parent->subscription_code : 'DELETED';

            if ($dryRun) {
                $this->line("  [DRY] Would delete orphan #{$orphan->id} ({$orphan->status->value}) child of #{$orphan->previous_subscription_id} ({$parentCode})");
                // Count payments to repoint
                $payCount = DB::table('payments')
                    ->where('payable_type', QuranSubscription::class)
                    ->where('payable_id', $orphan->id)
                    ->count();
                if ($payCount > 0) {
                    $this->line("    -> Would repoint {$payCount} payment(s) to parent #{$orphan->previous_subscription_id}");
                    $this->report['orphan_payments_repointed'] += $payCount;
                }
                // Merge grace metadata if present
                $orphanMeta = $orphan->metadata ?? [];
                if (isset($orphanMeta['grace_period_ends_at']) && $parent) {
                    $this->line('    -> Would merge grace_period_ends_at to parent');
                }
                $this->report['orphans_deleted']++;

                continue;
            }

            try {
                DB::transaction(function () use ($orphan, $parent) {
                    // Repoint payments from orphan to parent
                    $payCount = DB::table('payments')
                        ->where('payable_type', QuranSubscription::class)
                        ->where('payable_id', $orphan->id)
                        ->update(['payable_id' => $orphan->previous_subscription_id]);
                    $this->report['orphan_payments_repointed'] += $payCount;

                    // Merge grace metadata into parent if present
                    if ($parent) {
                        $orphanMeta = $orphan->metadata ?? [];
                        if (isset($orphanMeta['grace_period_ends_at']) || ! empty($orphanMeta['extensions'])) {
                            $parentMeta = $parent->metadata ?? [];
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
                    }

                    // Repoint any individual circles that point to the orphan
                    \App\Models\QuranIndividualCircle::where('subscription_id', $orphan->id)
                        ->update(['subscription_id' => $orphan->previous_subscription_id]);

                    // Delete the orphan (force delete to bypass soft deletes)
                    $orphan->forceDelete();
                    $this->report['orphans_deleted']++;
                });
            } catch (\Exception $e) {
                $this->report['errors']++;
                $this->error("  ERROR deleting orphan #{$orphan->id}: {$e->getMessage()}");
            }
        }

        $this->info("  Orphans deleted: {$this->report['orphans_deleted']}, payments repointed: {$this->report['orphan_payments_repointed']}");
    }

    // ========================================================================
    // STEP 3: Consolidate remaining parent→child chains
    // ========================================================================

    private function stepConsolidate(bool $dryRun): void
    {
        $this->info('--- STEP 3: Consolidate remaining renewal chains ---');

        // After cleanup, find remaining chains (root→active child)
        $children = QuranSubscription::withoutGlobalScopes()
            ->whereNotNull('previous_subscription_id')
            ->get();

        foreach ($children as $child) {
            $parent = QuranSubscription::withoutGlobalScopes()->find($child->previous_subscription_id);
            if (! $parent) {
                continue; // Parent already deleted (e.g. by orphan cleanup)
            }

            $parentSessions = \App\Models\QuranSession::withoutGlobalScopes()
                ->where('quran_subscription_id', $parent->id)->count();
            $childSessions = \App\Models\QuranSession::withoutGlobalScopes()
                ->where('quran_subscription_id', $child->id)->count();
            $parentPayments = DB::table('payments')
                ->where('payable_type', QuranSubscription::class)
                ->where('payable_id', $parent->id)->count();

            if ($dryRun) {
                $this->line("  [DRY] Consolidate #{$parent->id}({$parent->status->value},{$parentSessions}s,{$parentPayments}p) -> #{$child->id}({$child->status->value},{$childSessions}s)");
                $this->line('    -> Would archive parent as historical cycle on child');
                $this->line("    -> Would repoint {$parentSessions} sessions, {$parentPayments} payments");
                $this->report['chains_consolidated']++;
                $this->report['sessions_repointed'] += $parentSessions;
                $this->report['payments_repointed'] += $parentPayments;

                continue;
            }

            try {
                DB::transaction(function () use ($parent, $child) {
                    // Archive parent as a historical cycle on the child
                    // (child already has a current cycle from step 1 backfill)
                    SubscriptionCycle::materializeFromSubscription(
                        $parent,
                        $child,
                        SubscriptionCycle::STATE_ARCHIVED,
                        [
                            'metadata' => [
                                'consolidated_from' => $parent->id,
                                'consolidated_at' => now()->toDateTimeString(),
                            ],
                        ]
                    );

                    // Repoint parent's sessions to child
                    $sessCount = \App\Models\QuranSession::withoutGlobalScopes()
                        ->where('quran_subscription_id', $parent->id)
                        ->update(['quran_subscription_id' => $child->id]);
                    $this->report['sessions_repointed'] += $sessCount;

                    // Repoint parent's payments to child
                    $payCount = DB::table('payments')
                        ->where('payable_type', QuranSubscription::class)
                        ->where('payable_id', $parent->id)
                        ->update(['payable_id' => $child->id]);
                    $this->report['payments_repointed'] += $payCount;

                    // Repoint individual circles
                    $circleCount = \App\Models\QuranIndividualCircle::where('subscription_id', $parent->id)
                        ->update(['subscription_id' => $child->id]);
                    $this->report['circles_repointed'] += $circleCount;

                    // Clear previous_subscription_id on child
                    $child->update(['previous_subscription_id' => null]);

                    // Update child's cycle_count
                    $cycleCount = $child->cycles()->count();
                    $child->update(['cycle_count' => max(1, $cycleCount)]);

                    // Delete parent
                    $parent->forceDelete();
                    $this->report['chains_consolidated']++;
                });
            } catch (\Exception $e) {
                $this->report['errors']++;
                $this->error("  ERROR consolidating #{$parent->id} -> #{$child->id}: {$e->getMessage()}");
            }
        }

        $this->info("  Consolidated: {$this->report['chains_consolidated']}, sessions repointed: {$this->report['sessions_repointed']}, payments: {$this->report['payments_repointed']}");
    }

    // ========================================================================
    // STEP 4: Verify integrity
    // ========================================================================

    private function stepVerify(): void
    {
        $this->info('--- STEP 4: Verify integrity ---');
        $issues = 0;

        // 1. Every non-cancelled subscription should have current_cycle_id
        foreach ([QuranSubscription::class, AcademicSubscription::class] as $model) {
            $missing = $model::withoutGlobalScopes()
                ->whereNull('current_cycle_id')
                ->whereNotIn('status', [
                    SessionSubscriptionStatus::CANCELLED->value,
                    SessionSubscriptionStatus::PENDING->value,
                ])
                ->count();
            if ($missing > 0) {
                $this->error("  FAIL: {$missing} active ".class_basename($model).' without current_cycle_id');
                $issues++;
            }
        }

        // 2. No orphan previous_subscription_id references
        $orphanRefs = QuranSubscription::withoutGlobalScopes()
            ->whereNotNull('previous_subscription_id')
            ->count();
        if ($orphanRefs > 0) {
            $this->warn("  WARN: {$orphanRefs} Quran subs still have previous_subscription_id (pending consolidation)");
        } else {
            $this->info('  OK: No orphan previous_subscription_id references');
        }

        // 3. Every current_cycle_id points to a real cycle
        foreach ([QuranSubscription::class, AcademicSubscription::class] as $model) {
            $broken = $model::withoutGlobalScopes()
                ->whereNotNull('current_cycle_id')
                ->whereDoesntHave('currentCycle')
                ->count();
            if ($broken > 0) {
                $this->error("  FAIL: {$broken} ".class_basename($model).' with dangling current_cycle_id');
                $issues++;
            }
        }

        // 4. At most one ACTIVE cycle per subscription
        $duplicateActives = SubscriptionCycle::query()
            ->selectRaw('subscribable_type, subscribable_id, count(*) as cnt')
            ->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)
            ->groupBy('subscribable_type', 'subscribable_id')
            ->having('cnt', '>', 1)
            ->count();
        if ($duplicateActives > 0) {
            $this->error("  FAIL: {$duplicateActives} subscriptions with multiple active cycles");
            $issues++;
        } else {
            $this->info('  OK: No duplicate active cycles');
        }

        // 5. All sessions point to existing subscriptions
        $orphanSessions = \App\Models\QuranSession::withoutGlobalScopes()
            ->whereNotNull('quran_subscription_id')
            ->whereDoesntHave('quranSubscription')
            ->count();
        if ($orphanSessions > 0) {
            $this->error("  FAIL: {$orphanSessions} QuranSessions pointing to deleted subscriptions");
            $issues++;
        } else {
            $this->info('  OK: All sessions point to existing subscriptions');
        }

        // 6. Summary stats
        $totalCycles = SubscriptionCycle::count();
        $activeCycles = SubscriptionCycle::where('cycle_state', SubscriptionCycle::STATE_ACTIVE)->count();
        $archivedCycles = SubscriptionCycle::where('cycle_state', SubscriptionCycle::STATE_ARCHIVED)->count();
        $this->info("  Cycles: {$totalCycles} total ({$activeCycles} active, {$archivedCycles} archived)");

        $totalSubs = QuranSubscription::withoutGlobalScopes()->count()
            + AcademicSubscription::withoutGlobalScopes()->count();
        $this->info("  Subscriptions: {$totalSubs} total");

        if ($issues === 0) {
            $this->info('  ALL INTEGRITY CHECKS PASSED');
        } else {
            $this->error("  {$issues} INTEGRITY ISSUES FOUND");
            $this->report['errors'] += $issues;
        }
    }
}
