<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentSessionReport;
use App\Models\SubscriptionCycle;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fixes data integrity issues caused by the April 7 2026 DB restore
 * (March 31 backup + binlog replay).
 *
 * Issues:
 * - 7 subscriptions with duplicate individual circles
 * - 151 duplicate sessions (same circle + same time slot)
 * - 111 subscriptions exceeding their session limits
 * - Subscription counter desync from ghost-circle counting
 */
class FixPostRecoveryData extends Command
{
    protected $signature = 'app:fix-post-recovery-data
        {--dry-run : Report all issues without modifying anything}
        {--phase= : Run specific phase (audit|circles|sessions|overlimit|counters|all)}
        {--force : Skip confirmation prompts}';

    protected $description = 'Fix duplicate circles, sessions, and counter desync from the April 7 DB restore';

    private array $auditLog = [];

    private int $fixedCircles = 0;

    private int $fixedSessions = 0;

    private int $fixedOverLimit = 0;

    private int $fixedCounters = 0;

    /**
     * Hardcoded mapping from production analysis.
     * auth = authoritative circle (keep), ghost = duplicate circle (deactivate).
     * Sub 849 is inverted — education_unit_id points to the empty ghost.
     */
    private const CIRCLE_MAPPING = [
        535 => ['auth' => 171, 'ghost' => 77],
        757 => ['auth' => 343, 'ghost' => 231],
        798 => ['auth' => 377, 'ghost' => 156],
        818 => ['auth' => 391, 'ghost' => 374],
        849 => ['auth' => 324, 'ghost' => 418],
        888 => ['auth' => 437, 'ghost' => 163],
        910 => ['auth' => 449, 'ghost' => 189],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $phase = $this->option('phase') ?: 'all';

        $this->info($dryRun ? '=== DRY RUN MODE ===' : '=== LIVE EXECUTION ===');
        $this->newLine();

        // Phase 1: Audit always runs first
        $this->runAudit();

        if ($dryRun) {
            $this->info('Dry run complete. No changes made.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Proceed with fixes?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if (in_array($phase, ['all', 'circles'])) {
            $this->runFixDuplicateCircles();
        }

        if (in_array($phase, ['all', 'sessions'])) {
            $this->runFixDuplicateSessions();
        }

        if (in_array($phase, ['all', 'overlimit'])) {
            $this->runFixOverLimitSubscriptions();
        }

        if (in_array($phase, ['all', 'counters'])) {
            $this->runRecalculateCounters();
        }

        // Save audit log
        $logPath = storage_path('logs/post-recovery-fix-'.now()->format('Y-m-d_His').'.json');
        file_put_contents($logPath, json_encode($this->auditLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info("Summary: circles={$this->fixedCircles}, sessions={$this->fixedSessions}, overlimit={$this->fixedOverLimit}, counters={$this->fixedCounters}");
        $this->info("Audit log saved to: {$logPath}");

        // Re-run audit to verify
        $this->newLine();
        $this->info('=== POST-FIX VERIFICATION ===');
        $this->runAudit();

        return self::SUCCESS;
    }

    // ========================================================================
    // Phase 1: Audit
    // ========================================================================

    private function runAudit(): void
    {
        $this->info('--- Phase 1: Audit ---');

        // 1. Duplicate circles
        $dupCircles = $this->findDuplicateCircles();
        $this->info("Duplicate circles: {$dupCircles->count()} subscriptions affected");
        foreach ($dupCircles as $sub) {
            $this->line("  Sub {$sub->subscription_id}: circles [{$sub->circle_ids}]");
        }

        // 2. Duplicate sessions (same circle + same time)
        $dupSessions = $this->findDuplicateSessions();
        $this->info("Duplicate session groups: {$dupSessions->count()} ({$dupSessions->sum(fn ($g) => $g->dup_count - 1)} extra sessions)");

        // 3. Over-limit subscriptions
        $overLimit = $this->findOverLimitSubscriptions();
        $this->info("Over-limit subscriptions: {$overLimit->count()}");

        // 4. Counter mismatches
        $counterMismatches = $this->findCounterMismatches();
        $this->info("Counter mismatches (remaining=0 with scheduled): {$counterMismatches->count()}");

        $this->newLine();
    }

    private function findDuplicateCircles(): \Illuminate\Support\Collection
    {
        return collect(DB::select('
            SELECT subscription_id, GROUP_CONCAT(id ORDER BY id) as circle_ids, COUNT(*) as cnt
            FROM quran_individual_circles
            WHERE subscription_id IS NOT NULL AND deleted_at IS NULL
            GROUP BY subscription_id
            HAVING cnt > 1
        '));
    }

    private function findDuplicateSessions(): \Illuminate\Support\Collection
    {
        return collect(DB::select('
            SELECT individual_circle_id, scheduled_at, COUNT(*) as dup_count,
                   GROUP_CONCAT(id ORDER BY id) as session_ids,
                   GROUP_CONCAT(status ORDER BY id) as statuses
            FROM quran_sessions
            WHERE individual_circle_id IS NOT NULL
              AND status != ?
              AND deleted_at IS NULL
            GROUP BY individual_circle_id, scheduled_at
            HAVING dup_count > 1
        ', [SessionStatus::CANCELLED->value]));
    }

    private function findOverLimitSubscriptions(): \Illuminate\Support\Collection
    {
        return collect(DB::select("
            SELECT qs.id as sub_id, qs.total_sessions, COUNT(qsess.id) as actual_sessions,
                   SUM(CASE WHEN qsess.status = 'completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN qsess.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
            FROM quran_subscriptions qs
            JOIN quran_sessions qsess ON qsess.quran_subscription_id = qs.id
                AND qsess.status != ?
                AND qsess.deleted_at IS NULL
            WHERE qs.deleted_at IS NULL
            GROUP BY qs.id, qs.total_sessions
            HAVING actual_sessions > qs.total_sessions
            ORDER BY (actual_sessions - qs.total_sessions) DESC
        ", [SessionStatus::CANCELLED->value]));
    }

    private function findCounterMismatches(): \Illuminate\Support\Collection
    {
        return collect(DB::select("
            SELECT qs.id, qs.sessions_remaining, COUNT(qsess.id) as scheduled_count
            FROM quran_subscriptions qs
            JOIN quran_sessions qsess ON qsess.quran_subscription_id = qs.id
                AND qsess.status IN ('scheduled', 'ready')
                AND qsess.deleted_at IS NULL
            WHERE qs.sessions_remaining <= 0
              AND qs.status = 'active'
              AND qs.deleted_at IS NULL
            GROUP BY qs.id, qs.sessions_remaining
            HAVING scheduled_count > 0
        "));
    }

    // ========================================================================
    // Phase 2: Fix Duplicate Circles
    // ========================================================================

    private function runFixDuplicateCircles(): void
    {
        $this->info('--- Phase 2: Fix Duplicate Circles ---');

        foreach (self::CIRCLE_MAPPING as $subId => $mapping) {
            $authCircleId = $mapping['auth'];
            $ghostCircleId = $mapping['ghost'];

            DB::transaction(function () use ($subId, $authCircleId, $ghostCircleId) {
                $sub = QuranSubscription::withoutGlobalScopes()->lockForUpdate()->find($subId);
                if (! $sub) {
                    $this->warn("  Sub {$subId}: not found, skipping");

                    return;
                }

                $ghostCircle = QuranIndividualCircle::withoutGlobalScopes()->lockForUpdate()->find($ghostCircleId);
                if (! $ghostCircle) {
                    $this->warn("  Ghost circle {$ghostCircleId}: not found, skipping");

                    return;
                }

                // Skip if already deactivated (idempotent)
                if (! $ghostCircle->is_active && $ghostCircle->subscription_id === null) {
                    $this->line("  Sub {$subId}: ghost circle {$ghostCircleId} already deactivated, skipping");

                    return;
                }

                $authCircle = QuranIndividualCircle::withoutGlobalScopes()->lockForUpdate()->find($authCircleId);
                if (! $authCircle) {
                    $this->warn("  Auth circle {$authCircleId}: not found, skipping");

                    return;
                }

                // Step 1: Migrate ghost sessions to auth circle
                $ghostSessions = QuranSession::withoutGlobalScopes()
                    ->where('individual_circle_id', $ghostCircleId)
                    ->whereNull('deleted_at')
                    ->where('status', '!=', SessionStatus::CANCELLED->value)
                    ->get();

                $migratedCount = 0;
                foreach ($ghostSessions as $session) {
                    $session->update(['individual_circle_id' => $authCircleId]);
                    $migratedCount++;

                    $this->auditLog[] = [
                        'phase' => 2,
                        'action' => 'migrate_session',
                        'session_id' => $session->id,
                        'from_circle' => $ghostCircleId,
                        'to_circle' => $authCircleId,
                        'subscription_id' => $subId,
                    ];
                }

                // Also migrate cancelled/deleted sessions for completeness
                QuranSession::withoutGlobalScopes()
                    ->where('individual_circle_id', $ghostCircleId)
                    ->update(['individual_circle_id' => $authCircleId]);

                // Step 2: Fix education_unit_id if inverted (Sub 849)
                if ($sub->education_unit_id != $authCircleId) {
                    $oldEduUnit = $sub->education_unit_id;
                    $sub->update([
                        'education_unit_id' => $authCircleId,
                        'education_unit_type' => 'individual_circle',
                    ]);

                    $this->auditLog[] = [
                        'phase' => 2,
                        'action' => 'fix_education_unit',
                        'subscription_id' => $subId,
                        'old_education_unit_id' => $oldEduUnit,
                        'new_education_unit_id' => $authCircleId,
                    ];
                }

                // Step 3: Deactivate ghost circle
                $ghostCircle->update([
                    'is_active' => false,
                    'subscription_id' => null,
                ]);

                $this->auditLog[] = [
                    'phase' => 2,
                    'action' => 'deactivate_ghost_circle',
                    'circle_id' => $ghostCircleId,
                    'subscription_id' => $subId,
                    'migrated_sessions' => $migratedCount,
                ];

                $this->fixedCircles++;
                $this->line("  Sub {$subId}: migrated {$migratedCount} sessions from circle {$ghostCircleId} -> {$authCircleId}, ghost deactivated");
            });
        }

        $this->info("Phase 2 complete: {$this->fixedCircles} circles fixed");
        $this->newLine();
    }

    // ========================================================================
    // Phase 3: Fix Duplicate Sessions
    // ========================================================================

    private function runFixDuplicateSessions(): void
    {
        $this->info('--- Phase 3: Fix Duplicate Sessions ---');

        $duplicateGroups = $this->findDuplicateSessions();

        foreach ($duplicateGroups as $group) {
            $sessionIds = array_map('intval', explode(',', $group->session_ids));

            DB::transaction(function () use ($sessionIds) {
                $sessions = QuranSession::withoutGlobalScopes()
                    ->whereIn('id', $sessionIds)
                    ->whereNull('deleted_at')
                    ->where('status', '!=', SessionStatus::CANCELLED->value)
                    ->lockForUpdate()
                    ->get();

                if ($sessions->count() <= 1) {
                    return; // Already resolved or only 1 left
                }

                // Score each session to pick winner
                $scored = $sessions->map(function ($s) {
                    $score = 0;
                    $statusValue = $s->status instanceof SessionStatus ? $s->status->value : $s->status;

                    if ($statusValue === SessionStatus::COMPLETED->value) {
                        $score += 10000;
                    }

                    $reportCount = StudentSessionReport::withoutGlobalScopes()
                        ->where('session_id', $s->id)
                        ->whereNull('deleted_at')
                        ->count();
                    if ($reportCount > 0) {
                        $score += 1000 * $reportCount;
                    }

                    if ($s->subscription_counted) {
                        $score += 50;
                    }

                    // Lower ID = older = slightly preferred as tiebreaker
                    $score += (10000000 - $s->id) * 0.0001;

                    return ['session' => $s, 'score' => $score];
                })->sortByDesc('score')->values();

                $winner = $scored->first()['session'];
                $losers = $scored->skip(1)->pluck('session');

                foreach ($losers as $loser) {
                    $this->softDeleteDuplicateSession($loser, $winner);
                }
            });
        }

        $this->info("Phase 3 complete: {$this->fixedSessions} duplicate sessions soft-deleted");
        $this->newLine();
    }

    private function softDeleteDuplicateSession(QuranSession $loser, QuranSession $winner): void
    {
        $now = now();

        // Soft-delete associated reports
        $reportCount = StudentSessionReport::withoutGlobalScopes()
            ->where('session_id', $loser->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        // Soft-delete associated teacher earnings
        $earningCount = TeacherEarning::withoutGlobalScopes()
            ->where('session_type', 'quran_session')
            ->where('session_id', $loser->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        // Soft-delete the session itself (direct update to avoid model events)
        QuranSession::withoutGlobalScopes()
            ->where('id', $loser->id)
            ->update([
                'deleted_at' => $now,
                'cancellation_reason' => "[fix-post-recovery] Duplicate session soft-deleted. Winner: session {$winner->id}",
            ]);

        $this->auditLog[] = [
            'phase' => 3,
            'action' => 'soft_delete_duplicate_session',
            'session_id' => $loser->id,
            'winner_session_id' => $winner->id,
            'circle_id' => $loser->individual_circle_id,
            'scheduled_at' => $loser->scheduled_at?->toDateTimeString(),
            'loser_status' => $loser->status instanceof SessionStatus ? $loser->status->value : $loser->status,
            'was_subscription_counted' => (bool) $loser->subscription_counted,
            'deleted_reports' => $reportCount,
            'deleted_earnings' => $earningCount,
        ];

        $this->fixedSessions++;
    }

    // ========================================================================
    // Phase 4: Fix Over-Limit Subscriptions
    // ========================================================================

    private function runFixOverLimitSubscriptions(): void
    {
        $this->info('--- Phase 4: Fix Over-Limit Subscriptions ---');

        $overLimit = $this->findOverLimitSubscriptions();

        foreach ($overLimit as $info) {
            $subId = $info->sub_id;
            $excess = $info->actual_sessions - $info->total_sessions;
            $scheduledAvailable = $info->scheduled;

            if ($excess <= 0) {
                continue; // Already resolved by earlier phases
            }

            $toCancel = min($excess, $scheduledAvailable);

            if ($toCancel <= 0) {
                $this->warn("  Sub {$subId}: {$excess} excess sessions are all completed. Manual review needed.");
                $this->auditLog[] = [
                    'phase' => 4,
                    'action' => 'overlimit_all_completed',
                    'subscription_id' => $subId,
                    'excess' => $excess,
                ];

                continue;
            }

            DB::transaction(function () use ($subId, $toCancel, $excess) {
                $sub = QuranSubscription::withoutGlobalScopes()->lockForUpdate()->find($subId);
                if (! $sub) {
                    return;
                }

                // Find the auth circle
                $circleId = $sub->education_unit_id;
                if (! $circleId) {
                    $circle = QuranIndividualCircle::withoutGlobalScopes()
                        ->where('subscription_id', $subId)
                        ->where('is_active', true)
                        ->first();
                    $circleId = $circle?->id;
                }
                if (! $circleId) {
                    return;
                }

                // Get furthest-future scheduled sessions to cancel
                $sessionsToCancel = QuranSession::withoutGlobalScopes()
                    ->where('quran_subscription_id', $subId)
                    ->whereIn('status', [SessionStatus::SCHEDULED->value, 'ready'])
                    ->whereNull('deleted_at')
                    ->orderByDesc('scheduled_at')
                    ->limit($toCancel)
                    ->get();

                $now = now();
                foreach ($sessionsToCancel as $session) {
                    // Soft-delete reports (shouldn't exist for scheduled, but be safe)
                    StudentSessionReport::withoutGlobalScopes()
                        ->where('session_id', $session->id)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => $now]);

                    // Soft-delete the session
                    QuranSession::withoutGlobalScopes()
                        ->where('id', $session->id)
                        ->update([
                            'deleted_at' => $now,
                            'cancellation_reason' => "[fix-post-recovery] Excess session beyond limit of {$sub->total_sessions}",
                        ]);

                    $this->auditLog[] = [
                        'phase' => 4,
                        'action' => 'soft_delete_excess_session',
                        'session_id' => $session->id,
                        'subscription_id' => $subId,
                        'scheduled_at' => $session->scheduled_at?->toDateTimeString(),
                    ];

                    $this->fixedOverLimit++;
                }

                $remainingExcess = $excess - $sessionsToCancel->count();
                if ($remainingExcess > 0) {
                    $this->warn("  Sub {$subId}: still {$remainingExcess} excess after cancelling {$sessionsToCancel->count()} scheduled.");
                    $this->auditLog[] = [
                        'phase' => 4,
                        'action' => 'overlimit_residual',
                        'subscription_id' => $subId,
                        'remaining_excess' => $remainingExcess,
                    ];
                }

                $this->line("  Sub {$subId}: soft-deleted {$sessionsToCancel->count()} excess scheduled sessions");
            });
        }

        $this->info("Phase 4 complete: {$this->fixedOverLimit} excess sessions soft-deleted");
        $this->newLine();
    }

    // ========================================================================
    // Phase 5: Recalculate Counters
    // ========================================================================

    private function runRecalculateCounters(): void
    {
        $this->info('--- Phase 5: Recalculate Counters ---');

        // Collect all affected subscription IDs from the audit log
        $affectedSubIds = collect($this->auditLog)
            ->pluck('subscription_id')
            ->filter()
            ->unique();

        // Also include all subs from the circle mapping
        $affectedSubIds = $affectedSubIds->merge(array_keys(self::CIRCLE_MAPPING))->unique();

        foreach ($affectedSubIds as $subId) {
            DB::transaction(function () use ($subId) {
                $sub = QuranSubscription::withoutGlobalScopes()->lockForUpdate()->find($subId);
                if (! $sub) {
                    return;
                }

                // Find the authoritative circle
                $circle = null;
                if ($sub->education_unit_id) {
                    $circle = QuranIndividualCircle::withoutGlobalScopes()->find($sub->education_unit_id);
                }
                if (! $circle) {
                    $circle = QuranIndividualCircle::withoutGlobalScopes()
                        ->where('subscription_id', $sub->id)
                        ->where('is_active', true)
                        ->first();
                }
                if (! $circle) {
                    $this->warn("  Sub {$subId}: no active circle found, skipping counter fix");

                    return;
                }

                // Count completed sessions that were counted towards subscription
                $completedCounted = QuranSession::withoutGlobalScopes()
                    ->where('individual_circle_id', $circle->id)
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->where('subscription_counted', true)
                    ->whereNull('deleted_at')
                    ->count();

                $oldUsed = $sub->sessions_used;
                $oldRemaining = $sub->sessions_remaining;
                $oldCompleted = $sub->total_sessions_completed;

                $newUsed = $completedCounted;
                $newRemaining = max(0, $sub->total_sessions - $completedCounted);
                $newCompleted = $completedCounted;

                // Only update if something changed
                if ($oldUsed === $newUsed && $oldRemaining === $newRemaining && $oldCompleted === $newCompleted) {
                    return;
                }

                $metadata = $sub->metadata ?? [];
                if ($newRemaining > 0) {
                    unset($metadata['sessions_exhausted'], $metadata['sessions_exhausted_at']);
                } elseif ($newRemaining <= 0 && empty($metadata['sessions_exhausted'])) {
                    $metadata['sessions_exhausted'] = true;
                    $metadata['sessions_exhausted_at'] = now()->toDateTimeString();
                }

                $sub->update([
                    'sessions_used' => $newUsed,
                    'sessions_remaining' => $newRemaining,
                    'total_sessions_completed' => $newCompleted,
                    'metadata' => $metadata ?: null,
                ]);

                // Update cycle counters if applicable
                if ($sub->current_cycle_id) {
                    SubscriptionCycle::where('id', $sub->current_cycle_id)->update([
                        'sessions_used' => $newUsed,
                        'sessions_completed' => $newCompleted,
                    ]);
                }

                // Recalculate circle session counts
                $circle->update([
                    'sessions_scheduled' => QuranSession::withoutGlobalScopes()
                        ->where('individual_circle_id', $circle->id)
                        ->whereIn('status', [SessionStatus::SCHEDULED->value, 'ready'])
                        ->whereNull('deleted_at')
                        ->count(),
                    'sessions_completed' => QuranSession::withoutGlobalScopes()
                        ->where('individual_circle_id', $circle->id)
                        ->where('status', SessionStatus::COMPLETED->value)
                        ->whereNull('deleted_at')
                        ->count(),
                ]);

                $this->auditLog[] = [
                    'phase' => 5,
                    'action' => 'recalculate_counters',
                    'subscription_id' => $subId,
                    'circle_id' => $circle->id,
                    'old' => ['used' => $oldUsed, 'remaining' => $oldRemaining, 'completed' => $oldCompleted],
                    'new' => ['used' => $newUsed, 'remaining' => $newRemaining, 'completed' => $newCompleted],
                ];

                $this->fixedCounters++;
                $this->line("  Sub {$subId}: used {$oldUsed}->{$newUsed}, remaining {$oldRemaining}->{$newRemaining}, completed {$oldCompleted}->{$newCompleted}");
            });
        }

        $this->info("Phase 5 complete: {$this->fixedCounters} subscription counters recalculated");
        $this->newLine();
    }
}
