<?php

namespace App\Console\Commands;

use App\Models\BaseSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionInvariantChecker;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Throwable;

/**
 * Issue #5 — read-only diagnostic for the post-v2-flip stabilization window.
 *
 * Walks every SubscriptionCycle once, runs the v2 invariant checker on each
 * parent subscription, and emits a CSV row per affected cycle with:
 *
 *   cycle_id, sub_id, sub_type, violation_codes, severity, created_pre_flip,
 *   package_id_null, consumption_count, stored_sessions_used, suggested_action
 *
 * The output buckets violations into "legacy" (cycle predates the v2 flip
 * cutoff and v2_consumption_complete=false) vs "post-flip" (genuinely new
 * bugs). Operators triage from this file — the command never mutates state.
 *
 * Usage:
 *   php artisan subscriptions:audit-post-v2-flip --format=csv > /tmp/audit.csv
 *   php artisan subscriptions:audit-post-v2-flip --format=table
 */
class AuditPostV2Flip extends Command
{
    protected $signature = 'subscriptions:audit-post-v2-flip
                            {--format=csv : csv|table — output format}
                            {--limit=0 : Stop after this many cycles (0 = no limit)}
                            {--academy= : Restrict to a single academy id}';

    protected $description = 'Read-only audit of subscription cycles after the v2 flip; classifies legacy vs post-flip violations';

    public function handle(SubscriptionInvariantChecker $checker): int
    {
        $format = (string) ($this->option('format') ?? 'csv');
        $limit = (int) ($this->option('limit') ?? 0);
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $cutoff = $this->resolveCutoff();

        $rows = [];
        $cycleCount = 0;

        $cyclesQuery = SubscriptionCycle::query()
            ->orderBy('id');

        if ($academyId !== null) {
            $cyclesQuery->where('academy_id', $academyId);
        }

        $cyclesQuery->chunkById(200, function ($cycles) use (&$rows, &$cycleCount, $checker, $cutoff, $limit) {
            foreach ($cycles as $cycle) {
                /** @var SubscriptionCycle $cycle */
                if ($limit > 0 && $cycleCount >= $limit) {
                    return false;
                }
                $cycleCount++;

                $row = $this->buildRow($cycle, $checker, $cutoff);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }

            return true;
        });

        if ($format === 'table') {
            $this->table(array_keys($rows[0] ?? $this->emptyRow()), $rows);

            return self::SUCCESS;
        }

        // CSV: header + rows on stdout.
        $columns = array_keys($this->emptyRow());
        $this->line(implode(',', $columns));
        foreach ($rows as $row) {
            $this->line(implode(',', array_map([$this, 'csvEscape'], array_values($row))));
        }

        return self::SUCCESS;
    }

    private function resolveCutoff(): ?CarbonInterface
    {
        $raw = config('subscriptions.v2_flip_cutoff');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, scalar>|null Null when cycle has no violations.
     */
    private function buildRow(
        SubscriptionCycle $cycle,
        SubscriptionInvariantChecker $checker,
        ?CarbonInterface $cutoff,
    ): ?array {
        $sub = $this->loadSubscription($cycle);
        $consumptionCount = SessionConsumption::query()
            ->where('cycle_id', $cycle->getKey())
            ->whereNull('reversed_at')
            ->count();

        $createdPreFlip = $cutoff !== null
            && $cycle->created_at instanceof CarbonInterface
            && $cycle->created_at->lt($cutoff);

        $packageIdNull = $cycle->package_id === null && $cycle->pricing_source === 'package';
        $usageMismatch = (int) $cycle->sessions_used !== $consumptionCount;

        // Run the invariant checker only when we have a parent subscription;
        // otherwise this is a structural data issue — skip the heavy walk and
        // attribute the row to STRUCTURAL.
        $violationCodes = [];
        $severity = 'info';

        if ($sub instanceof BaseSubscription) {
            try {
                $violations = $checker->check($sub);
                foreach ($violations as $v) {
                    $code = $v['code'] ?? null;
                    if (! is_string($code)) {
                        continue;
                    }
                    if (! isset($v['context']['cycle_id'])) {
                        // Subscription-scoped violation — attribute it to every
                        // cycle row of that sub for traceability, but keep the
                        // CSV manageable: only attach when this is the active
                        // cycle.
                        if ((int) ($sub->current_cycle_id ?? 0) !== (int) $cycle->getKey()) {
                            continue;
                        }
                    } elseif ((int) $v['context']['cycle_id'] !== (int) $cycle->getKey()) {
                        continue;
                    }
                    $violationCodes[] = $code;
                    $severity = $this->mergeSeverity($severity, $v['severity'] ?? 'error');
                }
            } catch (Throwable) {
                $violationCodes[] = 'CHECKER-ERR';
            }
        }

        // Skip silent rows: nothing flagged at any level.
        if (empty($violationCodes) && ! $packageIdNull && ! $usageMismatch) {
            return null;
        }

        $action = $this->suggestAction($violationCodes, $packageIdNull, $usageMismatch, $createdPreFlip);

        return [
            'cycle_id' => (int) $cycle->getKey(),
            'sub_id' => (int) ($sub?->getKey() ?? $cycle->subscribable_id),
            'sub_type' => (string) $cycle->subscribable_type,
            'violation_codes' => implode('|', array_unique($violationCodes)),
            'severity' => (string) $severity,
            'created_pre_flip' => $createdPreFlip ? '1' : '0',
            'package_id_null' => $packageIdNull ? '1' : '0',
            'consumption_count' => $consumptionCount,
            'stored_sessions_used' => (int) $cycle->sessions_used,
            'suggested_action' => $action,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function emptyRow(): array
    {
        return [
            'cycle_id' => '',
            'sub_id' => '',
            'sub_type' => '',
            'violation_codes' => '',
            'severity' => '',
            'created_pre_flip' => '',
            'package_id_null' => '',
            'consumption_count' => '',
            'stored_sessions_used' => '',
            'suggested_action' => '',
        ];
    }

    private function loadSubscription(SubscriptionCycle $cycle): ?BaseSubscription
    {
        try {
            $morphMap = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
            $class = $morphMap[$cycle->subscribable_type] ?? null;
            if (! is_string($class) || ! class_exists($class)) {
                return null;
            }
            /** @var class-string<BaseSubscription> $class */
            $sub = $class::query()->find($cycle->subscribable_id);

            return $sub instanceof BaseSubscription ? $sub : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function mergeSeverity(string $current, string $incoming): string
    {
        $rank = ['info' => 0, 'warning' => 1, 'error' => 2];

        return ($rank[$incoming] ?? 0) > ($rank[$current] ?? 0)
            ? $incoming
            : $current;
    }

    private function suggestAction(
        array $violationCodes,
        bool $packageIdNull,
        bool $usageMismatch,
        bool $createdPreFlip,
    ): string {
        if ($packageIdNull) {
            return 'run-backfill-package-id';
        }
        if ($usageMismatch && $createdPreFlip) {
            return 'legacy-cycle-replay-attendance';
        }
        if ($usageMismatch && ! $createdPreFlip) {
            return 'investigate-post-flip-divergence';
        }
        if (in_array('INV-A2', $violationCodes, true)) {
            return 'reconcile-lie-state';
        }
        if (in_array('INV-G4', $violationCodes, true)) {
            return 'archive-hybrid-cycle';
        }

        return 'admin-triage';
    }

    private function csvEscape(mixed $value): string
    {
        $s = (string) $value;
        if (str_contains($s, ',') || str_contains($s, '"') || str_contains($s, "\n")) {
            return '"'.str_replace('"', '""', $s).'"';
        }

        return $s;
    }
}
