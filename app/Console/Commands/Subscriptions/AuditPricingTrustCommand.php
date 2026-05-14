<?php

namespace App\Console\Commands\Subscriptions;

use App\Enums\BillingCycle;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\PricingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Phase A.6 — Sharouq-shaped audit (R4).
 *
 * Read-only sweep over every subscription_cycle: for rows where
 * `pricing_source = 'package'` (i.e. the row claims its price was resolved
 * straight off the package), recompute the expected price via
 *   PricingResolver::resolvePriceFromPackage($package, $billingCycle) - $discount_amount
 * and compare against the stored `final_price`.
 *
 * A non-zero diff means one of:
 *   - the cycle was charged a sale price but the row was never tagged
 *     `pricing_source = 'sale_price'`, or
 *   - the cycle was manually overridden but never tagged
 *     `pricing_source = 'manual_override'`, or
 *   - the package price changed *after* the cycle was created and the cycle
 *     was never re-snapshotted (INV-D4 says this is fine — historical cycles
 *     keep their old final_price — but the audit surfaces it so operators can
 *     reclassify deliberately).
 *
 * Output:
 *   - console table (truncated to first 50 rows when noisy)
 *   - JSON file at storage/app/subscriptions/pricing-audit-{timestamp}.json
 *     containing the full row set
 *
 * Per the recovery plan, every legacy sub that fails this check is a Sharouq
 * candidate. The command does NOT mutate anything; operators triage from the
 * JSON.
 *
 * Usage:
 *   php artisan subscriptions:audit-pricing-trust
 *   php artisan subscriptions:audit-pricing-trust --academy=42
 */
class AuditPricingTrustCommand extends Command
{
    protected $signature = 'subscriptions:audit-pricing-trust
                            {--academy= : Restrict to a single academy id}';

    protected $description = 'INV-D2 audit — flag cycles whose final_price disagrees with PricingResolver while still claiming pricing_source=package';

    /**
     * Console truncation threshold — JSON always contains the full set.
     */
    private const CONSOLE_ROW_LIMIT = 50;

    public function handle(): int
    {
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->where('pricing_source', 'package');

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        $violations = [];
        $examined = 0;

        // Stream in chunks — at production scale there can be tens of thousands
        // of cycles. Lazy iteration keeps memory bounded.
        $query->orderBy('id')->chunkById(500, function ($chunk) use (&$violations, &$examined) {
            // Hydrate subscribables in batch so we don't re-query per cycle.
            $byMorph = $chunk->groupBy('subscribable_type');
            $subscribablesByKey = [];

            foreach ($byMorph as $morphAlias => $cycles) {
                $modelClass = $this->resolveSubscribableClass($morphAlias);
                if ($modelClass === null) {
                    continue;
                }
                $ids = $cycles->pluck('subscribable_id')->unique()->all();
                $rows = $modelClass::withoutGlobalScopes()
                    ->with(['package'])
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $id => $row) {
                    $subscribablesByKey[$morphAlias.':'.$id] = $row;
                }
            }

            foreach ($chunk as $cycle) {
                $examined++;
                $key = $cycle->subscribable_type.':'.$cycle->subscribable_id;
                $sub = $subscribablesByKey[$key] ?? null;
                if ($sub === null) {
                    $violations[] = [
                        'cycle_id' => $cycle->id,
                        'subscription_id' => $cycle->subscribable_id,
                        'subscribable_type' => $cycle->subscribable_type,
                        'expected' => null,
                        'actual' => (float) $cycle->final_price,
                        'diff' => null,
                        'note' => 'subscribable missing — cannot recompute',
                    ];

                    continue;
                }

                $package = $sub->package;
                if ($package === null) {
                    $violations[] = [
                        'cycle_id' => $cycle->id,
                        'subscription_id' => $cycle->subscribable_id,
                        'subscribable_type' => $cycle->subscribable_type,
                        'expected' => null,
                        'actual' => (float) $cycle->final_price,
                        'diff' => null,
                        'note' => 'package missing — cannot recompute (orphan)',
                    ];

                    continue;
                }

                $billingCycle = BillingCycle::tryFrom((string) $cycle->billing_cycle);
                if ($billingCycle === null) {
                    $violations[] = [
                        'cycle_id' => $cycle->id,
                        'subscription_id' => $cycle->subscribable_id,
                        'subscribable_type' => $cycle->subscribable_type,
                        'expected' => null,
                        'actual' => (float) $cycle->final_price,
                        'diff' => null,
                        'note' => 'unrecognised billing_cycle="'.$cycle->billing_cycle.'"',
                    ];

                    continue;
                }

                $base = PricingResolver::resolvePriceFromPackage($package, $billingCycle);
                $expected = round($base - (float) $cycle->discount_amount, 2);
                $actual = round((float) $cycle->final_price, 2);
                $diff = round($actual - $expected, 2);

                if (abs($diff) < 0.005) {
                    continue;
                }

                $violations[] = [
                    'cycle_id' => $cycle->id,
                    'subscription_id' => $cycle->subscribable_id,
                    'subscribable_type' => $cycle->subscribable_type,
                    'academy_id' => $cycle->academy_id,
                    'billing_cycle' => $cycle->billing_cycle,
                    'package_id' => $cycle->package_id,
                    'discount_amount' => (float) $cycle->discount_amount,
                    'expected' => $expected,
                    'actual' => $actual,
                    'diff' => $diff,
                    'currency' => $cycle->currency,
                    'cycle_state' => $cycle->cycle_state,
                    'payment_status' => $cycle->payment_status,
                ];
            }
        });

        $this->info(sprintf('Examined %d cycle(s) tagged pricing_source=package.', $examined));

        if (empty($violations)) {
            $this->info('INV-D2 holds for every examined cycle.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d violation(s) found.', count($violations)));

        $preview = array_slice($violations, 0, self::CONSOLE_ROW_LIMIT);
        $this->table(
            ['cycle_id', 'sub_id', 'morph', 'expected', 'actual', 'diff', 'note'],
            array_map(static function (array $r): array {
                return [
                    $r['cycle_id'],
                    $r['subscription_id'],
                    $r['subscribable_type'],
                    $r['expected'] ?? '—',
                    $r['actual'],
                    $r['diff'] ?? '—',
                    $r['note'] ?? '',
                ];
            }, $preview),
        );

        if (count($violations) > self::CONSOLE_ROW_LIMIT) {
            $this->warn(sprintf('(showing first %d; full set written to JSON)', self::CONSOLE_ROW_LIMIT));
        }

        $path = $this->writeJson($violations, $examined, $academyId);
        $this->info("Full audit written to: $path");

        return self::SUCCESS;
    }

    /**
     * @return class-string|null
     */
    private function resolveSubscribableClass(string $morphAlias): ?string
    {
        return match ($morphAlias) {
            (new QuranSubscription)->getMorphClass() => QuranSubscription::class,
            (new AcademicSubscription)->getMorphClass() => AcademicSubscription::class,
            default => null,  // CourseSubscription doesn't have a package() relation.
        };
    }

    /**
     * @param  list<array<string,mixed>>  $violations
     */
    private function writeJson(array $violations, int $examined, ?int $academyId): string
    {
        $timestamp = Carbon::now()->format('Ymd-His');
        $relPath = "subscriptions/pricing-audit-{$timestamp}.json";

        $payload = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'academy_filter' => $academyId,
            'examined_count' => $examined,
            'violation_count' => count($violations),
            'violations' => $violations,
        ];

        Storage::disk('local')->put($relPath, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ));

        return Storage::disk('local')->path($relPath);
    }
}
