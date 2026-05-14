<?php

namespace App\Console\Commands\Subscriptions;

use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionInvariantChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase A.4 — daily sweep of {@see SubscriptionInvariantChecker} across the
 * subscription population, with a structured JSON report and a Telegram /
 * subscriptions-log alert on any non-empty violations.
 *
 * Read-only — never mutates subscriptions.
 *
 * Usage:
 *   php artisan subscriptions:invariant-check --all
 *   php artisan subscriptions:invariant-check --sub-id=Quran:123
 *   php artisan subscriptions:invariant-check --academy=42 --all
 *
 * Exit codes:
 *   0 — no error-severity violations.
 *   1 — at least one error-severity violation (cron failure pages on this).
 *
 * The `subscriptions` log channel (config/logging.php, Phase C) carries a
 * `subscription_invariant_violations` warning entry whenever the run
 * produces non-empty violations; the LiveKit-VPS tail-and-forward script
 * pages Telegram via the existing `itqan-alert` pipeline.
 */
class InvariantCheckCommand extends Command
{
    protected $signature = 'subscriptions:invariant-check
                            {--all : Iterate every active subscription thread}
                            {--sub-id= : Restrict to a single subscription. Format: Type:id (e.g. Quran:123, Academic:42, Course:7) or a plain int (assumed Quran first, Academic second).}
                            {--academy= : Restrict to a single academy id}
                            {--report=storage/app/subscriptions/invariant-{date}.json : Path for the JSON report (relative paths are written under storage_path()).}';

    protected $description = 'Phase A.4 invariant sweep across subscriptions; writes JSON report and pages on violations.';

    public function handle(SubscriptionInvariantChecker $checker): int
    {
        $singleId = $this->option('sub-id');
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;
        $reportTemplate = (string) $this->option('report');

        if (! $this->option('all') && $singleId === null) {
            $this->error('Pass --all to sweep every sub or --sub-id=Type:id to target one.');

            return self::FAILURE;
        }

        $subs = $singleId !== null
            ? $this->resolveSingleSubscription((string) $singleId)
            : $this->resolveAllSubscriptions($academy);

        if (empty($subs)) {
            $this->warn('No subscriptions matched the given filters.');

            return self::SUCCESS;
        }

        $total = count($subs);
        $this->info(sprintf('Running invariant check across %d subscription(s).', $total));

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
        $bar->setMessage('starting…');
        $bar->start();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'academy_filter' => $academy,
            'sub_id_filter' => $singleId,
            'totals' => [
                'subscriptions_examined' => 0,
                'subscriptions_with_violations' => 0,
                'total_violations' => 0,
                'error_violations' => 0,
                'warning_violations' => 0,
                'info_violations' => 0,
            ],
            'results' => [],
        ];

        foreach ($subs as $sub) {
            $bar->setMessage(sprintf('%s#%d', $sub->getMorphClass(), (int) $sub->getKey()));

            try {
                $violations = $checker->check($sub);
            } catch (Throwable $e) {
                $violations = [[
                    'code' => 'INV-RUNTIME',
                    'severity' => 'error',
                    'message' => 'Checker crashed while evaluating this subscription.',
                    'context' => [
                        'exception' => $e::class,
                        'error' => $e->getMessage(),
                    ],
                ]];
            }

            $report['totals']['subscriptions_examined']++;

            if (! empty($violations)) {
                $report['totals']['subscriptions_with_violations']++;
                foreach ($violations as $v) {
                    $report['totals']['total_violations']++;
                    $severity = $v['severity'] ?? 'error';
                    if ($severity === 'error') {
                        $report['totals']['error_violations']++;
                    } elseif ($severity === 'warning') {
                        $report['totals']['warning_violations']++;
                    } else {
                        $report['totals']['info_violations']++;
                    }
                }

                $report['results'][] = [
                    'subscription_id' => (int) $sub->getKey(),
                    'subscription_type' => $sub->getMorphClass(),
                    'academy_id' => $sub->academy_id,
                    'violations' => $violations,
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $reportPath = $this->writeReport($report, $reportTemplate);
        $this->info(sprintf('Report written to %s', $reportPath));

        $this->table(
            ['metric', 'value'],
            collect($report['totals'])
                ->map(fn (int $v, string $k) => [$k, $v])
                ->values()
                ->all(),
        );

        // Phase C alerting — write to the dedicated subscriptions log channel
        // ONLY if at least one violation exists. The LiveKit-VPS tail script
        // picks `subscription_invariant_violations` warnings up and pipes them
        // through the itqan-alert Telegram pipeline.
        if ($report['totals']['total_violations'] > 0) {
            Log::channel('subscriptions')->warning('subscription_invariant_violations', [
                'examined' => $report['totals']['subscriptions_examined'],
                'subs_with_violations' => $report['totals']['subscriptions_with_violations'],
                'errors' => $report['totals']['error_violations'],
                'warnings' => $report['totals']['warning_violations'],
                'info' => $report['totals']['info_violations'],
                'report_path' => $reportPath,
            ]);
        }

        // Non-zero exit only on error-severity violations — warnings/info don't
        // page operations (they're surfaced in the JSON for triage).
        return $report['totals']['error_violations'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, BaseSubscription>
     */
    private function resolveSingleSubscription(string $raw): array
    {
        [$type, $id] = $this->parseSingleSubId($raw);

        $sub = match ($type) {
            'quran' => QuranSubscription::withoutGlobalScopes()->find($id),
            'academic' => AcademicSubscription::withoutGlobalScopes()->find($id),
            'course' => CourseSubscription::withoutGlobalScopes()->find($id),
            default => null,
        };

        if (! $sub instanceof BaseSubscription) {
            $this->error(sprintf('Subscription %s#%d not found.', $type, $id));

            return [];
        }

        return [$sub];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parseSingleSubId(string $raw): array
    {
        if (str_contains($raw, ':')) {
            [$type, $id] = explode(':', $raw, 2);

            return [strtolower(trim($type)), (int) $id];
        }

        // Plain int — default to Quran (most common); operator should use
        // the Type:id form for unambiguous lookup. We try Quran first then
        // Academic to keep the convenience.
        $id = (int) $raw;
        if (QuranSubscription::withoutGlobalScopes()->whereKey($id)->exists()) {
            return ['quran', $id];
        }
        if (AcademicSubscription::withoutGlobalScopes()->whereKey($id)->exists()) {
            return ['academic', $id];
        }

        return ['course', $id];
    }

    /**
     * @return array<int, BaseSubscription>
     */
    private function resolveAllSubscriptions(?int $academyId): array
    {
        $out = [];
        foreach ([QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class] as $modelClass) {
            $query = $modelClass::withoutGlobalScopes();
            if ($academyId !== null) {
                $query->where('academy_id', $academyId);
            }
            $query->orderBy('id')->chunkById(500, function ($chunk) use (&$out): void {
                foreach ($chunk as $sub) {
                    $out[] = $sub;
                }
            });
        }

        return $out;
    }

    /**
     * Resolve the {date} placeholder in the report path, ensure the parent
     * directory exists, write the JSON, return the absolute path.
     *
     * @param  array<string, mixed>  $report
     */
    private function writeReport(array $report, string $template): string
    {
        $resolved = str_replace('{date}', now()->format('Y-m-d-His'), $template);

        // If a relative path was passed, write under storage_path() so the
        // path resolves identically across environments.
        $absolute = str_starts_with($resolved, '/')
            ? $resolved
            : base_path($resolved);

        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents(
            $absolute,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        return $absolute;
    }
}
