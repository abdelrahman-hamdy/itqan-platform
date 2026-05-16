<?php

namespace App\Console\Commands\Subscriptions;

use App\Enums\BillingCycle;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\PricingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * INV-D2 classifier: turns the flat output of `subscriptions:audit-pricing-trust`
 * into a per-signature breakdown so admin triage is per-class, not per-row.
 *
 * Signatures (deterministic, mutually exclusive — first match wins):
 *
 *   DRIFT_FROM_PACKAGE_PRICE
 *     Cycle has NO `package_snapshot` (legacy row) AND the LIVE package price
 *     differs from `final_price + discount_amount`. This is the post-2026_05_14
 *     pricing-trust migration shape: snapshot=NULL, source defaulted to
 *     'package', and the live package price has since drifted. INV-D4 says
 *     historical prices should NOT be re-resolved against live — the right
 *     fix is to backfill `package_snapshot` from the cycle's recorded price.
 *
 *   DISCOUNT_NOT_RECORDED
 *     `actual < expected`, `discount_amount = 0`, and the delta is positive.
 *     Strong indicator a discount/coupon was applied to `final_price` but the
 *     `discount_amount` column was never populated. Backfill candidate.
 *
 *   DISCOUNT_NOT_APPLIED
 *     `discount_amount > 0` but `actual = expected + discount_amount` (within
 *     epsilon). Discount column says X off; final_price still has the full
 *     amount. Either the discount column is wrong, or the final_price never
 *     got the subtraction.
 *
 *   FREE_NOT_OVERRIDE
 *     `actual = 0`, `expected > 0`. Sub was given away (sponsorship, trial
 *     gift, scholarship) but the row still claims `pricing_source = 'package'`.
 *     Should be `sale_price` or `manual_override` with a reason recorded.
 *
 *   OVERCHARGE_NOT_OVERRIDE
 *     `actual > expected`. Customer was charged MORE than the package price.
 *     Manual surcharge that was never logged as a `manual_override`.
 *
 *   BILLING_CYCLE_UNRESOLVABLE
 *     `cycle.billing_cycle` is not a known enum value. Can't resolve expected
 *     price; data is corrupt at a more fundamental level.
 *
 *   ORPHAN_SUBSCRIPTION
 *     Cycle's subscribable row is missing (deleted).
 *
 *   ORPHAN_PACKAGE
 *     No `package_snapshot`, no live package (sub.package() returns null).
 *
 *   UNKNOWN_DELTA
 *     None of the above patterns match. Manual review.
 *
 * Output:
 *   - Console summary table (counts per signature + total)
 *   - Per-row CSV at storage/app/subscriptions/pricing-trust-report-{ts}.csv
 *
 * Strictly read-only. The admin uses the CSV to decide per-class remediation;
 * no row is changed by this command.
 */
class AuditPricingTrustReportCommand extends Command
{
    protected $signature = 'subscriptions:audit-pricing-trust-report
                            {--academy= : Restrict to a single academy id}
                            {--signature= : Restrict CSV output to one signature (filter, not aggregator)}';

    protected $description = 'INV-D2 classifier — categorize pricing-trust violations into actionable signatures.';

    private const SIGNATURES = [
        'DRIFT_FROM_PACKAGE_PRICE',
        'SNAPSHOT_MISSING_PRICE_FIELDS',
        'DISCOUNT_NOT_RECORDED',
        'DISCOUNT_NOT_APPLIED',
        'FREE_NOT_OVERRIDE',
        'OVERCHARGE_NOT_OVERRIDE',
        'BILLING_CYCLE_UNRESOLVABLE',
        'ORPHAN_SUBSCRIPTION',
        'ORPHAN_PACKAGE',
        'UNKNOWN_DELTA',
    ];

    /**
     * Snapshot keys we consider to contain price information. If a recorded
     * `package_snapshot` carries none of these, it's a partial snapshot
     * (id+name+sessions only) — `PricingResolver` will fall through to 0 and
     * generate false-positive INV-D2 hits.
     */
    private const SNAPSHOT_PRICE_KEYS = [
        'price', 'monthly_price', 'quarterly_price', 'yearly_price',
        'sale_monthly_price', 'sale_quarterly_price', 'sale_yearly_price',
        'final_price',
    ];

    public function handle(): int
    {
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;
        $filterSignature = $this->option('signature');
        if ($filterSignature !== null && ! in_array($filterSignature, self::SIGNATURES, true)) {
            $this->error('Unknown --signature='.$filterSignature.'. Allowed: '.implode(',', self::SIGNATURES));

            return self::FAILURE;
        }

        // `withoutGlobalScopes()` deliberately removes the academy scope so
        // super-admin runs see every tenant, but we still need to honor the
        // SoftDeletes scope — a soft-deleted cycle is intentionally hidden
        // and shouldn't keep showing up as a violation (e.g., the 34 orphan
        // cycles soft-deleted via `fix-orphan-cycle-soft-delete`).
        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('pricing_source', 'package');

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        $counts = array_fill_keys(self::SIGNATURES, 0);
        $rows = [];
        $examined = 0;
        $clean = 0;

        $query->orderBy('id')->chunkById(500, function ($chunk) use (&$counts, &$rows, &$examined, &$clean, $filterSignature) {
            $byMorph = $chunk->groupBy('subscribable_type');
            $subscribablesByKey = [];

            foreach ($byMorph as $morphAlias => $cycles) {
                $modelClass = $this->resolveSubscribableClass($morphAlias);
                if ($modelClass === null) {
                    continue;
                }
                $ids = $cycles->pluck('subscribable_id')->unique()->all();
                $hydrated = $modelClass::withoutGlobalScopes()
                    ->with(['package'])
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                foreach ($hydrated as $id => $row) {
                    $subscribablesByKey[$morphAlias.':'.$id] = $row;
                }
            }

            foreach ($chunk as $cycle) {
                $examined++;
                $key = $cycle->subscribable_type.':'.$cycle->subscribable_id;
                $sub = $subscribablesByKey[$key] ?? null;

                $classified = $this->classify($cycle, $sub);

                if ($classified === null) {
                    $clean++;

                    continue;
                }

                $counts[$classified['signature']]++;

                if ($filterSignature !== null && $classified['signature'] !== $filterSignature) {
                    continue;
                }

                $rows[] = [
                    'cycle_id' => $cycle->id,
                    'sub_id' => $cycle->subscribable_id,
                    'sub_type' => $cycle->subscribable_type,
                    'academy_id' => $cycle->academy_id,
                    'signature' => $classified['signature'],
                    'actual_final_price' => $classified['actual'],
                    'expected_final_price' => $classified['expected'],
                    'delta' => $classified['delta'],
                    'discount_amount' => (float) ($cycle->discount_amount ?? 0),
                    'has_snapshot' => $classified['has_snapshot'] ? 'yes' : 'no',
                    'has_live_package' => $classified['has_live_package'] ? 'yes' : 'no',
                    'snapshot_price' => $classified['snapshot_price'],
                    'live_price' => $classified['live_price'],
                    'billing_cycle' => (string) $cycle->billing_cycle,
                    'currency' => (string) $cycle->currency,
                    'cycle_state' => (string) $cycle->cycle_state,
                    'payment_status' => (string) $cycle->payment_status,
                    'created_at' => optional($cycle->created_at)->toDateTimeString(),
                    'recommendation' => $this->recommendationFor($classified['signature']),
                ];
            }
        });

        $this->info(sprintf('Examined: %d cycle(s) with pricing_source=package', $examined));
        $this->info(sprintf('Clean (INV-D2 satisfied): %d', $clean));
        $total = array_sum($counts);
        $this->warn(sprintf('Violations: %d', $total));

        $tableRows = [];
        foreach ($counts as $sig => $n) {
            $tableRows[] = [$sig, $n, $this->recommendationFor($sig)];
        }
        $this->table(['Signature', 'Count', 'Recommended action'], $tableRows);

        if ($rows === []) {
            $this->info('No rows match the filter; CSV not written.');

            return self::SUCCESS;
        }

        $path = $this->writeCsv($rows);
        $this->info("Per-row CSV written to: $path");

        return self::SUCCESS;
    }

    /**
     * Returns ['signature' => string, 'actual' => float, 'expected' => float|null,
     *          'delta' => float|null, 'has_snapshot' => bool, 'has_live_package' => bool,
     *          'snapshot_price' => float|null, 'live_price' => float|null]
     * or null when INV-D2 holds.
     */
    private function classify(SubscriptionCycle $cycle, ?object $sub): ?array
    {
        $actual = round((float) $cycle->final_price, 2);
        $discount = round((float) ($cycle->discount_amount ?? 0), 2);
        $hasSnapshot = is_array($cycle->package_snapshot) && ! empty($cycle->package_snapshot);
        $snapshotHasPrice = $hasSnapshot
            && count(array_intersect(self::SNAPSHOT_PRICE_KEYS, array_keys($cycle->package_snapshot))) > 0
            && count(array_filter(
                array_intersect_key($cycle->package_snapshot, array_flip(self::SNAPSHOT_PRICE_KEYS)),
                static fn ($v) => $v !== null,
            )) > 0;
        $livePackage = is_object($sub) ? ($sub->package ?? null) : null;
        $hasLivePackage = $livePackage !== null;

        if ($sub === null) {
            return [
                'signature' => 'ORPHAN_SUBSCRIPTION',
                'actual' => $actual,
                'expected' => null,
                'delta' => null,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => false,
                'snapshot_price' => null,
                'live_price' => null,
            ];
        }

        $billingCycle = $cycle->billing_cycle instanceof BillingCycle
            ? $cycle->billing_cycle
            : BillingCycle::tryFrom((string) $cycle->billing_cycle);

        if ($billingCycle === null) {
            return [
                'signature' => 'BILLING_CYCLE_UNRESOLVABLE',
                'actual' => $actual,
                'expected' => null,
                'delta' => null,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => null,
                'live_price' => null,
            ];
        }

        $snapshotPrice = null;
        if ($snapshotHasPrice) {
            $base = PricingResolver::resolvePriceFromPackage($cycle->package_snapshot, $billingCycle);
            $snapshotPrice = round((float) $base, 2);
        }
        $livePrice = null;
        if ($hasLivePackage) {
            $base = PricingResolver::resolvePriceFromPackage($livePackage, $billingCycle);
            $livePrice = round((float) $base, 2);
        }

        if ($snapshotPrice === null && $livePrice === null) {
            return [
                'signature' => 'ORPHAN_PACKAGE',
                'actual' => $actual,
                'expected' => null,
                'delta' => null,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => null,
                'live_price' => null,
            ];
        }

        // Snapshot present but partial (no price fields). Treat as drift —
        // the cycle's final_price IS the historical truth; the snapshot just
        // needs to be backfilled with the price fields it never carried.
        // This pattern dominates pre-v2 cycles where the snapshot was
        // serialized with only structural fields (id/name/sessions/duration).
        if ($hasSnapshot && ! $snapshotHasPrice) {
            return [
                'signature' => 'SNAPSHOT_MISSING_PRICE_FIELDS',
                'actual' => $actual,
                'expected' => null,
                'delta' => null,
                'has_snapshot' => true,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => null,
                'live_price' => $livePrice,
            ];
        }

        // Match the checker's preference: snapshot > live.
        $expectedBase = $snapshotPrice ?? $livePrice;
        $expected = round((float) $expectedBase - $discount, 2);

        if (abs($actual - $expected) < 0.01) {
            // INV-D2 satisfied.
            return null;
        }

        $delta = round($expected - $actual, 2);

        // FREE_NOT_OVERRIDE: actual=0, expected>0. Unambiguous regardless of
        // snapshot — a paid package was rendered free without an override tag.
        if ($actual === 0.0 && $expected > 0.0) {
            return [
                'signature' => 'FREE_NOT_OVERRIDE',
                'actual' => $actual,
                'expected' => $expected,
                'delta' => $delta,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => $snapshotPrice,
                'live_price' => $livePrice,
            ];
        }

        // DRIFT_FROM_PACKAGE_PRICE: snapshot is missing so the only "expected"
        // signal is the live package, which can have changed in EITHER direction
        // since the cycle was created. Without a snapshot we can't tell drift
        // from overcharge/underpayment, so we classify the whole class as drift
        // — the fix is the same (backfill the snapshot from final_price).
        if (! $hasSnapshot && $hasLivePackage) {
            return [
                'signature' => 'DRIFT_FROM_PACKAGE_PRICE',
                'actual' => $actual,
                'expected' => $expected,
                'delta' => $delta,
                'has_snapshot' => false,
                'has_live_package' => true,
                'snapshot_price' => null,
                'live_price' => $livePrice,
            ];
        }

        // DISCOUNT_NOT_APPLIED: discount column set, but actual = expected_base
        // (i.e., the discount was recorded but never subtracted from
        // final_price). Snapshot present so we trust the comparison.
        if ($discount > 0.0 && $expectedBase !== null && abs($actual - (float) $expectedBase) < 0.01) {
            return [
                'signature' => 'DISCOUNT_NOT_APPLIED',
                'actual' => $actual,
                'expected' => $expected,
                'delta' => $delta,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => $snapshotPrice,
                'live_price' => $livePrice,
            ];
        }

        // From here we have a snapshot — the comparison is trustworthy.
        // OVERCHARGE: actual > expected (genuine surcharge).
        if ($actual > $expected) {
            return [
                'signature' => 'OVERCHARGE_NOT_OVERRIDE',
                'actual' => $actual,
                'expected' => $expected,
                'delta' => $delta,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => $snapshotPrice,
                'live_price' => $livePrice,
            ];
        }

        // DISCOUNT_NOT_RECORDED: actual < expected, no discount column set,
        // delta is positive (i.e. customer paid less than the package).
        if ($actual < $expected && $discount === 0.0 && $delta > 0) {
            return [
                'signature' => 'DISCOUNT_NOT_RECORDED',
                'actual' => $actual,
                'expected' => $expected,
                'delta' => $delta,
                'has_snapshot' => $hasSnapshot,
                'has_live_package' => $hasLivePackage,
                'snapshot_price' => $snapshotPrice,
                'live_price' => $livePrice,
            ];
        }

        return [
            'signature' => 'UNKNOWN_DELTA',
            'actual' => $actual,
            'expected' => $expected,
            'delta' => $delta,
            'has_snapshot' => $hasSnapshot,
            'has_live_package' => $hasLivePackage,
            'snapshot_price' => $snapshotPrice,
            'live_price' => $livePrice,
        ];
    }

    private function recommendationFor(string $signature): string
    {
        return match ($signature) {
            'DRIFT_FROM_PACKAGE_PRICE' => 'Backfill package_snapshot from cycle final_price+discount (preserves history; suppresses false positive).',
            'SNAPSHOT_MISSING_PRICE_FIELDS' => 'Snapshot has structural fields but no price keys. Backfill price into package_snapshot from cycle final_price+discount.',
            'DISCOUNT_NOT_RECORDED' => 'Admin per-case: set discount_amount = expected - actual; or flip to sale_price with reason.',
            'DISCOUNT_NOT_APPLIED' => 'Admin per-case: either zero discount_amount or rewrite final_price (data quality issue).',
            'FREE_NOT_OVERRIDE' => 'Flip pricing_source -> manual_override with reason (sponsorship/trial gift); record actor.',
            'OVERCHARGE_NOT_OVERRIDE' => 'Flip pricing_source -> manual_override with reason (surcharge); record actor.',
            'BILLING_CYCLE_UNRESOLVABLE' => 'Data corruption — investigate billing_cycle column directly.',
            'ORPHAN_SUBSCRIPTION' => 'Soft-delete cycle (subscribable row is missing).',
            'ORPHAN_PACKAGE' => 'INV-D4 (separate) — backfill package_id or accept as terminal cycle.',
            'UNKNOWN_DELTA' => 'Manual review — does not match any known signature.',
        };
    }

    /**
     * @return class-string|null
     */
    private function resolveSubscribableClass(string $morphAlias): ?string
    {
        return match ($morphAlias) {
            (new QuranSubscription)->getMorphClass() => QuranSubscription::class,
            (new AcademicSubscription)->getMorphClass() => AcademicSubscription::class,
            (new CourseSubscription)->getMorphClass() => CourseSubscription::class,
            default => null,
        };
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     */
    private function writeCsv(array $rows): string
    {
        $timestamp = Carbon::now()->format('Ymd-His');
        $relPath = "subscriptions/pricing-trust-report-{$timestamp}.csv";

        $header = array_keys($rows[0]);
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $header);
        foreach ($rows as $r) {
            fputcsv($stream, array_map(static fn ($v) => $v === null ? '' : (string) $v, $r));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        Storage::disk('local')->put($relPath, $csv);

        return Storage::disk('local')->path($relPath);
    }
}
