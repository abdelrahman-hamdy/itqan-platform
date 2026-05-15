<?php

namespace App\Console\Commands;

use App\Models\BaseSubscription;
use App\Services\Subscription\SessionEvidenceReconciler;
use App\Services\Subscription\SubscriptionManifestService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Read-only per-subscription audit. Loads the full manifest, runs the
 * evidence reconciler against every session, prints a markdown table to
 * the terminal, and appends rows to storage/audit/sub-{id}.csv +
 * storage/audit/MASTER-decisions.csv.
 *
 * Strictly read-only. The audit identifies what *should* happen; the
 * apply step is a separate (Phase 2) command and lives in dedicated
 * per-pattern fix scripts.
 *
 * Examples:
 *   php artisan subscriptions:audit-one --id=1245
 *   php artisan subscriptions:audit-one --id=1245 --morph=quran_subscription
 *   php artisan subscriptions:audit-one --id=1245 --no-csv
 */
class AuditOneSubscription extends Command
{
    protected $signature = 'subscriptions:audit-one
                            {--id= : Subscription ID}
                            {--morph= : Morph alias (quran_subscription / academic_subscription / course_subscription). Optional; auto-detect when omitted.}
                            {--no-csv : Skip writing CSV files (terminal output only)}';

    protected $description = 'Read-only audit of a single subscription. Prints evidence + verdict per session, writes per-sub CSV and appends to MASTER-decisions.csv.';

    public function __construct(
        private readonly SubscriptionManifestService $manifests,
        private readonly SessionEvidenceReconciler $reconciler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = (int) $this->option('id');
        if ($id <= 0) {
            $this->error('--id is required and must be a positive integer.');

            return self::INVALID;
        }

        $sub = $this->loadSubscription($id);
        if ($sub === null) {
            $this->error(sprintf('Subscription #%d not found.', $id));

            return self::FAILURE;
        }

        $bundle = $this->manifests->build($sub);
        $manifest = $bundle['manifest'];
        $studentUserId = (int) ($manifest['subscription']['student_id'] ?? 0);

        $this->printHeader($sub, $bundle);
        $this->printCyclesTable($bundle['drift']);
        $verdicts = $this->printSessionsTable($manifest, $studentUserId);

        if (! $this->option('no-csv')) {
            $this->writeCsv($sub, $bundle, $verdicts);
        }

        return self::SUCCESS;
    }

    private function loadSubscription(int $id): ?BaseSubscription
    {
        $morph = $this->option('morph');
        if ($morph !== null && $morph !== '') {
            return $this->manifests->findByIdAndMorph($id, (string) $morph);
        }

        return $this->manifests->findById($id);
    }

    /**
     * @param  array<string,mixed>  $bundle
     */
    private function printHeader(BaseSubscription $sub, array $bundle): void
    {
        $manifest = $bundle['manifest'];
        $row = $manifest['subscription'];
        $counts = $manifest['counts'];

        $this->info(sprintf('=== Subscription #%d (%s) ===', $sub->getKey(), $sub->getMorphClass()));
        $this->line(sprintf(
            'view_state=%s  status=%s  payment_status=%s  sessions_used=%s/%s  ends_at=%s',
            $bundle['view_state'],
            $row['status'] ?? '—',
            $row['payment_status'] ?? '—',
            $row['sessions_used'] ?? '—',
            $row['total_sessions'] ?? '—',
            $row['ends_at'] ?? '—',
        ));
        $this->line(sprintf(
            'counts: %d cycle(s), %d session(s), %d payment(s), %d active consumption(s), %d audit-log row(s) [%d w/ violations]',
            $counts['cycles'],
            $counts['sessions'],
            $counts['payments'],
            $counts['consumptions_active'],
            $counts['audit_log_entries'],
            $counts['audit_log_violations'],
        ));
        $this->line('');
    }

    /**
     * @param  array<int, array<string,mixed>>  $drift
     */
    private function printCyclesTable(array $drift): void
    {
        if ($drift === []) {
            $this->line('No cycles.');

            return;
        }

        $rows = array_map(static fn (array $d) => [
            $d['cycle_id'],
            $d['cycle_number'],
            $d['cycle_state'],
            $d['v2_consumption_complete'] ? 'yes' : 'no',
            $d['cycle_sessions_used'],
            $d['consumption_rows'],
            $d['sessions_on_cycle'],
            $d['legacy_counted_sessions'],
            $d['kind'],
        ], $drift);

        $this->table(
            ['cycle', '#', 'state', 'v2_done', 'agg.used', 'rows', 'sessions', 'legacy_cnt', 'drift_kind'],
            $rows,
        );
    }

    /**
     * Per-session evidence + verdict.
     *
     * @param  array<string,mixed>  $manifest
     * @return list<array<string,mixed>>
     */
    private function printSessionsTable(array $manifest, int $studentUserId): array
    {
        $sessions = $manifest['sessions'] ?? [];
        if ($sessions === []) {
            $this->line('No sessions reference this subscription.');

            return [];
        }

        $rows = [];
        $verdicts = [];
        foreach ($sessions as $session) {
            $verdict = $this->reconciler->reconcile(
                $session,
                $studentUserId,
                $manifest['consumptions'] ?? [],
            );

            $rows[] = [
                $session['id'],
                substr((string) ($session['scheduled_at'] ?? ''), 0, 19),
                $session['status'] ?? '—',
                $session['cycle_id'] ?? '—',
                $this->summariseAttendance($session, $studentUserId),
                count($session['reports'] ?? []) > 0 ? 'y' : 'n',
                $session['has_recording'] ? 'y' : 'n',
                $session['legacy_subscription_counted'] ? 'y' : 'n',
                $this->lookupConsumptionFor($manifest, (int) $session['id'], $studentUserId),
                $verdict['verdict'],
                $verdict['confidence'],
            ];

            $verdicts[] = [
                'session_id' => $session['id'],
                'cycle_id' => $session['cycle_id'],
                'student_id' => $studentUserId,
                'scheduled_at' => $session['scheduled_at'] ?? null,
                'status' => $session['status'] ?? null,
                'verdict' => $verdict['verdict'],
                'confidence' => $verdict['confidence'],
                'reasons' => implode(' | ', $verdict['reasons']),
                'conflicts' => implode(' | ', $verdict['conflicts']),
            ];
        }

        $this->table(
            ['session', 'scheduled', 'status', 'cycle', 'att', 'rpt?', 'rec?', 'legacy?', 'consumption', 'verdict', 'conf'],
            $rows,
        );

        return $verdicts;
    }

    /**
     * @param  array<string,mixed>  $session
     */
    private function summariseAttendance(array $session, int $studentUserId): string
    {
        foreach ($session['attendance'] ?? [] as $att) {
            if ((int) ($att['user_id'] ?? 0) === $studentUserId) {
                return (string) ($att['attendance_status'] ?? '—');
            }
        }

        return '—';
    }

    /**
     * @param  array<string,mixed>  $manifest
     */
    private function lookupConsumptionFor(array $manifest, int $sessionId, int $studentUserId): string
    {
        foreach ($manifest['consumptions'] ?? [] as $c) {
            if ((int) $c['session_id'] !== $sessionId) {
                continue;
            }
            if ((int) ($c['student_user_id'] ?? 0) !== $studentUserId) {
                continue;
            }
            if (! empty($c['reversed_at'])) {
                return sprintf('reversed #%d', $c['id']);
            }

            return sprintf('#%d:%s', $c['id'], $c['source']);
        }

        return '—';
    }

    /**
     * @param  array<string,mixed>  $bundle
     * @param  list<array<string,mixed>>  $verdicts
     */
    private function writeCsv(BaseSubscription $sub, array $bundle, array $verdicts): void
    {
        $dir = storage_path('audit');
        File::ensureDirectoryExists($dir);

        // Per-sub CSV (one row per session, fully expanded)
        $subFile = sprintf('%s/sub-%d.csv', $dir, $sub->getKey());
        $writeHeader = ! File::exists($subFile);
        $fh = fopen($subFile, 'a');
        if ($fh === false) {
            $this->warn(sprintf('Could not open %s for writing', $subFile));

            return;
        }
        if ($writeHeader) {
            fputcsv($fh, [
                'subscription_id', 'subscription_type', 'session_id', 'cycle_id',
                'student_id', 'scheduled_at', 'status', 'verdict', 'confidence',
                'reasons', 'conflicts', 'audited_at',
            ]);
        }
        $now = Carbon::now()->toAtomString();
        foreach ($verdicts as $v) {
            fputcsv($fh, [
                $sub->getKey(), $sub->getMorphClass(),
                $v['session_id'], $v['cycle_id'], $v['student_id'],
                $v['scheduled_at'], $v['status'],
                $v['verdict'], $v['confidence'],
                $v['reasons'], $v['conflicts'], $now,
            ]);
        }
        fclose($fh);

        // Master decisions CSV (one row per subscription, summary)
        $masterFile = sprintf('%s/MASTER-decisions.csv', $dir);
        $writeHeader = ! File::exists($masterFile);
        $fh = fopen($masterFile, 'a');
        if ($fh === false) {
            $this->warn(sprintf('Could not open %s for writing', $masterFile));

            return;
        }
        if ($writeHeader) {
            fputcsv($fh, [
                'subscription_id', 'subscription_type', 'view_state',
                'sessions_total', 'sessions_with_verdict_count',
                'sessions_with_verdict_dont_count',
                'sessions_authoritative', 'sessions_uncertain', 'sessions_no_signal',
                'cycles_total', 'cycles_drifting', 'drift_kinds',
                'audited_at',
            ]);
        }

        $counters = [
            'COUNT' => 0,
            'DONT_COUNT' => 0,
            'AUTHORITATIVE_FROM_CONSUMPTION' => 0,
            'UNCERTAIN' => 0,
            'NO_SIGNAL' => 0,
        ];
        foreach ($verdicts as $v) {
            $counters[$v['verdict']] = ($counters[$v['verdict']] ?? 0) + 1;
        }

        $driftKinds = [];
        $driftCount = 0;
        foreach ($bundle['drift'] as $d) {
            if ($d['is_drifting']) {
                $driftCount++;
            }
            $kind = $d['kind'];
            $driftKinds[$kind] = ($driftKinds[$kind] ?? 0) + 1;
        }
        $driftSummary = implode(',', array_map(
            fn (string $k, int $n) => "{$k}:{$n}",
            array_keys($driftKinds),
            array_values($driftKinds),
        ));

        fputcsv($fh, [
            $sub->getKey(), $sub->getMorphClass(), $bundle['view_state'],
            count($verdicts),
            $counters['COUNT'],
            $counters['DONT_COUNT'],
            $counters['AUTHORITATIVE_FROM_CONSUMPTION'],
            $counters['UNCERTAIN'],
            $counters['NO_SIGNAL'],
            count($bundle['drift']), $driftCount, $driftSummary,
            $now,
        ]);
        fclose($fh);

        $this->line(sprintf('Wrote %s + appended to MASTER-decisions.csv', $subFile));
    }
}
