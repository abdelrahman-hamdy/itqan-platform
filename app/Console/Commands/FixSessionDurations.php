<?php

namespace App\Console\Commands;

use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSessionDurations extends Command
{
    protected $signature = 'sessions:fix-durations
                          {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix session durations that were incorrectly set to 45 minutes due to missing session_duration_minutes on subscriptions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting session duration fix...');
        $this->newLine();

        try {
            DB::beginTransaction();

            $subsFixed = $this->fixSubscriptions($isDryRun);
            $circlesFixed = $this->fixIndividualCircles($isDryRun);
            $sessionsFixed = $this->fixSessions($isDryRun);

            if (! $isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Subscriptions fixed: {$subsFixed}");
        $this->info("  Individual circles fixed: {$circlesFixed}");
        $this->info("  Sessions fixed: {$sessionsFixed}");

        if ($isDryRun) {
            $this->warn('  No actual changes made (dry run)');
        }

        return self::SUCCESS;
    }

    /**
     * Fix subscriptions where session_duration_minutes=45 but package has a different value.
     */
    private function fixSubscriptions(bool $isDryRun): int
    {
        $this->info('[1/3] Fixing subscription durations...');

        $fixed = 0;

        QuranSubscription::query()
            ->where('session_duration_minutes', 45)
            ->whereNotNull('package_id')
            ->with('package')
            ->chunkById(200, function ($subscriptions) use (&$fixed, $isDryRun) {
                foreach ($subscriptions as $subscription) {
                    $packageDuration = $subscription->package?->session_duration_minutes;

                    // Only fix if the package has a defined duration that is NOT 45
                    if ($packageDuration && $packageDuration !== 45) {
                        $this->line("  Subscription #{$subscription->id} (code: {$subscription->subscription_code}): 45 -> {$packageDuration} min");

                        if (! $isDryRun) {
                            $subscription->updateQuietly(['session_duration_minutes' => $packageDuration]);
                        }

                        $fixed++;
                    }
                }
            });

        $this->info("  -> {$fixed} subscriptions ".($isDryRun ? 'would be' : '').' fixed');

        return $fixed;
    }

    /**
     * Fix individual circles where default_duration_minutes doesn't match subscription/package.
     */
    private function fixIndividualCircles(bool $isDryRun): int
    {
        $this->info('[2/3] Fixing individual circle durations...');

        $fixed = 0;

        QuranIndividualCircle::query()
            ->with(['subscription.package'])
            ->chunkById(200, function ($circles) use (&$fixed, $isDryRun) {
                foreach ($circles as $circle) {
                    $correctDuration = $circle->subscription?->session_duration_minutes
                        ?? $circle->subscription?->package?->session_duration_minutes;

                    // Only fix if we have a source of truth AND the current value differs
                    if ($correctDuration && $circle->default_duration_minutes !== $correctDuration) {
                        $this->line("  Circle #{$circle->id}: {$circle->default_duration_minutes} -> {$correctDuration} min");

                        if (! $isDryRun) {
                            $circle->updateQuietly(['default_duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }
                }
            });

        $this->info("  -> {$fixed} circles ".($isDryRun ? 'would be' : '').' fixed');

        return $fixed;
    }

    /**
     * Fix sessions where duration_minutes doesn't match the source of truth.
     */
    private function fixSessions(bool $isDryRun): int
    {
        $this->info('[3/3] Fixing session durations...');

        $fixed = 0;

        // Fix individual sessions
        QuranSession::query()
            ->whereNotNull('individual_circle_id')
            ->with(['individualCircle.subscription.package'])
            ->chunkById(200, function ($sessions) use (&$fixed, $isDryRun) {
                foreach ($sessions as $session) {
                    $correctDuration = $session->individualCircle?->subscription?->session_duration_minutes
                        ?? $session->individualCircle?->subscription?->package?->session_duration_minutes
                        ?? $session->individualCircle?->default_duration_minutes;

                    if ($correctDuration && $session->duration_minutes !== $correctDuration) {
                        $this->line("  Individual Session #{$session->id}: {$session->duration_minutes} -> {$correctDuration} min");

                        if (! $isDryRun) {
                            $session->updateQuietly(['duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }
                }
            });

        // Fix group sessions
        QuranSession::query()
            ->whereNotNull('circle_id')
            ->with(['circle.schedule'])
            ->chunkById(200, function ($sessions) use (&$fixed, $isDryRun) {
                foreach ($sessions as $session) {
                    $correctDuration = $session->circle?->schedule?->default_duration_minutes;

                    if ($correctDuration && $session->duration_minutes !== $correctDuration) {
                        $this->line("  Group Session #{$session->id}: {$session->duration_minutes} -> {$correctDuration} min");

                        if (! $isDryRun) {
                            $session->updateQuietly(['duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }
                }
            });

        $this->info("  -> {$fixed} sessions ".($isDryRun ? 'would be' : '').' fixed');

        return $fixed;
    }
}
