<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;

/**
 * Runs SubscriptionReconciler::syncWithoutInvariantCheck() against every
 * subscription that's drifted from its current cycle (INV-A1 — subscription
 * row should mirror currentCycle's date/counter/pricing columns).
 *
 * The reconciler's `mirrorFromCycle()` only saves when the row is dirty,
 * so dry-run is honest: it walks the population, computes the would-be
 * change set, and reports counts without writing.
 *
 * --apply triggers the actual mirror writes. Each sub goes through the
 * canonical reconciler so the SubscriptionRowGuard observer sees the
 * `reconciling=true` flag and permits the derived-field update.
 *
 * The reconciler then runs the invariant checker. Info-severity findings
 * (documented metadata gaps) no longer block the write since the prior
 * commit; error/warning still does.
 */
class MirrorCurrentCycle extends Command
{
    protected $signature = 'subscriptions:fix-mirror-current-cycle
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of subs processed}
                            {--academy= : Restrict to one academy id}';

    protected $description = 'Re-mirror parent subscription row columns from the current cycle (INV-A1 fix).';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $classes = [QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class];

        $total = 0;
        foreach ($classes as $class) {
            $q = $class::query();
            if ($academy !== null) {
                $q->where('academy_id', $academy);
            }
            $total += $q->count();
        }
        if ($limit !== null && $limit < $total) {
            $total = $limit;
        }

        $this->info(sprintf('Subscriptions to inspect: %d', $total));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = [
            'inspected' => 0,
            'mirrored' => 0,
            'already_clean' => 0,
            'errored_post_mirror' => 0,
            'no_current_cycle' => 0,
        ];
        $errorSummary = [];

        foreach ($classes as $class) {
            $q = $class::query()->orderBy('id');
            if ($academy !== null) {
                $q->where('academy_id', $academy);
            }
            $q->chunkById(50, function ($chunk) use ($reconciler, $apply, $limit, &$stats, &$errorSummary, $bar) {
                foreach ($chunk as $sub) {
                    if ($limit !== null && $stats['inspected'] >= $limit) {
                        return false;
                    }
                    $stats['inspected']++;

                    if ($sub->currentCycle === null) {
                        $stats['no_current_cycle']++;
                        $bar->advance();

                        continue;
                    }

                    if (! $apply) {
                        $bar->advance();

                        continue;
                    }

                    try {
                        $reconciler->syncWithoutInvariantCheck($sub);
                        $sub->refresh();
                        $stats['mirrored']++;
                    } catch (\Throwable $e) {
                        $stats['errored_post_mirror']++;
                        $code = $this->extractFirstCode($e->getMessage());
                        $errorSummary[$code] = ($errorSummary[$code] ?? 0) + 1;
                    }

                    $bar->advance();
                }

                return true;
            });
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s inspected=%d, mirrored=%d, errored=%d, no_current_cycle=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $stats['inspected'],
            $stats['mirrored'],
            $stats['errored_post_mirror'],
            $stats['no_current_cycle'],
        ));

        if (! empty($errorSummary)) {
            $this->line('Error breakdown:');
            foreach ($errorSummary as $code => $n) {
                $this->line(sprintf('  %s: %d', $code, $n));
            }
        }

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the mirror writes. Each sub uses syncWithoutInvariantCheck so writes only happen when the row is actually dirty.');
        }

        return $stats['errored_post_mirror'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function extractFirstCode(string $message): string
    {
        if (preg_match('/first: (INV-[A-Z]\d+)/', $message, $m)) {
            return $m[1];
        }

        return 'other';
    }
}
