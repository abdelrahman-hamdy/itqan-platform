<?php

namespace App\Console\Commands\Backfill;

use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;

/**
 * Bug #11 audit — list subscriptions whose latest "ACTIVE" state was
 * preceded by a CANCELLED record, suggesting they were silently revived
 * by a webhook through the old `activateFromPayment` allowlist.
 *
 * Read-only. Operator inspects the CSV and decides per-case whether to
 * re-cancel (zombie) or keep (legitimate admin reactivation).
 *
 *   php artisan subscriptions:audit-resurrected-cancelled
 */
class AuditResurrectedCancelledCommand extends BaseAuditCommand
{
    protected $signature = 'subscriptions:audit-resurrected-cancelled
                            {--out= : Optional CSV path; defaults to storage/logs/bug11-audit-{timestamp}.csv}';

    protected $description = 'Bug #11: list subs that may have been silently revived from CANCELLED by a webhook';

    public function handle(): int
    {
        $candidates = collect();

        foreach ([QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class] as $modelClass) {
            $modelClass::withoutGlobalScopes()
                ->whereNotNull('cancelled_at')
                ->whereNotIn('status', [
                    \App\Enums\SessionSubscriptionStatus::CANCELLED,
                    \App\Enums\EnrollmentStatus::CANCELLED,
                ])
                ->chunkById(200, function ($subs) use ($candidates, $modelClass) {
                    foreach ($subs as $sub) {
                        $candidates->push([
                            'model' => class_basename($modelClass),
                            'id' => $sub->id,
                            'status' => $sub->status?->value ?? (string) $sub->status,
                            'cancelled_at' => $sub->cancelled_at?->toIso8601String(),
                            'cancellation_reason' => $sub->cancellation_reason,
                            'last_payment_date' => $sub->last_payment_date?->toIso8601String() ?? null,
                            'updated_at' => $sub->updated_at?->toIso8601String(),
                        ]);
                    }
                });
        }

        $this->info(sprintf('Found %d candidate(s) of possible resurrection.', $candidates->count()));

        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }

        $path = $this->writeCsv(
            'bug11',
            ['model', 'id', 'status', 'cancelled_at', 'cancellation_reason', 'last_payment_date', 'updated_at'],
            $candidates,
        );

        $this->info("CSV written to: $path");
        $this->newLine();
        $this->line('Operator next steps:');
        $this->line('  - Inspect each row in admin UI; verify whether the CANCELLED→ACTIVE');
        $this->line('    transition was a legitimate admin action or a webhook revival.');
        $this->line('  - For zombies, re-cancel via the standard cancel action; do not bulk-mutate.');

        return self::SUCCESS;
    }
}
