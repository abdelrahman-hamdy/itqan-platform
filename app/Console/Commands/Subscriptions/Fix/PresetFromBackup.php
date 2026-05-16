<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Auto-restore the pre-platform consumption offset for admin-wizard subs
 * whose `sub.sessions_used` was wiped by the pre-fix reconciler.
 *
 * Reads `storage/app/preset-recovery-values.json` (extracted offline from
 * the pre-cleanup-20260515-011928.sql.gz backup). For each at-risk sub
 * present in the JSON, stamps `cycle.metadata.pre_platform_consumption_preserved`
 * + `preserved_value`, recomputes `cycle.sessions_used = active_consumption + preset`,
 * and runs the SubscriptionReconciler so `sub.sessions_used` mirrors back.
 *
 * Subs not present in the JSON (count=0 in backup, or created after the
 * backup was taken) stay on the supervisor preset-review page for manual
 * decision. Subs where (active + preset) would exceed total_sessions are
 * skipped + reported.
 *
 * BackfillLog per row (bug_id=preset-from-backup-2026-05-16) for rollback.
 * Dry-run by default; pass --apply to write.
 */
class PresetFromBackup extends Command
{
    protected $signature = 'subscriptions:fix-preset-from-backup
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--json=preset-recovery-values.json : Filename under storage/app/ that holds the preset values}';

    protected $description = 'Auto-restore admin-wizard pre-platform preset values from the May-15 prod backup JSON.';

    private const BUG_ID = 'preset-from-backup-2026-05-16';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $jsonFile = (string) $this->option('json');

        if (! Storage::disk('local')->exists($jsonFile)) {
            $this->error(sprintf('Recovery JSON not found at storage/app/%s', $jsonFile));

            return self::FAILURE;
        }

        $payload = json_decode(Storage::disk('local')->get($jsonFile), true);
        if (! is_array($payload) || ! isset($payload['values']) || ! is_array($payload['values'])) {
            $this->error('Invalid JSON shape (expected {"values": {sub_id: {preset, ...}}})');

            return self::FAILURE;
        }

        $values = $payload['values'];
        $this->info(sprintf(
            '%s — loaded %d recovery values from storage/app/%s (source: %s)',
            $apply ? 'APPLYING' : 'DRY-RUN',
            count($values),
            $jsonFile,
            $payload['backup_source'] ?? 'unknown',
        ));
        $this->newLine();

        $atRiskIds = $this->atRiskSubIds();
        $this->line(sprintf('At-risk subs currently in prod: %d', count($atRiskIds)));

        $scanned = 0;
        $restored = 0;
        $zeroStamped = 0;
        $skipped = [
            'not_in_backup' => 0,
            'preset_exceeds_total' => 0,
            'used_exceeds_total' => 0,
            'cycle_already_has_metadata' => 0,
        ];
        $errors = 0;

        foreach ($atRiskIds as $subId) {
            $scanned++;

            $entry = $values[$subId] ?? $values[(string) $subId] ?? null;
            if (! is_array($entry)) {
                $skipped['not_in_backup']++;
                continue;
            }

            $preset = (int) ($entry['preset'] ?? 0);
            if ($preset < 0) {
                $skipped['not_in_backup']++;
                continue;
            }

            try {
                $sub = QuranSubscription::query()->withoutGlobalScopes()->find($subId);
                if ($sub === null || $sub->current_cycle_id === null) {
                    $errors++;
                    $this->warn(sprintf('sub #%d: missing sub row or current_cycle_id', $subId));
                    continue;
                }

                $cycle = SubscriptionCycle::query()->find($sub->current_cycle_id);
                if ($cycle === null) {
                    $errors++;
                    $this->warn(sprintf('sub #%d: cycle #%d not found', $subId, $sub->current_cycle_id));
                    continue;
                }

                $metadata = (array) ($cycle->metadata ?? []);
                if (! empty($metadata['pre_platform_consumption_preserved'])
                    || isset($metadata['unaccounted_sessions_used'])) {
                    $skipped['cycle_already_has_metadata']++;
                    continue;
                }

                $totalSessions = (int) $sub->total_sessions;
                if ($preset >= $totalSessions) {
                    $skipped['preset_exceeds_total']++;
                    $this->warn(sprintf('sub #%d: preset=%d >= total=%d, skipped', $subId, $preset, $totalSessions));
                    continue;
                }

                $activeConsumption = SessionConsumption::query()
                    ->where('subscription_id', $sub->getKey())
                    ->where('subscription_type', $sub->getMorphClass())
                    ->whereNull('reversed_at')
                    ->count();

                $newCycleUsed = $activeConsumption + $preset;
                if ($newCycleUsed > $totalSessions) {
                    $skipped['used_exceeds_total']++;
                    $this->warn(sprintf(
                        'sub #%d: active=%d + preset=%d = %d > total=%d, skipped (needs manual review)',
                        $subId,
                        $activeConsumption,
                        $preset,
                        $newCycleUsed,
                        $totalSessions,
                    ));
                    continue;
                }

                if (! $apply) {
                    $this->line(sprintf(
                        '  would %s sub #%d: preset=%d, active=%d, cycle.used %d→%d',
                        $preset > 0 ? 'restore' : 'mark-zero',
                        $subId,
                        $preset,
                        $activeConsumption,
                        (int) $cycle->sessions_used,
                        $newCycleUsed,
                    ));
                    if ($preset > 0) {
                        $restored++;
                    } else {
                        $zeroStamped++;
                    }
                    continue;
                }

                DB::transaction(function () use ($sub, $cycle, $entry, $preset, $newCycleUsed, $reconciler) {
                    $originalMetadata = $cycle->metadata;
                    $originalSessionsUsed = (int) $cycle->sessions_used;

                    $metadata = (array) ($cycle->metadata ?? []);
                    $metadata['pre_platform_consumption_preserved'] = true;
                    $metadata['preserved_value'] = $preset;
                    $metadata['preserved_at'] = now()->toDateTimeString();
                    $metadata['preserved_source'] = $entry['source'] ?? 'backup_restore_2026-05-15';
                    $metadata['preserved_backup_sub_used'] = $entry['backup_sub_used'] ?? null;
                    $metadata['preserved_backup_cycle_used'] = $entry['backup_cycle_used'] ?? null;

                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'subscription_cycles',
                        'row_id' => $cycle->id,
                        'column_name' => 'sessions_used_metadata',
                        'original_value' => json_encode([
                            'sessions_used' => $originalSessionsUsed,
                            'metadata' => $originalMetadata,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'new_value' => json_encode([
                            'sessions_used' => $newCycleUsed,
                            'metadata' => $metadata,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'backfill_command' => 'subscriptions:fix-preset-from-backup',
                        'ran_at' => now(),
                    ]);

                    $cycle->metadata = $metadata;
                    $cycle->sessions_used = $newCycleUsed;
                    $cycle->save();

                    $reconciler->sync($sub->fresh(['currentCycle']));
                });

                if ($preset > 0) {
                    $restored++;
                    $this->line(sprintf('  ✓ restored sub #%d (preset=%d)', $subId, $preset));
                } else {
                    $zeroStamped++;
                    $this->line(sprintf('  ✓ marked sub #%d preset=0 (off the review list)', $subId));
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('sub #%d ERROR: %s', $subId, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: scanned=%d restored=%d zero_stamped=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $scanned,
            $restored,
            $zeroStamped,
            $errors,
        ));

        if (array_sum($skipped) > 0) {
            $this->newLine();
            $this->warn('Skipped rows:');
            foreach ($skipped as $reason => $n) {
                if ($n > 0) {
                    $this->line(sprintf('  %s: %d', $reason, $n));
                }
            }
            $this->line('  → these subs remain on /manage/preset-sessions-review for supervisor input.');
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Same predicate used by PresetSessionsReviewService::atRiskSubs(): the
     * 77 admin-wizard subs whose current cycle is missing the preserved-offset
     * metadata flag.
     *
     * @return list<int>
     */
    private function atRiskSubIds(): array
    {
        return DB::table('quran_subscriptions AS s')
            ->join('subscription_cycles AS c', 'c.id', '=', 's.current_cycle_id')
            ->where('s.purchase_source', 'admin')
            ->where('c.metadata', 'LIKE', '%materialized_from_subscription%')
            ->where('c.metadata', 'NOT LIKE', '%pre_platform_consumption_preserved%')
            ->where('c.metadata', 'NOT LIKE', '%unaccounted_sessions_used%')
            ->whereNull('s.deleted_at')
            ->orderBy('s.id')
            ->pluck('s.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
