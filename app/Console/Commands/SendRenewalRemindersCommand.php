<?php

namespace App\Console\Commands;

use App\Services\CronJobLogger;
use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SendRenewalRemindersCommand
 *
 * Sends renewal reminder notifications to students with upcoming subscription renewals.
 * Designed to run daily via scheduler.
 *
 * REMINDER SCHEDULE:
 * - 7 days before renewal: First reminder
 * - 3 days before renewal: Final reminder
 */
class SendRenewalRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:send-reminders
                          {--dry-run : Show what would be done without actually sending}
                          {--details : Show detailed output for each reminder}';

    /**
     * The console command description.
     */
    protected $description = 'Send renewal reminder notifications for upcoming subscription renewals';

    public function __construct(
        private SubscriptionRenewalService $renewalService
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
        $executionData = CronJobLogger::logCronStart('subscriptions:send-reminders', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
        ]);

        $this->info('Starting renewal reminder processing...');
        $this->info('Current time: ' . now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual reminders will be sent');
        }

        try {
            if ($isDryRun) {
                $results = $this->simulateReminders($isVerbose);
            } else {
                $results = $this->renewalService->sendRenewalReminders();
            }

            $this->displayResults($results, $isDryRun, $isVerbose);

            // Log completion
            CronJobLogger::logCronEnd('subscriptions:send-reminders', $executionData, $results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Renewal reminder processing failed: ' . $e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            CronJobLogger::logCronError('subscriptions:send-reminders', $executionData, $e);

            Log::error('Renewal reminder processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Simulate reminders for dry-run mode
     */
    private function simulateReminders(bool $isVerbose): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'reminders' => [
                '7_day' => [],
                '3_day' => [],
            ],
        ];

        // Check for 7-day reminders
        $sevenDayTarget = now()->addDays(7)->toDateString();
        $threeDayTarget = now()->addDays(3)->toDateString();

        // Get subscriptions needing reminders by checking next_billing_date
        $allActive = collect();

        // Query Quran subscriptions
        $quranSubs = \App\Models\QuranSubscription::where('auto_renew', true)
            ->where('status', \App\Enums\SubscriptionStatus::ACTIVE)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(next_billing_date)'), [$sevenDayTarget, $threeDayTarget])
            ->with('student.user')
            ->get();
        $allActive = $allActive->merge($quranSubs);

        // Query Academic subscriptions
        $academicSubs = \App\Models\AcademicSubscription::where('auto_renew', true)
            ->where('status', \App\Enums\SubscriptionStatus::ACTIVE)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(next_billing_date)'), [$sevenDayTarget, $threeDayTarget])
            ->with('student.user')
            ->get();
        $allActive = $allActive->merge($academicSubs);

        foreach ($allActive as $subscription) {
            $billingDate = $subscription->next_billing_date?->toDateString();
            $daysUntil = $billingDate === $sevenDayTarget ? 7 : 3;

            $info = [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'type' => $subscription->getSubscriptionType(),
                'student' => $subscription->student?->user?->name ?? 'Unknown',
                'renewal_price' => $subscription->calculateRenewalPrice(),
                'next_billing_date' => $billingDate,
                'days_until' => $daysUntil,
            ];

            if ($daysUntil === 7) {
                $results['reminders']['7_day'][] = $info;
            } else {
                $results['reminders']['3_day'][] = $info;
            }

            $results['sent']++;

            if ($isVerbose) {
                $this->info("Would send {$daysUntil}-day reminder for: {$info['code']} ({$info['type']})");
                $this->info("  Student: {$info['student']}");
                $this->info("  Renewal date: {$billingDate}");
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
        $this->info("Renewal Reminders {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        if ($results['sent'] === 0) {
            $this->info('No renewal reminders to send at this time.');
            return;
        }

        if ($isDryRun) {
            $sevenDay = count($results['reminders']['7_day'] ?? []);
            $threeDay = count($results['reminders']['3_day'] ?? []);

            $this->info("7-day reminders that would be sent: {$sevenDay}");
            $this->info("3-day reminders that would be sent: {$threeDay}");
            $this->info("Total reminders: {$results['sent']}");
        } else {
            $this->info("Reminders sent: {$results['sent']}");
            $this->info("Skipped: {$results['skipped']}");
        }

        // Show errors if any
        if (!empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - Subscription {$error['subscription_id']}: {$error['error']}");
            }
        }

        $this->info('');
        $this->info('Renewal reminder processing completed.');
    }
}
