<?php

namespace App\Console\Commands;

use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Exception;
use Illuminate\Console\Command;

/**
 * Migrate existing subscriptions from the old `original_ends_at` metadata pattern
 * to the new `grace_period_ends_at` pattern.
 *
 * Old behavior: extend action pushed `ends_at` forward, saved real end in `metadata['original_ends_at']`
 * New behavior: `ends_at` is never modified; grace extension stored in `metadata['grace_period_ends_at']`
 *
 * This command restores `ends_at` to `original_ends_at` and converts the pushed-forward
 * `ends_at` into `grace_period_ends_at`.
 *
 * Usage:
 *   php artisan subscriptions:migrate-grace-periods --dry-run
 *   php artisan subscriptions:migrate-grace-periods
 */
class MigrateGracePeriodMetadata extends Command
{
    protected $signature = 'subscriptions:migrate-grace-periods
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Migrate subscriptions from original_ends_at to grace_period_ends_at metadata';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Scanning subscriptions with metadata['original_ends_at']...");

        foreach ($this->getSubscriptionModels() as $modelClass => $label) {
            $this->info("\n{$prefix}Processing {$label}...");

            $modelClass::withoutGlobalScopes()
                ->whereNotNull('metadata')
                ->chunkById(100, function ($subscriptions) use ($dryRun, $label, &$migrated, &$skipped, &$errors) {
                    foreach ($subscriptions as $subscription) {
                        $metadata = $subscription->metadata ?? [];

                        if (! isset($metadata['original_ends_at'])) {
                            continue;
                        }

                        // Already migrated — has grace_period_ends_at
                        if (isset($metadata['grace_period_ends_at'])) {
                            $this->line("  - Skipped #{$subscription->id}: already has grace_period_ends_at");
                            $skipped++;

                            continue;
                        }

                        $originalEndsAt = $metadata['original_ends_at'];
                        $currentEndsAt = $subscription->ends_at?->format('Y-m-d H:i:s');

                        if ($dryRun) {
                            $this->info("  [DRY RUN] #{$subscription->id} ({$label}):");
                            $this->line("    Current ends_at:      {$currentEndsAt}");
                            $this->line("    original_ends_at:     {$originalEndsAt}");
                            $this->line("    → Restore ends_at to: {$originalEndsAt}");
                            $this->line("    → Set grace_period_ends_at: {$currentEndsAt}");
                            $migrated++;

                            continue;
                        }

                        try {
                            // Current ends_at = the grace-extended date → becomes grace_period_ends_at
                            // original_ends_at = the real paid-for end → restores to ends_at
                            $metadata['grace_period_ends_at'] = $currentEndsAt;
                            unset($metadata['original_ends_at']);

                            // Also update extension log entries if they exist
                            if (isset($metadata['extensions'])) {
                                foreach ($metadata['extensions'] as &$ext) {
                                    if (isset($ext['original_ends_at'])) {
                                        $ext['ends_at_at_time'] = $ext['original_ends_at'];
                                        unset($ext['original_ends_at']);
                                    }
                                    if (isset($ext['new_ends_at'])) {
                                        $ext['grace_period_ends_at'] = $ext['new_ends_at'];
                                        unset($ext['new_ends_at']);
                                    }
                                }
                                unset($ext);
                            }

                            $updateData = [
                                'ends_at' => $originalEndsAt,
                                'metadata' => $metadata ?: null,
                            ];

                            // For Academic subscriptions, also sync end_date
                            if ($subscription instanceof AcademicSubscription) {
                                $updateData['end_date'] = $originalEndsAt;
                            }

                            $subscription->update($updateData);

                            $this->line("  - Migrated #{$subscription->id}: ends_at restored to {$originalEndsAt}, grace_period_ends_at = {$currentEndsAt}");
                            $migrated++;
                        } catch (Exception $e) {
                            $this->error("  - Error #{$subscription->id}: {$e->getMessage()}");
                            $errors++;
                        }
                    }
                });
        }

        $this->newLine();
        $this->info("{$prefix}Migration complete:");
        $this->line("  Migrated: {$migrated}");
        $this->line("  Skipped:  {$skipped}");
        $this->line("  Errors:   {$errors}");

        return $errors > 0 ? 1 : 0;
    }

    private function getSubscriptionModels(): array
    {
        return [
            QuranSubscription::class => 'Quran Subscriptions',
            AcademicSubscription::class => 'Academic Subscriptions',
        ];
    }
}
