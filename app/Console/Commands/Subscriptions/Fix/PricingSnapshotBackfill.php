<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\BillingCycle;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\CourseSubscription;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\PricingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * INV-D2 auto-fix — narrow scope: only patch cycles whose data is internally
 * consistent (snapshot specs match live package, final_price matches one of
 * the live prices). Everything else is quarantined and printed for admin
 * review, not auto-applied.
 *
 * ## Why narrow scope
 *
 * Two seemingly-similar shapes hide very different root causes:
 *
 *   1. "Snapshot has structural fields (id/name/sessions/duration) matching
 *      the live package; just missing the price keys." → safe: the cycle was
 *      genuinely on this package, the writer just forgot to serialize price.
 *      Write the live package's prices into the snapshot; INV-D2 holds; no
 *      history is altered.
 *
 *   2. "Snapshot/sub.package_id points at package X, but final_price doesn't
 *      match any of X's prices — yet it DOES match some OTHER academy
 *      package's price." → unsafe: the sub was likely renewed onto a
 *      different package, and `package_id` got rewritten without the cycle
 *      being archived under the old package. Writing snapshot.<bc>_price =
 *      final_price into this cycle would cement the wrong package as the
 *      historical truth, hiding the real bug.
 *
 * Shape 2 was discovered after a complaint about sub 599: the cycle's
 * snapshot had no price keys, sub.package_id points to package #14 (16
 * sessions × 60 min, sale 400), but an earlier (archived) cycle paid 150 —
 * which matches packages #7/#41 etc., not #14. The archived cycle was
 * originally on a smaller package; the renewal flipped package_id but never
 * preserved the old package on the archived cycle.
 *
 * ## Buckets the writer uses
 *
 *   A_CONSISTENT_MISSING_PRICE_FIELD  → auto-apply. Snapshot specs match
 *     live package; final_price matches live regular or sale.
 *   C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS → quarantine. Snapshot has different
 *     sessions/duration than sub.package — the snapshot is from an older
 *     different package.
 *   D_NO_SNAPSHOT_FINAL_MATCHES_ANOTHER_PKG → quarantine. No snapshot, and
 *     final_price matches at least one OTHER academy package — that's
 *     probably the original package, and the current package_id is wrong.
 *   E_NO_SNAPSHOT_NO_MATCH → quarantine. No snapshot, no other package
 *     matches — likely a manual override or legacy price that no longer
 *     exists. Needs admin reason+actor capture.
 *
 * ## Safety properties for Bucket A
 *
 *   - Student / supervisor surfaces show `cycle.final_price` directly
 *     (student/subscriptions.blade.php:57,112,170, supervisor/.../show.blade
 *     .php:205,319). The snapshot is invisible to humans.
 *   - Next-cycle renewal re-resolves from the LIVE package (sale honored)
 *     in SubscriptionRenewalService::resolvePricing() lines 606–611. Writing
 *     into the historical snapshot doesn't affect the next cycle.
 *   - We copy ALL live price fields (`<bc>_price`, `sale_<bc>_price`, etc.)
 *     into the snapshot — preserves the regular/sale distinction. Resolver
 *     prefers sale, so its return value matches what the student actually
 *     paid (since this is Bucket A: final_price matched one of those prices).
 *
 * BackfillLog row per write keyed `bug_id='inv-d2-snapshot'` for rollback.
 */
class PricingSnapshotBackfill extends Command
{
    protected $signature = 'subscriptions:fix-pricing-snapshot-backfill
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}
                            {--academy= : Restrict to a single academy id}
                            {--preventive : Also patch cycles that are currently INV-D2 clean but lack a price-bearing snapshot (drift protection)}
                            {--quarantine-csv : Write quarantined C/D/E cycles to storage/app/subscriptions/snapshot-quarantine-{ts}.csv}';

    protected $description = 'INV-D2 auto-fix: backfill package_snapshot.price from cycle.final_price for DRIFT + SNAPSHOT_MISSING cycles.';

    private const SNAPSHOT_PRICE_KEYS = [
        'price', 'monthly_price', 'quarterly_price', 'yearly_price',
        'sale_monthly_price', 'sale_quarterly_price', 'sale_yearly_price',
        'final_price',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;
        $preventive = (bool) $this->option('preventive');
        $writeQuarantineCsv = (bool) $this->option('quarantine-csv');

        $cycles = $this->fetchCandidates($academyId);

        // Hydrate parent subscriptions for the live-package fallback used when
        // checking INV-D2 violation membership.
        $subscribablesByKey = $this->hydrateSubscribables($cycles);

        // Preload all packages for academy-wide matching during quarantine
        // classification (D bucket needs to scan all academy packages).
        $packagesByAcademy = $this->preloadPackages();

        $eligible = [];
        $quarantine = [];
        foreach ($cycles as $cycle) {
            $key = $cycle->subscribable_type.':'.$cycle->subscribable_id;
            $sub = $subscribablesByKey[$key] ?? null;

            $result = $this->classifyCycle($cycle, $sub, $packagesByAcademy, $preventive);
            if ($result === null) {
                continue;
            }
            // Bucket A and the auto-fixable variant of Bucket C both carry a
            // pre-built `new_snapshot`; either is safe to apply. Anything
            // without `new_snapshot` is quarantined.
            if (in_array($result['bucket'], ['A_CONSISTENT_MISSING_PRICE_FIELD', 'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS'], true)
                && isset($result['new_snapshot'])) {
                $eligible[] = $result;
            } else {
                $quarantine[] = $result;
            }
        }

        // === Quarantine summary FIRST (so the admin sees what's NOT touched).
        if ($quarantine !== []) {
            $this->warn(sprintf('Quarantined (NOT auto-fixed): %d cycle(s) — admin review required.', count($quarantine)));
            $byBucket = [];
            foreach ($quarantine as $q) {
                $byBucket[$q['bucket']] = ($byBucket[$q['bucket']] ?? 0) + 1;
            }
            $this->table(
                ['Quarantine bucket', 'Count', 'Why'],
                array_map(fn ($k) => [$k, $byBucket[$k], $this->bucketWhy($k)], array_keys($byBucket)),
            );

            // First 5 of each quarantine bucket so admin sees the shape.
            foreach (array_keys($byBucket) as $bucket) {
                $samples = array_values(array_filter($quarantine, fn ($q) => $q['bucket'] === $bucket));
                $this->line('');
                $this->line(sprintf('-- %s sample (up to 5) --', $bucket));
                $this->table(
                    ['cycle', 'sub', 'final', 'live_pkg', 'live_sessions', 'live_dur', 'live_regular', 'live_sale', 'snap_id', 'snap_sessions', 'snap_dur', 'matching_pkgs'],
                    array_map(fn ($q) => [
                        $q['cycle_id'], $q['sub_id'], $q['final_price'],
                        $q['live_pkg_id'] ?? '—', $q['live_sessions'] ?? '—', $q['live_duration'] ?? '—',
                        $q['live_regular'] ?? '—', $q['live_sale'] ?? '—',
                        $q['snap_id'] ?? '—', $q['snap_sessions'] ?? '—', $q['snap_duration'] ?? '—',
                        $q['matching_pkgs'] === [] ? '—' : implode('|', $q['matching_pkgs']),
                    ], array_slice($samples, 0, 5)),
                );
            }

            if ($writeQuarantineCsv) {
                $path = $this->writeQuarantineCsv($quarantine);
                $this->line('');
                $this->info('Quarantine CSV written to: '.$path);
            }
            $this->line('');
        }

        $eligibleByBucket = [];
        foreach ($eligible as $e) {
            $eligibleByBucket[$e['bucket']] = ($eligibleByBucket[$e['bucket']] ?? 0) + 1;
        }
        $this->info(sprintf('Auto-fixable: %d cycle(s)', count($eligible)));
        $this->table(
            ['Bucket', 'Count'],
            array_map(fn ($k, $v) => [$k, $v], array_keys($eligibleByBucket), array_values($eligibleByBucket)),
        );

        if ($eligible === []) {
            $this->info('Nothing to auto-apply.');

            return self::SUCCESS;
        }

        if ($limit !== null && count($eligible) > $limit) {
            $eligible = array_slice($eligible, 0, $limit);
        }

        // Show a preview sample so the operator sees what's about to change.
        $previewLimit = min(10, count($eligible));
        $this->line('');
        $this->line(sprintf('Preview (first %d of %d auto-fix candidates):', $previewLimit, count($eligible)));
        $this->table(
            ['cycle_id', 'sub_id', 'bucket', 'final', 'discount', 'bc', 'live_reg', 'live_sale', 'paid_kind', 'snapshot_was'],
            array_map(static function (array $p): array {
                return [
                    $p['cycle_id'],
                    $p['sub_id'],
                    str_replace(['A_CONSISTENT_MISSING_PRICE_FIELD', 'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS'], ['A', 'C'], $p['bucket']),
                    $p['final_price'],
                    $p['discount'],
                    $p['billing_cycle'],
                    $p['live_regular'] ?? '—',
                    $p['live_sale'] ?? '—',
                    $p['paid_kind'] ?? '—',
                    $p['snapshot_was'] === [] ? 'NULL/empty' : 'has '.count($p['snapshot_was']).' keys (no price)',
                ];
            }, array_slice($eligible, 0, $previewLimit)),
        );

        if (! $apply) {
            $this->line('');
            $this->comment('DRY-RUN. Re-run with --apply to perform the writes. Each cycle gets a BackfillLog row keyed bug_id=inv-d2-snapshot.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($eligible));
        $bar->start();
        $touched = 0;
        $errors = 0;

        foreach ($eligible as $plan) {
            try {
                DB::transaction(fn () => $this->applyPlan($plan));
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\ncycle #%d: %s", $plan['cycle_id'], $e->getMessage()));
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');

        $this->info(sprintf('APPLIED: %d cycle(s) written; %d error(s).', $touched, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubscriptionCycle>
     */
    private function fetchCandidates(?int $academyId): \Illuminate\Support\Collection
    {
        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->where('pricing_source', 'package');

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @return array<string, object>
     */
    private function hydrateSubscribables(\Illuminate\Support\Collection $cycles): array
    {
        $byMorph = $cycles->groupBy('subscribable_type');
        $out = [];

        foreach ($byMorph as $morphAlias => $group) {
            $modelClass = match ($morphAlias) {
                (new QuranSubscription)->getMorphClass() => QuranSubscription::class,
                (new AcademicSubscription)->getMorphClass() => AcademicSubscription::class,
                (new CourseSubscription)->getMorphClass() => CourseSubscription::class,
                default => null,
            };
            if ($modelClass === null) {
                continue;
            }
            $ids = $group->pluck('subscribable_id')->unique()->all();
            $rows = $modelClass::withoutGlobalScopes()
                ->with(['package'])
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');
            foreach ($rows as $id => $row) {
                $out[$morphAlias.':'.$id] = $row;
            }
        }

        return $out;
    }

    /**
     * Classify each cycle into Bucket A (safe auto-fix) or C/D/E (quarantine).
     * Returns null when the cycle should be entirely skipped (clean / orphan /
     * free / etc).
     *
     * @param  array<int, \Illuminate\Support\Collection>  $packagesByAcademy
     */
    private function classifyCycle(
        SubscriptionCycle $cycle,
        ?object $sub,
        array $packagesByAcademy,
        bool $preventive,
    ): ?array {
        if ($sub === null) {
            return null; // ORPHAN_SUBSCRIPTION — separate cleanup
        }

        $billingCycle = $cycle->billing_cycle instanceof BillingCycle
            ? $cycle->billing_cycle
            : BillingCycle::tryFrom((string) $cycle->billing_cycle);
        if ($billingCycle === null) {
            return null;
        }

        $snapshot = $cycle->package_snapshot;
        $hasSnapshot = is_array($snapshot) && ! empty($snapshot);
        $snapshotHasPrice = $hasSnapshot
            && count(array_filter(
                array_intersect_key($snapshot, array_flip(self::SNAPSHOT_PRICE_KEYS)),
                static fn ($v) => $v !== null,
            )) > 0;

        // Skip cycles whose snapshot already has price keys — handled
        // (correctly or incorrectly) elsewhere. We never touch snapshots
        // that carry their own price.
        if ($snapshotHasPrice) {
            return null;
        }

        $livePackage = $sub->package ?? null;
        if (! $hasSnapshot && $livePackage === null) {
            return null; // ORPHAN_PACKAGE — separate cleanup
        }

        $finalPrice = round((float) $cycle->final_price, 2);
        $discount = round((float) ($cycle->discount_amount ?? 0), 2);

        $livePrice = $livePackage !== null
            ? PricingResolver::resolvePriceFromPackage($livePackage, $billingCycle)
            : 0.0;

        // FREE_NOT_OVERRIDE: paid=0, live>0 — admin signal must remain.
        if ($finalPrice === 0.0 && (float) $livePrice > 0.0) {
            return null;
        }

        // Default mode: only target cycles currently violating INV-D2. Mirror
        // the checker semantics (snapshot preferred, partial snapshot
        // resolves to 0 → still a mismatch).
        if (! $preventive) {
            $basePrice = $hasSnapshot
                ? PricingResolver::resolvePriceFromPackage($snapshot, $billingCycle)
                : (float) $livePrice;
            $expected = (float) $basePrice - $discount;
            if (abs($finalPrice - $expected) < 0.01) {
                return null;
            }
        }

        // === Bucket classification ===
        $base = [
            'cycle_id' => (int) $cycle->id,
            'sub_id' => (int) $cycle->subscribable_id,
            'sub_type' => (string) $cycle->subscribable_type,
            'final_price' => $finalPrice,
            'discount' => $discount,
            'billing_cycle' => $billingCycle->value,
            'live_pkg_id' => $livePackage?->id,
            'live_sessions' => $livePackage?->sessions_per_month,
            'live_duration' => $livePackage?->session_duration_minutes,
            'live_regular' => $livePackage ? (float) $livePackage->{$billingCycle->value.'_price'} : null,
            'live_sale' => $livePackage && $livePackage->{'sale_'.$billingCycle->value.'_price'} !== null
                ? (float) $livePackage->{'sale_'.$billingCycle->value.'_price'}
                : null,
            'snap_id' => $hasSnapshot ? ($snapshot['id'] ?? null) : null,
            'snap_sessions' => $hasSnapshot ? ($snapshot['sessions_per_month'] ?? null) : null,
            'snap_duration' => $hasSnapshot ? ($snapshot['session_duration_minutes'] ?? null) : null,
            'snapshot_was' => is_array($snapshot) ? $snapshot : [],
            'matching_pkgs' => [],
        ];

        if ($hasSnapshot && $livePackage !== null) {
            $specsMatch = ($base['snap_sessions'] == $base['live_sessions'])
                && ($base['snap_duration'] == $base['live_duration']);
            $matchesRegular = $base['live_regular'] !== null
                && abs($finalPrice - ($base['live_regular'] - $discount)) < 0.01;
            $matchesSale = $base['live_sale'] !== null
                && abs($finalPrice - ($base['live_sale'] - $discount)) < 0.01;

            if ($specsMatch && ($matchesRegular || $matchesSale)) {
                // Bucket A — safe auto-fix.
                $base['bucket'] = 'A_CONSISTENT_MISSING_PRICE_FIELD';
                $base['paid_kind'] = $matchesSale ? 'sale' : 'regular';
                $base['new_snapshot'] = $this->buildSnapshotForBucketA($snapshot, $livePackage);
                return $base;
            }

            if ($specsMatch && ! $matchesRegular && ! $matchesSale) {
                $base['bucket'] = 'B_PRICE_DRIFT_WITHIN_SAME_PACKAGE';
                return $base;
            }

            // Bucket C — snapshot specs differ from sub.package_id. The
            // snapshot's own `id` field tells us the historical package. Look
            // it up; if its price matches final_price, this is auto-fixable
            // by reading prices from THAT package (not sub.package_id's live).
            $snapPkgId = $base['snap_id'] ?? null;
            if ($snapPkgId !== null) {
                $snapPkgs = $packagesByAcademy[$cycle->subscribable_type][$sub->academy_id] ?? collect();
                $snapPkg = $snapPkgs->firstWhere('id', $snapPkgId);
                if ($snapPkg !== null) {
                    $snapPkgReg = (float) ($snapPkg->{$billingCycle->value.'_price'} ?? 0);
                    $snapPkgSale = $snapPkg->{'sale_'.$billingCycle->value.'_price'} !== null
                        ? (float) $snapPkg->{'sale_'.$billingCycle->value.'_price'}
                        : null;
                    $matchesSnapPkgReg = abs($finalPrice - ($snapPkgReg - $discount)) < 0.01;
                    $matchesSnapPkgSale = $snapPkgSale !== null
                        && abs($finalPrice - ($snapPkgSale - $discount)) < 0.01;
                    $specsAgree = ((int) $base['snap_sessions'] === (int) $snapPkg->sessions_per_month)
                        && ((int) $base['snap_duration'] === (int) $snapPkg->session_duration_minutes);

                    if ($specsAgree && ($matchesSnapPkgReg || $matchesSnapPkgSale)) {
                        $base['bucket'] = 'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS';
                        $base['paid_kind'] = $matchesSnapPkgSale ? 'sale' : 'regular';
                        $base['live_regular'] = $snapPkgReg;
                        $base['live_sale'] = $snapPkgSale;
                        $base['new_snapshot'] = $this->buildSnapshotForBucketA($snapshot, $snapPkg);
                        return $base;
                    }
                }
            }

            // Snapshot disagrees with both sub.package_id AND its own snap_id
            // package — quarantine (admin review).
            $base['bucket'] = 'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS';
            return $base;
        }

        // No snapshot — search for academy packages whose price matches final.
        $candidates = $packagesByAcademy[$cycle->subscribable_type][$sub->academy_id] ?? collect();
        $matches = [];
        foreach ($candidates as $p) {
            $reg = (float) ($p->{$billingCycle->value.'_price'} ?? 0);
            $sale = $p->{'sale_'.$billingCycle->value.'_price'} !== null
                ? (float) $p->{'sale_'.$billingCycle->value.'_price'}
                : null;
            if (abs($finalPrice + $discount - $reg) < 0.01) {
                $matches[] = $p->id.':'.$p->sessions_per_month.'x'.$p->session_duration_minutes.'(regular)';
                continue;
            }
            if ($sale !== null && abs($finalPrice + $discount - $sale) < 0.01) {
                $matches[] = $p->id.':'.$p->sessions_per_month.'x'.$p->session_duration_minutes.'(sale)';
            }
        }
        $base['matching_pkgs'] = $matches;

        if (count($matches) === 0) {
            $base['bucket'] = 'E_NO_SNAPSHOT_NO_MATCH';
            return $base;
        }

        $base['bucket'] = 'D_NO_SNAPSHOT_FINAL_MATCHES_ANOTHER_PKG';
        return $base;
    }

    /**
     * Build the new snapshot for a Bucket A cycle: preserve existing keys,
     * then copy all live-package price fields so the snapshot fully reflects
     * the package as it stands today (resolver returns sale_price when
     * present, which is what this cycle paid).
     */
    private function buildSnapshotForBucketA(array $snapshot, object $livePackage): array
    {
        $live = $livePackage;
        $priceFields = [
            'monthly_price' => $live->monthly_price ?? null,
            'quarterly_price' => $live->quarterly_price ?? null,
            'yearly_price' => $live->yearly_price ?? null,
            'sale_monthly_price' => $live->sale_monthly_price ?? null,
            'sale_quarterly_price' => $live->sale_quarterly_price ?? null,
            'sale_yearly_price' => $live->sale_yearly_price ?? null,
        ];

        $out = $snapshot;
        foreach ($priceFields as $k => $v) {
            if ($v === null) {
                continue;
            }
            $out[$k] = (float) $v;
        }
        return $out;
    }

    private function bucketWhy(string $bucket): string
    {
        return match ($bucket) {
            'B_PRICE_DRIFT_WITHIN_SAME_PACKAGE' => 'snapshot specs match live, but final_price does not match any current price of that package.',
            'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS' => 'snapshot sessions/duration differ from sub.package — likely sub renewed onto a different package; old cycle still tagged to former package.',
            'D_NO_SNAPSHOT_FINAL_MATCHES_ANOTHER_PKG' => 'no snapshot; final_price matches some OTHER academy package — current package_id likely wrong for this cycle.',
            'E_NO_SNAPSHOT_NO_MATCH' => 'no snapshot; final_price matches no academy package — manual override or legacy pricing.',
            default => '',
        };
    }

    /**
     * @return array<string, array<int, \Illuminate\Support\Collection>>
     */
    private function preloadPackages(): array
    {
        return [
            (new QuranSubscription)->getMorphClass() => QuranPackage::withoutGlobalScopes()->get()->groupBy('academy_id')->all(),
            (new AcademicSubscription)->getMorphClass() => AcademicPackage::withoutGlobalScopes()->get()->groupBy('academy_id')->all(),
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     */
    private function writeQuarantineCsv(array $rows): string
    {
        $timestamp = Carbon::now()->format('Ymd-His');
        $relPath = "subscriptions/snapshot-quarantine-{$timestamp}.csv";

        $header = ['cycle_id', 'sub_id', 'sub_type', 'bucket', 'final_price', 'discount', 'billing_cycle',
            'live_pkg_id', 'live_sessions', 'live_duration', 'live_regular', 'live_sale',
            'snap_id', 'snap_sessions', 'snap_duration', 'snapshot_was', 'matching_pkgs'];

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $header);
        foreach ($rows as $r) {
            fputcsv($stream, [
                $r['cycle_id'], $r['sub_id'], $r['sub_type'], $r['bucket'],
                $r['final_price'], $r['discount'], $r['billing_cycle'],
                $r['live_pkg_id'] ?? '', $r['live_sessions'] ?? '', $r['live_duration'] ?? '',
                $r['live_regular'] ?? '', $r['live_sale'] ?? '',
                $r['snap_id'] ?? '', $r['snap_sessions'] ?? '', $r['snap_duration'] ?? '',
                json_encode($r['snapshot_was'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                implode('|', $r['matching_pkgs']),
            ]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        Storage::disk('local')->put($relPath, $csv);
        return Storage::disk('local')->path($relPath);
    }

    private function applyPlan(array $plan): void
    {
        $bugId = $plan['bucket'] === 'C_PACKAGE_MISMATCH_SNAPSHOT_DIFFERS'
            ? 'inv-d2-snapshot-bucket-c'
            : 'inv-d2-snapshot-bucket-a';

        BackfillLog::create([
            'bug_id' => $bugId,
            'table_name' => 'subscription_cycles',
            'row_id' => $plan['cycle_id'],
            'column_name' => 'package_snapshot',
            'original_value' => json_encode($plan['snapshot_was'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_value' => json_encode($plan['new_snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'backfill_command' => 'subscriptions:fix-pricing-snapshot-backfill',
            'ran_at' => Carbon::now(),
        ]);

        DB::table('subscription_cycles')
            ->where('id', $plan['cycle_id'])
            ->update([
                'package_snapshot' => json_encode($plan['new_snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => Carbon::now(),
            ]);
    }
}
