<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-deletes orphan `subscription_cycles` — rows whose `subscribable_id`
 * no longer matches any row in the parent table (parent subscription was
 * deleted but the cycle row was never cleaned up).
 *
 * Pre-conditions enforced PER CYCLE:
 *   - parent sub does NOT exist
 *   - zero un-reversed `session_consumption` rows reference the cycle
 *   - zero `payments` rows reference `subscription_cycle_id`
 *   - zero quran_sessions / academic_sessions reference `subscription_cycle_id`
 *
 * Any cycle that fails ANY pre-condition is quarantined and reported, NOT
 * soft-deleted. The audit at the start of the run prints each cycle's
 * eligibility for human review.
 *
 * Once soft-deleted, the cycle disappears from all Eloquent queries
 * (SoftDeletes global scope) — invariant checker, audit-pricing-trust,
 * supervisor inspector, etc. — without losing the row. Restore via
 * `cycle->restore()` or `DB::table('subscription_cycles')->update([deleted_at => null])`.
 *
 * Each soft-delete writes a BackfillLog row keyed
 * `bug_id='orphan-cycle-soft-delete'` with the full cycle row as JSON in
 * `original_value` for forensic recovery.
 */
class OrphanCycleSoftDelete extends Command
{
    protected $signature = 'subscriptions:fix-orphan-cycle-soft-delete
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--academy= : Restrict to one academy id}';

    protected $description = 'Soft-delete orphan subscription_cycles (parent sub missing) with per-cycle BackfillLog.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        if (! Schema::hasColumn('subscription_cycles', 'deleted_at')) {
            $this->error('subscription_cycles.deleted_at column missing — run the migration first.');
            return self::FAILURE;
        }

        $query = SubscriptionCycle::query()->withTrashed();
        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }
        $cycles = $query->get();

        // Phase 1: filter to orphans (parent sub missing).
        $orphans = $cycles->filter(function (SubscriptionCycle $c) {
            return ! match ($c->subscribable_type) {
                'quran_subscription' => QuranSubscription::withoutGlobalScopes()->where('id', $c->subscribable_id)->exists(),
                'academic_subscription' => AcademicSubscription::withoutGlobalScopes()->where('id', $c->subscribable_id)->exists(),
                'course_subscription' => CourseSubscription::withoutGlobalScopes()->where('id', $c->subscribable_id)->exists(),
                default => true,
            };
        });

        $this->info(sprintf('Orphan cycles found: %d', $orphans->count()));

        $eligible = [];
        $quarantine = [];

        foreach ($orphans as $c) {
            // Skip already soft-deleted.
            if ($c->deleted_at !== null) {
                continue;
            }

            $consumptionCount = SessionConsumption::query()->where('cycle_id', $c->id)->count();
            $paymentCycleRefs = DB::table('payments')->where('subscription_cycle_id', $c->id)->count();
            $sessRefs = 0;
            foreach (['quran_sessions', 'academic_sessions'] as $tbl) {
                if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'subscription_cycle_id')) {
                    $sessRefs += DB::table($tbl)->where('subscription_cycle_id', $c->id)->count();
                }
            }

            $row = [
                'cycle_id' => $c->id,
                'sub_id' => $c->subscribable_id,
                'sub_type' => $c->subscribable_type,
                'state' => $c->cycle_state,
                'payment_status' => $c->payment_status,
                'final_price' => (float) $c->final_price,
                'consumption' => $consumptionCount,
                'payments_to_cycle' => $paymentCycleRefs,
                'sessions_to_cycle' => $sessRefs,
                'created_at' => optional($c->created_at)?->toDateTimeString(),
            ];

            if ($consumptionCount === 0 && $paymentCycleRefs === 0 && $sessRefs === 0) {
                $row['_cycle_obj'] = $c;
                $eligible[] = $row;
            } else {
                $quarantine[] = $row;
            }
        }

        $this->info(sprintf('Eligible (zero refs): %d', count($eligible)));
        if (! empty($quarantine)) {
            $this->warn(sprintf('Quarantined (has refs, NOT auto-deleted): %d', count($quarantine)));
            $this->table(
                ['cycle', 'sub_id', 'sub_type', 'state', 'consumption', 'payments', 'sessions'],
                array_map(static fn ($r) => [$r['cycle_id'], $r['sub_id'], $r['sub_type'], $r['state'], $r['consumption'], $r['payments_to_cycle'], $r['sessions_to_cycle']], $quarantine),
            );
        }

        if ($eligible === []) {
            $this->info('Nothing to do.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line(sprintf('Preview (first 10 of %d eligible):', count($eligible)));
        $this->table(
            ['cycle', 'sub_id', 'sub_type', 'state', 'payment', 'final', 'created_at'],
            array_map(static fn ($r) => [$r['cycle_id'], $r['sub_id'], $r['sub_type'], $r['state'], $r['payment_status'], $r['final_price'], $r['created_at']], array_slice($eligible, 0, 10)),
        );

        if (! $apply) {
            $this->line('');
            $this->comment('DRY-RUN. Re-run with --apply to soft-delete.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($eligible));
        $bar->start();
        $touched = 0;
        $errors = 0;
        foreach ($eligible as $plan) {
            try {
                DB::transaction(fn () => $this->softDelete($plan));
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\ncycle #%d: %s", $plan['cycle_id'], $e->getMessage()));
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
        $this->info(sprintf('APPLIED: %d cycle(s) soft-deleted; %d error(s).', $touched, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function softDelete(array $plan): void
    {
        /** @var SubscriptionCycle $cycle */
        $cycle = $plan['_cycle_obj'];

        // Capture full row as JSON for rollback.
        $original = $cycle->getAttributes();

        BackfillLog::create([
            'bug_id' => 'orphan-cycle-soft-delete',
            'table_name' => 'subscription_cycles',
            'row_id' => $cycle->id,
            'column_name' => 'deleted_at',
            'original_value' => json_encode($original, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_value' => Carbon::now()->toDateTimeString(),
            'backfill_command' => 'subscriptions:fix-orphan-cycle-soft-delete',
            'ran_at' => Carbon::now(),
        ]);

        $cycle->delete();
    }
}
