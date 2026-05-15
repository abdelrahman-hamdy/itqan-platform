<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionLifecycle;
use Illuminate\Console\Command;

/**
 * Resolves two related cron-miss invariants:
 *
 *   INV-F6 — subscription.status=PAUSED but ends_at is in the past with no
 *            active grace window. Should be EXPIRED.
 *   INV-G4 — current cycle's payment_status=pending, cycle.ends_at is past,
 *            cycle_state≠archived, and the parent sub isn't EXPIRED yet
 *            (hybrid expire path).
 *
 * Both are flipped via the canonical SubscriptionLifecycle::expire() so the
 * audit log entry, lock, reconciler sync, and SubscriptionExpiredUnpaid
 * notification all fire correctly. Per-sub transaction; failure on one sub
 * does not poison the rest.
 *
 * Dry-run by default. --apply required for writes.
 */
class StaleExpiredSubs extends Command
{
    protected $signature = 'subscriptions:fix-stale-expired-subs
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of subs processed}
                            {--academy= : Restrict to one academy id}
                            {--mode=all : f6|g4|all (which shape to target)}';

    protected $description = 'Re-runs SubscriptionLifecycle::expire() against subs that the cron missed (INV-F6 + INV-G4).';

    public function handle(SubscriptionLifecycle $lifecycle): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;
        $mode = (string) ($this->option('mode') ?? 'all');

        $candidates = $this->collectCandidates($mode, $academy, $limit);
        $this->info(sprintf(
            'Candidates: %d sub(s) [mode=%s%s]',
            count($candidates),
            $mode,
            $academy ? ", academy={$academy}" : '',
        ));

        if (count($candidates) === 0) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($candidates));
        $bar->start();

        $touched = 0;
        $errors = 0;

        foreach ($candidates as $sub) {
            try {
                if ($apply) {
                    $lifecycle->expire($sub);
                }
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\nsub #%d (%s): %s", $sub->getKey(), $sub->getMorphClass(), $e->getMessage()));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s %d sub(s) processed; %d error(s).',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. Every write goes through SubscriptionLifecycle::expire() — full audit + lock + reconciler.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<\App\Models\BaseSubscription>
     */
    private function collectCandidates(string $mode, ?int $academy, ?int $limit): array
    {
        $candidates = [];

        // INV-F6 shape — status=PAUSED + ends_at past + not in grace window.
        if (in_array($mode, ['f6', 'all'], true)) {
            foreach ([QuranSubscription::class, AcademicSubscription::class] as $class) {
                $query = $class::query()
                    ->where('status', SessionSubscriptionStatus::PAUSED->value)
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<', now());
                if ($academy !== null) {
                    $query->where('academy_id', $academy);
                }
                foreach ($query->cursor() as $sub) {
                    if ($sub->isInGracePeriod()) {
                        continue;
                    }
                    $candidates[$sub->getMorphClass().':'.$sub->getKey()] = $sub;
                }
            }
        }

        // INV-G4 shape — current cycle pending+past_ends_at+not_archived;
        // parent sub may already be PAUSED or even ACTIVE.
        if (in_array($mode, ['g4', 'all'], true)) {
            $cycles = SubscriptionCycle::query()
                ->where('payment_status', SubscriptionCycle::PAYMENT_PENDING)
                ->where('cycle_state', '!=', SubscriptionCycle::STATE_ARCHIVED)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now())
                ->get();

            foreach ($cycles as $cycle) {
                $class = match ($cycle->subscribable_type) {
                    'quran_subscription' => QuranSubscription::class,
                    'academic_subscription' => AcademicSubscription::class,
                    default => null,
                };
                if ($class === null) {
                    continue;
                }
                $sub = $class::query()->find($cycle->subscribable_id);
                if ($sub === null) {
                    continue;
                }
                if ($academy !== null && (int) $sub->academy_id !== $academy) {
                    continue;
                }
                if ($sub->status === SessionSubscriptionStatus::EXPIRED) {
                    continue;
                }
                $candidates[$sub->getMorphClass().':'.$sub->getKey()] = $sub;
            }
        }

        $list = array_values($candidates);
        if ($limit !== null && count($list) > $limit) {
            $list = array_slice($list, 0, $limit);
        }

        return $list;
    }
}
