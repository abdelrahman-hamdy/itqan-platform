<?php

namespace App\Console\Commands;

use App\Services\CronJobLogger;
use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSubscriptionRenewalsCommand
 *
 * Processes automatic subscription renewals for Quran and Academic subscriptions.
 * Designed to run daily via scheduler.
 *
 * DESIGN DECISIONS:
 * - NO grace period: Payment failure immediately expires subscription
 * - Processes all academies in a single run
 * - Logs all results for audit trail
 * - Supports dry-run mode for testing
 */
class ProcessSubscriptionRenewalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:process-renewals
                          {--dry-run : Show what would be done without actually processing}
                          {--details : Show detailed output for each subscription}';

    /**
     * The console command description.
     */
    protected $description = 'Process automatic subscription renewals for Quran and Academic subscriptions';

    public function __construct(
        private SubscriptionRenewalService $renewalService,
        private CronJobLogger $cronJobLogger
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('subscriptions:process-renewals', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
        ]);

        $this->info('Starting subscription renewal processing...');
        $this->info('Current time: '.now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual renewals will be processed');
        }

        try {
            if ($isDryRun) {
                $results = $this->simulateRenewals($isVerbose);
            } else {
                $results = $this->renewalService->processAllDueRenewals();
            }

            $this->displayResults($results, $isDryRun, $isVerbose);

            // Log completion
            $this->cronJobLogger->logCronEnd('subscriptions:process-renewals', $executionData, $results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Subscription renewal processing failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('subscriptions:process-renewals', $executionData, $e);

            Log::error('Subscription renewal processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Simulate renewals for dry-run mode
     */
    private function simulateRenewals(bool $isVerbose): array
    {
        $dueSubscriptions = $this->renewalService->getDueForRenewal();

        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'subscriptions' => [],
        ];

        foreach ($dueSubscriptions as $subscription) {
            $results['processed']++;

            $info = [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'type' => $subscription->getSubscriptionType(),
                'student' => $subscription->student?->user?->name ?? 'Unknown',
                'renewal_price' => $subscription->calculateRenewalPrice(),
                'next_billing_date' => $subscription->next_billing_date?->format('Y-m-d'),
            ];

            $results['subscriptions'][] = $info;

            if ($isVerbose) {
                $this->info("Would process renewal for: {$info['code']} ({$info['type']})");
                $this->info("  Student: {$info['student']}");
                $this->info("  Amount: {$info['renewal_price']} SAR");
                $this->line('');
            }
        }

        return $results;
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun, bool $isVerbose): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Renewal Processing {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        if ($results['processed'] === 0) {
            $this->info('No subscriptions due for renewal at this time.');

            return;
        }

        $this->info("Processed: {$results['processed']}");

        if (! $isDryRun) {
            $this->info("Successful: {$results['successful']}");
            $this->info("Failed: {$results['failed']}");

            if ($results['failed'] > 0) {
                $this->warn('Failed renewals have been expired (NO grace period).');
            }
        } else {
            $this->info("Subscriptions that would be processed: {$results['processed']}");

            if (! empty($results['subscriptions'])) {
                $totalAmount = array_sum(array_column($results['subscriptions'], 'renewal_price'));
                $this->info("Total renewal amount: {$totalAmount} SAR");
            }
        }

        // Show errors if any
        if (! empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - Subscription {$error['subscription_code']}: {$error['error']}");
            }
        }

        $this->info('');
        $this->info('Subscription renewal processing completed.');
    }
}
