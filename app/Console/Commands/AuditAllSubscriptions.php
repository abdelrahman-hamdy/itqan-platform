<?php

namespace App\Console\Commands;

use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Services\Subscription\SessionEvidenceReconciler;
use App\Services\Subscription\SubscriptionManifestService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Read-only sweep across every subscription. Drives the audit-one logic
 * against the full population and produces three artefacts:
 *
 *   storage/audit/MASTER-decisions.csv  (one row per sub, summary verdict)
 *   storage/audit/REVIEW-queue.md       (only the UNCERTAIN/conflict subs)
 *   storage/audit/AUTO-batch.json       (subs whose evidence is unanimous,
 *                                       eligible for auto-apply)
 *
 * Filters:
 *   --tenant=itqan-academy   restrict to one tenant subdomain
 *   --morph=quran            restrict to one morph (quran|academic|course)
 *   --limit=N                cap subs processed (debug / dry-run)
 *
 * Always strictly read-only. No writes to subscription tables.
 */
class AuditAllSubscriptions extends Command
{
    protected $signature = 'subscriptions:audit-all-subs
                            {--tenant= : Tenant subdomain (academy.subdomain) to scope to}
                            {--morph= : Single morph type: quran|academic|course}
                            {--limit= : Cap the number of subscriptions audited (debug)}
                            {--reset-csv : Truncate MASTER-decisions.csv before run}';

    protected $description = 'Read-only sweep: audits every subscription, writes MASTER-decisions.csv + REVIEW-queue.md + AUTO-batch.json.';

    public function __construct(
        private readonly SubscriptionManifestService $manifests,
        private readonly SessionEvidenceReconciler $reconciler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dir = storage_path('audit');
        File::ensureDirectoryExists($dir);

        if ($this->option('reset-csv')) {
            foreach (['MASTER-decisions.csv', 'REVIEW-queue.md', 'AUTO-batch.json'] as $f) {
                $path = "{$dir}/{$f}";
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }

        $classes = $this->classesToScan();
        $total = 0;
        foreach ($classes as $class) {
            $total += $this->countMatching($class);
        }
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        if ($limit !== null && $limit < $total) {
            $total = $limit;
        }

        $this->info(sprintf('Auditing %d subscription(s) across %d morph(s)...', $total, count($classes)));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $auto = [];
        $review = [];
        $masterRows = [];
        $processed = 0;

        foreach ($classes as $class) {
            $query = $this->scopedQuery($class);
            $query->orderBy('id')->chunkById(50, function ($chunk) use ($limit, &$processed, &$auto, &$review, &$masterRows, $bar) {
                foreach ($chunk as $sub) {
                    if ($limit !== null && $processed >= $limit) {
                        return false;
                    }
                    $row = $this->auditSubscription($sub);
                    $masterRows[] = $row['master'];
                    if ($row['eligible_for_auto']) {
                        $auto[] = $row['auto'];
                    } else {
                        $review[] = $row['review'];
                    }
                    $processed++;
                    $bar->advance();
                }

                return true;
            });
        }

        $bar->finish();
        $this->line('');

        $this->writeMasterCsv($masterRows);
        $this->writeReviewMarkdown($review);
        $this->writeAutoBatch($auto);

        $this->info(sprintf(
            'Done. %d sub(s) audited. %d auto-eligible, %d in review queue.',
            $processed, count($auto), count($review),
        ));

        return self::SUCCESS;
    }

    /** @return list<class-string<BaseSubscription>> */
    private function classesToScan(): array
    {
        return match ($this->option('morph')) {
            'quran' => [QuranSubscription::class],
            'academic' => [AcademicSubscription::class],
            'course' => [CourseSubscription::class],
            default => [QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class],
        };
    }

    /**
     * @param  class-string<BaseSubscription>  $class
     */
    private function scopedQuery(string $class): \Illuminate\Database\Eloquent\Builder
    {
        $query = $class::query();
        $tenant = $this->option('tenant');
        if ($tenant !== null && $tenant !== '') {
            $academy = \App\Models\Academy::query()
                ->where('subdomain', $tenant)
                ->orWhere('id', $tenant)
                ->first();
            if ($academy === null) {
                $this->warn(sprintf('No academy matches tenant=%s; will return empty set.', $tenant));
                $query->whereRaw('1=0');
            } else {
                $query->where('academy_id', $academy->id);
            }
        }

        return $query;
    }

    /**
     * @param  class-string<BaseSubscription>  $class
     */
    private function countMatching(string $class): int
    {
        return $this->scopedQuery($class)->count();
    }

    /**
     * @return array{master: list<scalar|string>, eligible_for_auto: bool, auto?: array<string,mixed>, review?: array<string,mixed>}
     */
    private function auditSubscription(BaseSubscription $sub): array
    {
        $bundle = $this->manifests->build($sub);
        $manifest = $bundle['manifest'];
        $studentUserId = (int) ($manifest['subscription']['student_id'] ?? 0);

        $counters = [
            'COUNT' => 0,
            'DONT_COUNT' => 0,
            'AUTHORITATIVE_FROM_CONSUMPTION' => 0,
            'UNCERTAIN' => 0,
            'NO_SIGNAL' => 0,
        ];
        $uncertainSessions = [];
        $autoPlan = [];

        foreach ($manifest['sessions'] ?? [] as $session) {
            $verdict = $this->reconciler->reconcile(
                $session,
                $studentUserId,
                $manifest['consumptions'] ?? [],
            );
            $counters[$verdict['verdict']] = ($counters[$verdict['verdict']] ?? 0) + 1;

            if ($verdict['verdict'] === SessionEvidenceReconciler::VERDICT_UNCERTAIN) {
                $uncertainSessions[] = [
                    'session_id' => $session['id'],
                    'cycle_id' => $session['cycle_id'] ?? null,
                    'scheduled_at' => $session['scheduled_at'] ?? null,
                    'status' => $session['status'] ?? null,
                    'conflicts' => $verdict['conflicts'],
                    'reasons' => $verdict['reasons'],
                ];
            } elseif (in_array($verdict['verdict'], [
                SessionEvidenceReconciler::VERDICT_COUNT,
                SessionEvidenceReconciler::VERDICT_DONT_COUNT,
            ], true) && $verdict['confidence'] === SessionEvidenceReconciler::CONFIDENCE_HIGH) {
                $autoPlan[] = [
                    'session_id' => $session['id'],
                    'cycle_id' => $session['cycle_id'] ?? null,
                    'verdict' => $verdict['verdict'],
                    'reasons' => $verdict['reasons'],
                ];
            }
        }

        $driftCount = 0;
        $driftKinds = [];
        foreach ($bundle['drift'] as $d) {
            if ($d['is_drifting']) {
                $driftCount++;
            }
            $driftKinds[$d['kind']] = ($driftKinds[$d['kind']] ?? 0) + 1;
        }

        $masterRow = [
            'subscription_id' => $sub->getKey(),
            'subscription_type' => $sub->getMorphClass(),
            'academy_id' => $manifest['subscription']['academy_id'] ?? null,
            'view_state' => $bundle['view_state'],
            'sessions_total' => count($manifest['sessions'] ?? []),
            'verdict_count' => $counters['COUNT'],
            'verdict_dont_count' => $counters['DONT_COUNT'],
            'verdict_authoritative' => $counters['AUTHORITATIVE_FROM_CONSUMPTION'],
            'verdict_uncertain' => $counters['UNCERTAIN'],
            'verdict_no_signal' => $counters['NO_SIGNAL'],
            'cycles_total' => count($bundle['drift']),
            'cycles_drifting' => $driftCount,
            'drift_kinds' => implode(',', array_map(
                fn (string $k, int $n) => "{$k}:{$n}",
                array_keys($driftKinds),
                array_values($driftKinds),
            )),
            'audited_at' => Carbon::now()->toAtomString(),
        ];

        $eligibleForAuto = $counters['UNCERTAIN'] === 0
            && $driftCount === 0
            && $counters['NO_SIGNAL'] === 0;

        $result = [
            'master' => array_values($masterRow),
            'eligible_for_auto' => $eligibleForAuto,
        ];

        if ($eligibleForAuto) {
            $result['auto'] = [
                'subscription_id' => $sub->getKey(),
                'subscription_type' => $sub->getMorphClass(),
                'view_state' => $bundle['view_state'],
                'session_plan' => $autoPlan,
            ];
        } else {
            $result['review'] = [
                'subscription_id' => $sub->getKey(),
                'subscription_type' => $sub->getMorphClass(),
                'academy_id' => $manifest['subscription']['academy_id'] ?? null,
                'view_state' => $bundle['view_state'],
                'cycles' => array_map(static fn (array $d) => [
                    'cycle_id' => $d['cycle_id'],
                    'cycle_state' => $d['cycle_state'],
                    'kind' => $d['kind'],
                    'aggregate_minus_consumption_diff' => $d['aggregate_minus_consumption_diff'],
                    'is_drifting' => $d['is_drifting'],
                ], $bundle['drift']),
                'uncertain_sessions' => $uncertainSessions,
            ];
        }

        return $result;
    }

    /**
     * @param  list<list<scalar|string>>  $rows
     */
    private function writeMasterCsv(array $rows): void
    {
        $path = storage_path('audit/MASTER-decisions.csv');
        $fh = fopen($path, 'w');
        if ($fh === false) {
            $this->warn(sprintf('Could not open %s for writing', $path));

            return;
        }
        fputcsv($fh, [
            'subscription_id', 'subscription_type', 'academy_id', 'view_state',
            'sessions_total', 'verdict_count', 'verdict_dont_count',
            'verdict_authoritative', 'verdict_uncertain', 'verdict_no_signal',
            'cycles_total', 'cycles_drifting', 'drift_kinds', 'audited_at',
        ]);
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);
        $this->line(sprintf('Wrote %s (%d rows)', $path, count($rows)));
    }

    /**
     * @param  list<array<string,mixed>>  $review
     */
    private function writeReviewMarkdown(array $review): void
    {
        $path = storage_path('audit/REVIEW-queue.md');
        $body = "# Subscription Review Queue\n\nGenerated: ".Carbon::now()->toAtomString()."\n\n";
        $body .= "These subscriptions need a human verdict before any apply step runs.\n\n";
        $body .= '**'.count($review)."** subscription(s) in review.\n\n";

        foreach ($review as $r) {
            $body .= sprintf("## #%d (%s) — view_state=%s — academy=%s\n\n",
                $r['subscription_id'],
                $r['subscription_type'],
                $r['view_state'],
                $r['academy_id'] ?? '—',
            );
            if (! empty($r['cycles'])) {
                $body .= "**Cycles:**\n\n| cycle | state | kind | agg − rows |\n|---|---|---|---|\n";
                foreach ($r['cycles'] as $c) {
                    $body .= sprintf("| %d | %s | %s | %s |\n",
                        $c['cycle_id'], $c['cycle_state'], $c['kind'],
                        $c['aggregate_minus_consumption_diff'],
                    );
                }
                $body .= "\n";
            }
            if (! empty($r['uncertain_sessions'])) {
                $body .= "**Uncertain sessions:**\n\n";
                foreach ($r['uncertain_sessions'] as $s) {
                    $body .= sprintf("- session **#%d** (cycle %s, scheduled %s, status=%s)\n",
                        $s['session_id'], $s['cycle_id'] ?? '—',
                        $s['scheduled_at'] ?? '—', $s['status'] ?? '—',
                    );
                    if (! empty($s['conflicts'])) {
                        foreach ($s['conflicts'] as $c) {
                            $body .= "    - conflict: {$c}\n";
                        }
                    }
                }
                $body .= "\n";
            }
            $body .= "---\n\n";
        }

        File::put($path, $body);
        $this->line(sprintf('Wrote %s (%d sub(s) in review)', $path, count($review)));
    }

    /**
     * @param  list<array<string,mixed>>  $auto
     */
    private function writeAutoBatch(array $auto): void
    {
        $path = storage_path('audit/AUTO-batch.json');
        $payload = [
            'generated_at' => Carbon::now()->toAtomString(),
            'count' => count($auto),
            'subscriptions' => $auto,
        ];
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line(sprintf('Wrote %s (%d sub(s) eligible for auto)', $path, count($auto)));
    }
}
