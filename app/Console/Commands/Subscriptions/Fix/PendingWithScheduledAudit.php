<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;

/**
 * Audit-only report: subs currently in the legacy "lie state" (sub
 * status ACTIVE + current cycle payment_status PENDING) that still have
 * future SCHEDULED sessions on the books. Phase 3 makes these subs
 * lose access; this report tells admin which students need manual
 * triage before the flip.
 *
 * Writes a markdown file to docs/cleanup/pending-with-scheduled-{date}.md.
 * No mutations.
 */
class PendingWithScheduledAudit extends Command
{
    protected $signature = 'subscriptions:audit-pending-with-scheduled
                            {--out= : Output file path (default: docs/cleanup/pending-with-scheduled-{date}.md)}';

    protected $description = 'Report subs in legacy lie-state with future scheduled sessions, so admin can triage before the pending-no-access flip.';

    public function handle(): int
    {
        $outPath = $this->option('out')
            ?? base_path('docs/cleanup/pending-with-scheduled-'.now()->format('Y-m-d').'.md');

        $rows = [];
        SubscriptionCycle::query()
            ->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)
            ->where('payment_status', SubscriptionCycle::PAYMENT_PENDING)
            ->whereNull('grace_period_ends_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('subscribable')
            ->chunk(200, function ($cycles) use (&$rows) {
                foreach ($cycles as $cycle) {
                    $sub = $cycle->subscribable;
                    if (! $sub instanceof BaseSubscription
                        || $sub->status !== SessionSubscriptionStatus::ACTIVE
                        || ! method_exists($sub, 'sessions')) {
                        continue;
                    }

                    $futureSessions = $sub->sessions()
                        ->where('status', SessionStatus::SCHEDULED->value)
                        ->where('scheduled_at', '>', now())
                        ->count();

                    if ($futureSessions === 0) {
                        continue;
                    }

                    $rows[] = [
                        'sub_type' => $cycle->subscribable_type,
                        'sub_id' => $sub->getKey(),
                        'cycle_id' => $cycle->getKey(),
                        'starts_at' => $cycle->starts_at?->toDateTimeString(),
                        'ends_at' => $cycle->ends_at?->toDateTimeString(),
                        'future_scheduled' => $futureSessions,
                        'student_id' => $sub->student_id ?? null,
                        'academy_id' => $sub->academy_id ?? null,
                    ];
                }
            });

        $body = "# Pending-with-scheduled audit — ".now()->toDateString()."\n\n";
        $body .= "Subs in lie-state (active sub + pending cycle, no grace) with future scheduled sessions.\n";
        $body .= "Phase 3 of the renewal-flow plan flips these to no-access — triage each row manually.\n\n";
        $body .= "Total: ".count($rows)." sub(s)\n\n";

        if (count($rows) > 0) {
            $body .= "| sub | cycle | window | future_scheduled | student | academy |\n";
            $body .= "| --- | --- | --- | --- | --- | --- |\n";
            foreach ($rows as $r) {
                $body .= sprintf(
                    "| %s#%d | %d | %s → %s | %d | %s | %s |\n",
                    class_basename((string) $r['sub_type']),
                    $r['sub_id'],
                    $r['cycle_id'],
                    $r['starts_at'],
                    $r['ends_at'],
                    $r['future_scheduled'],
                    $r['student_id'],
                    $r['academy_id'],
                );
            }
        } else {
            $body .= "No lie-state subs with future scheduled sessions. Phase 3 flip is safe.\n";
        }

        $dir = dirname($outPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($outPath, $body);

        $this->info(sprintf('Wrote %d row(s) to %s', count($rows), $outPath));

        return self::SUCCESS;
    }
}
