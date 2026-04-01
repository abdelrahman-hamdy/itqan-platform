<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ExpireActiveSubscriptions Command
 *
 * Transitions ACTIVE subscriptions to EXPIRED when their ends_at date has passed.
 * Respects grace periods — subscriptions with an active grace period are skipped.
 *
 * On expiry:
 * - Deactivates linked circle/lesson
 * - Suspends future scheduled sessions (recoverable on reactivation)
 */
class ExpireActiveSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-active
                            {--dry-run : Preview changes without making them}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Expire active subscriptions past their end date';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Scanning for active subscriptions past their end date...');
        $this->newLine();

        $stats = [
            'quran' => 0,
            'academic' => 0,
            'skipped_grace' => 0,
            'errors' => 0,
        ];

        $now = now();

        $types = [
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
        ];

        foreach ($types as $type => $modelClass) {
            $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now)
                ->chunkById(100, function ($subscriptions) use ($dryRun, $now, &$stats, $type) {
                    foreach ($subscriptions as $subscription) {
                        if ($this->isInGracePeriod($subscription, $now)) {
                            $stats['skipped_grace']++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  [DRY RUN] Would expire {$type} subscription #{$subscription->id} (ends_at: {$subscription->ends_at})");
                            $stats[$type]++;

                            continue;
                        }

                        try {
                            DB::transaction(function () use ($subscription) {
                                // 1. Set subscription to EXPIRED
                                $subscription->update(['status' => SessionSubscriptionStatus::EXPIRED]);

                                // 2. Deactivate linked circle/lesson
                                $this->deactivateLinkedEntities($subscription);

                                // 3. Suspend future scheduled sessions
                                $this->suspendFutureSessions($subscription);
                            });

                            $stats[$type]++;

                            Log::info('Subscription auto-expired', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'ends_at' => $subscription->ends_at->toDateTimeString(),
                            ]);
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            Log::error('Failed to expire subscription', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        $total = $stats['quran'] + $stats['academic'];

        if ($total === 0 && $stats['skipped_grace'] === 0) {
            $this->info('No subscriptions to expire.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Type', 'Expired'],
            [
                ['Quran', $stats['quran']],
                ['Academic', $stats['academic']],
                ['Total', $total],
            ]
        );

        if ($stats['skipped_grace'] > 0) {
            $this->line("  Skipped (grace period): {$stats['skipped_grace']}");
        }

        if ($stats['errors'] > 0) {
            $this->warn("  Errors: {$stats['errors']}");
        }

        if ($dryRun) {
            $this->warn('Dry run mode — no changes made.');
        } else {
            $this->info("Expired {$total} subscription(s).");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Deactivate linked circle (Quran) or lesson (Academic).
     */
    private function deactivateLinkedEntities($subscription): void
    {
        // Quran: deactivate individual circle
        if ($subscription instanceof QuranSubscription && $subscription->education_unit_id) {
            $subscription->educationUnit?->update(['is_active' => false]);
        }

        // Academic: deactivate lesson
        if ($subscription instanceof AcademicSubscription) {
            $subscription->lesson?->update(['is_active' => false]);
        }
    }

    /**
     * Suspend future scheduled/unscheduled sessions (recoverable on reactivation).
     */
    private function suspendFutureSessions($subscription): void
    {
        if (! method_exists($subscription, 'sessions')) {
            return;
        }

        $subscription->sessions()
            ->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::UNSCHEDULED->value,
                SessionStatus::READY->value,
            ])
            ->where(function ($q) {
                $q->where('scheduled_at', '>', now())
                    ->orWhereNull('scheduled_at');
            })
            ->update(['status' => SessionStatus::SUSPENDED->value]);
    }

    private function isInGracePeriod($subscription, Carbon $now): bool
    {
        $metadata = $subscription->metadata ?? [];

        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isAfter($now);
    }
}
