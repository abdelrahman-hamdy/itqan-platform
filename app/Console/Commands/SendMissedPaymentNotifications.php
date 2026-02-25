<?php

namespace App\Console\Commands;

use Exception;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\Academy;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMissedPaymentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-missed-notifications {--dry-run : Only show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for successful payments that missed webhook delivery';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no notifications will be sent');
        }

        // Process per-academy to enforce tenant scoping
        $academies = Academy::all();
        $this->info("Processing missed payment notifications across {$academies->count()} academies");

        foreach ($academies as $academy) {
            $this->processAcademy($academy, $dryRun, $notificationService);
        }

        return Command::SUCCESS;
    }

    /**
     * Process missed payment notifications for a single academy (tenant).
     */
    private function processAcademy(Academy $academy, bool $dryRun, NotificationService $notificationService): void
    {
        $this->line("Processing academy: {$academy->name} (ID: {$academy->id})");

        // Find payments that succeeded but never got payment notification
        // (webhook probably never arrived)
        $missedPaymentNotifications = Payment::where('academy_id', $academy->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereNull('payment_notification_sent_at')
            ->where('paid_at', '<', now()->subMinutes(15))
            ->get();

        $this->info("  Found {$missedPaymentNotifications->count()} payments missing payment notifications");

        foreach ($missedPaymentNotifications as $payment) {
            try {
                $user = $payment->user;
                if (! $user) {
                    $this->warn("Payment {$payment->id} has no user, skipping");
                    continue;
                }

                $this->line("Processing payment {$payment->id} for user {$user->name}");

                if (! $dryRun) {
                    // Send payment notification
                    $notificationData = [
                        'amount' => $payment->amount,
                        'currency' => $payment->currency ?? 'SAR',
                        'description' => $payment->description ?? __('payments.service.subscription_ref'),
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id ?? null,
                        'subdomain' => $payment->academy?->subdomain ?? config('multitenancy.default_tenant_subdomain'),
                    ];

                    if ($payment->payable) {
                        $notificationData['subscription_id'] = $payment->payable_id;
                        $notificationData['subscription_type'] = $payment->payable->getSubscriptionType();
                    }

                    $notificationService->sendPaymentSuccessNotification($user, $notificationData);

                    // Mark as sent
                    $payment->update(['payment_notification_sent_at' => now()]);

                    $this->info("✓ Payment notification sent for payment {$payment->id}");

                    Log::info('Missed payment notification sent', [
                        'payment_id' => $payment->id,
                        'user_id' => $user->id,
                        'paid_at' => $payment->paid_at,
                    ]);
                } else {
                    $this->line("  Would send payment notification to {$user->email}");
                }
            } catch (Exception $e) {
                $this->error("Failed to send payment notification for payment {$payment->id}: {$e->getMessage()}");

                Log::error('Failed to send missed payment notification', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Find payments with active subscriptions but no subscription notification
        $missedSubscriptionNotifications = Payment::where('academy_id', $academy->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereNotNull('payment_notification_sent_at') // Payment notification was sent
            ->whereNull('subscription_notification_sent_at') // But subscription notification wasn't
            ->where('paid_at', '<', now()->subMinutes(15))
            ->whereHas('payable', function ($query) {
                // Only process if subscription is active
                $query->where('status', SessionSubscriptionStatus::ACTIVE);
            })
            ->get();

        $this->info("  Found {$missedSubscriptionNotifications->count()} subscriptions missing activation notifications");

        foreach ($missedSubscriptionNotifications as $payment) {
            try {
                $subscription = $payment->payable;
                $student = $subscription?->student;

                if (! $student) {
                    $this->warn("Payment {$payment->id} has no student, skipping");
                    continue;
                }

                $this->line("Processing subscription notification for payment {$payment->id}");

                if (! $dryRun) {
                    // Get subscription name
                    $subscriptionName = 'Subscription';
                    if (method_exists($subscription, 'getSubscriptionDisplayName')) {
                        $subscriptionName = $subscription->getSubscriptionDisplayName();
                    }

                    $subscriptionType = $subscription->getSubscriptionType();

                    $notificationService->send(
                        $student,
                        NotificationType::SUBSCRIPTION_ACTIVATED,
                        [
                            'subscription_name' => $subscriptionName,
                            'subscription_type' => $subscriptionType,
                            'start_date' => $subscription->starts_at?->format('Y-m-d'),
                            'end_date' => $subscription->ends_at?->format('Y-m-d'),
                        ],
                        '/student/subscriptions',
                        [
                            'subscription_id' => $subscription->id,
                            'subscription_type' => $subscriptionType,
                        ],
                        true
                    );

                    // Mark as sent
                    $payment->update(['subscription_notification_sent_at' => now()]);

                    $this->info("✓ Subscription notification sent for payment {$payment->id}");

                    Log::info('Missed subscription notification sent', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscription->id,
                        'student_id' => $student->id,
                        'academy_id' => $academy->id,
                    ]);
                } else {
                    $this->line("  Would send subscription notification to {$student->email}");
                }
            } catch (Exception $e) {
                $this->error("Failed to send subscription notification for payment {$payment->id}: {$e->getMessage()}");

                Log::error('Failed to send missed subscription notification', [
                    'payment_id' => $payment->id,
                    'academy_id' => $academy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $totalProcessed = $missedPaymentNotifications->count() + $missedSubscriptionNotifications->count();

        if ($dryRun) {
            $this->info("  DRY RUN: would have processed {$totalProcessed} notifications for {$academy->name}");
        } else {
            $this->info("  Processed {$totalProcessed} missed notifications for {$academy->name}");
        }
    }
}
