<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aligns `payments.subscription_id` with `payments.payable_id` for the small
 * population (15 rows on prod 2026-05-16) where the legacy direct FK and the
 * modern polymorphic FK disagree.
 *
 * Root cause: prior sub-merge / sub-deletion operations updated the
 * polymorphic `payable_id` to point at the surviving sub but left the legacy
 * `subscription_id` pointing at the now-deleted sub. The polymorphic
 * relation is canonical (no code paths read `payments.subscription_id`
 * directly per grep on 2026-05-16), so the misalignment is purely a data
 * hygiene issue — but it surfaces in any direct-FK reporting query.
 *
 * Safety gates per payment:
 *   1. `payable_type` resolves to a known sub model (FQCN or morph alias)
 *   2. `payable_id` references an EXISTING subscription
 *   3. `subscription_id` references a MISSING (deleted) subscription
 *   4. They differ
 *
 * Each fix is logged to `BackfillLog` under bug_id='payment-sub-id-crossref'
 * with the original subscription_id captured for rollback.
 */
class PaymentSubscriptionIdCrossref extends Command
{
    protected $signature = 'subscriptions:fix-payment-sub-id-crossref
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Align payments.subscription_id with payments.payable_id where they disagree (legacy stale FK).';

    private const QURAN_PAYABLE_TYPES = ['App\\Models\\QuranSubscription', 'quran_subscription'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $candidates = DB::table('payments')
            ->whereIn('payable_type', self::QURAN_PAYABLE_TYPES)
            ->whereNotNull('subscription_id')
            ->whereNotNull('payable_id')
            ->whereColumn('subscription_id', '!=', 'payable_id')
            ->get();

        $this->info(sprintf('Candidates with subscription_id != payable_id: %d', count($candidates)));

        $eligible = [];
        $rejected = [];

        foreach ($candidates as $p) {
            $subFromSubId = QuranSubscription::withoutGlobalScopes()->find($p->subscription_id);
            $subFromPayableId = QuranSubscription::withoutGlobalScopes()->find($p->payable_id);

            // Gate: payable_id MUST resolve, subscription_id MUST be missing.
            // This is the only signature we trust 100% — surviving sub via
            // payable_id, dead sub via subscription_id.
            if ($subFromPayableId === null) {
                $rejected[] = ['pmt' => $p->id, 'reason' => 'payable_id_missing'];
                continue;
            }
            if ($subFromSubId !== null) {
                $rejected[] = ['pmt' => $p->id, 'reason' => 'subscription_id_still_exists_admin_call'];
                continue;
            }

            $eligible[] = [
                'pmt_id' => $p->id,
                'old_sub_id' => $p->subscription_id,
                'new_sub_id' => $p->payable_id,
                'amount' => $p->amount,
                'status' => $p->status,
            ];
        }

        $this->info(sprintf('Eligible to auto-fix: %d', count($eligible)));
        if (! empty($rejected)) {
            $this->warn(sprintf('Rejected (need admin review): %d', count($rejected)));
            foreach ($rejected as $r) {
                $this->line(sprintf('  pmt #%d — %s', $r['pmt'], $r['reason']));
            }
        }

        if ($eligible === []) {
            return self::SUCCESS;
        }

        $this->table(
            ['pmt_id', 'old_subscription_id', 'new_subscription_id', 'amount', 'status'],
            array_map(static fn ($r) => [$r['pmt_id'], $r['old_sub_id'], $r['new_sub_id'], $r['amount'], $r['status']], $eligible),
        );

        if (! $apply) {
            $this->comment('DRY-RUN. Re-run with --apply to perform the writes.');
            return self::SUCCESS;
        }

        $touched = 0;
        $errors = 0;
        foreach ($eligible as $plan) {
            try {
                DB::transaction(function () use ($plan) {
                    BackfillLog::create([
                        'bug_id' => 'payment-sub-id-crossref',
                        'table_name' => 'payments',
                        'row_id' => $plan['pmt_id'],
                        'column_name' => 'subscription_id',
                        'original_value' => (string) $plan['old_sub_id'],
                        'new_value' => (string) $plan['new_sub_id'],
                        'backfill_command' => 'subscriptions:fix-payment-sub-id-crossref',
                        'ran_at' => Carbon::now(),
                    ]);
                    DB::table('payments')
                        ->where('id', $plan['pmt_id'])
                        ->update(['subscription_id' => $plan['new_sub_id'], 'updated_at' => Carbon::now()]);
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('pmt #%d: %s', $plan['pmt_id'], $e->getMessage()));
            }
        }

        $this->info(sprintf('APPLIED: %d payment(s); %d error(s).', $touched, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
