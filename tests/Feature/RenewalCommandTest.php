<?php

namespace Tests\Feature;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Academy;
use App\Models\QuranSubscription;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Notifications\Admin\RenewalBatchFailureNotification;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RenewalCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        // Create academy
        $this->academy = Academy::factory()->create([
            'name' => 'Test Academy',
            'subdomain' => 'test',
        ]);
    }

    protected function createRenewalDueSubscription(bool $withSavedCard = true): QuranSubscription
    {
        $student = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => \App\Enums\UserType::STUDENT,
        ]);

        if ($withSavedCard) {
            SavedPaymentMethod::factory()->create([
                'user_id' => $student->id,
                'academy_id' => $this->academy->id,
                'gateway' => 'paymob',
                'is_active' => true,
                'expires_at' => now()->addYear(),
            ]);
        }

        return QuranSubscription::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'auto_renew' => true,
            'next_billing_date' => now()->subDay(), // Due for renewal
            'ends_at' => now()->addDays(7),
        ]);
    }

    /** @test */
    public function it_does_not_charge_in_dry_run_mode()
    {
        // Create subscriptions
        $this->createRenewalDueSubscription();
        $this->createRenewalDueSubscription();

        // Mock payment service - should not be called
        $paymentService = $this->mock(PaymentService::class);
        $paymentService->shouldNotReceive('processSubscriptionRenewal');

        // Run command in dry-run mode
        $this->artisan('subscriptions:process-renewals --dry-run')
            ->assertExitCode(0);

        // Verify subscriptions were not updated
        $this->assertEquals(2, QuranSubscription::where('payment_status', SubscriptionPaymentStatus::PAID)->count());
    }

    /** @test */
    public function it_processes_only_due_subscriptions()
    {
        // Create due subscription
        $dueSubscription = $this->createRenewalDueSubscription();

        // Create not-due subscription
        $notDueSubscription = $this->createRenewalDueSubscription();
        $notDueSubscription->update([
            'next_billing_date' => now()->addDays(10),
        ]);

        // Create subscription without auto-renew
        $noAutoRenewSubscription = $this->createRenewalDueSubscription();
        $noAutoRenewSubscription->update([
            'auto_renew' => false,
        ]);

        // Mock successful payment
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->once()->andReturn([
                'success' => true,
                'transaction_id' => 'test_transaction',
            ]);
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->expectsOutput('Subscription renewal processing completed.')
            ->assertExitCode(0);

        // Only the due subscription should be processed
        $dueSubscription->refresh();
        $this->assertTrue($dueSubscription->next_billing_date->isFuture());

        // Others should remain unchanged
        $notDueSubscription->refresh();
        $this->assertEquals(now()->addDays(10)->format('Y-m-d'), $notDueSubscription->next_billing_date->format('Y-m-d'));

        $noAutoRenewSubscription->refresh();
        $this->assertEquals(now()->subDay()->format('Y-m-d'), $noAutoRenewSubscription->next_billing_date->format('Y-m-d'));
    }

    /** @test */
    public function it_triggers_batch_failure_alert_when_failure_rate_exceeds_20_percent()
    {
        Notification::fake();

        // Create 10 subscriptions - 3 with saved cards (30% will fail)
        for ($i = 0; $i < 3; $i++) {
            $this->createRenewalDueSubscription(true); // Will succeed
        }

        for ($i = 0; $i < 7; $i++) {
            $this->createRenewalDueSubscription(false); // Will fail (no card)
        }

        // Mock payment service - succeed only for subscriptions with a saved card
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')
                ->andReturnUsing(function ($subscription, $amount = null) {
                    $hasSavedCard = \App\Models\SavedPaymentMethod::where('user_id', $subscription->student_id)->exists();
                    if ($hasSavedCard) {
                        return ['success' => true, 'transaction_id' => 'test_transaction'];
                    }

                    return ['success' => false, 'error' => 'No saved payment method'];
                });
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->assertExitCode(0);

        // Assert notification was sent (70% failure rate)
        $adminEmail = config('app.admin_email');
        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            RenewalBatchFailureNotification::class,
            function ($notification, $channels, $notifiable) use ($adminEmail) {
                return $notifiable->routes['mail'] === $adminEmail;
            }
        );
    }

    /** @test */
    public function it_does_not_trigger_alert_when_failure_rate_is_below_20_percent()
    {
        Notification::fake();

        // Create 10 subscriptions - 9 with saved cards (10% will fail)
        for ($i = 0; $i < 9; $i++) {
            $this->createRenewalDueSubscription(true); // Will succeed
        }

        $this->createRenewalDueSubscription(false); // Will fail (no card)

        // Mock payment service
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => true,
                'transaction_id' => 'test_transaction',
            ]);
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->assertExitCode(0);

        // Assert notification was NOT sent (only 10% failure rate)
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_logs_renewal_processing_results()
    {
        // Create subscriptions
        $this->createRenewalDueSubscription();

        // Mock payment service
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => true,
                'transaction_id' => 'test_transaction',
            ]);
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->expectsOutput('Subscription renewal processing completed.')
            ->assertExitCode(0);

        // Verify log file exists
        $logPath = storage_path('logs/laravel.log');
        $this->assertFileExists($logPath);

        // Verify log contains renewal information
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Subscription renewal processing completed.', $logContent);
    }

    /** @test */
    public function it_respects_atomic_locks_to_prevent_duplicate_processing()
    {
        $this->markTestSkipped('Cannot test atomic locks with in-memory mocks across separate process spawns');

        // Create subscription
        $subscription = $this->createRenewalDueSubscription();

        // Mock payment service
        $callCount = 0;
        $this->mock(PaymentService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                sleep(2); // Simulate slow payment processing

                return [
                    'success' => true,
                    'transaction_id' => 'test_transaction',
                ];
            });
        });

        // Simulate concurrent command execution
        $process1 = new \Symfony\Component\Process\Process(['php', 'artisan', 'subscriptions:process-renewals']);
        $process2 = new \Symfony\Component\Process\Process(['php', 'artisan', 'subscriptions:process-renewals']);

        $process1->start();
        usleep(100000); // 100ms delay
        $process2->start();

        $process1->wait();
        $process2->wait();

        // Verify payment was only charged once (lock prevented duplicate)
        $this->assertEquals(1, $callCount, 'Payment should only be charged once due to atomic lock');
    }

    /** @test */
    public function it_continues_processing_after_individual_subscription_failure()
    {
        // Create 3 subscriptions
        $subscription1 = $this->createRenewalDueSubscription();
        $subscription2 = $this->createRenewalDueSubscription();
        $subscription3 = $this->createRenewalDueSubscription();

        // Mock payment service - first fails, others succeed
        $callCount = 0;
        $this->mock(PaymentService::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'success' => false,
                        'error' => 'Payment declined',
                    ];
                }

                return [
                    'success' => true,
                    'transaction_id' => 'test_transaction',
                ];
            });
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->assertExitCode(0);

        // Verify all 3 were attempted
        $this->assertEquals(3, $callCount);

        // Verify first subscription failed, others succeeded
        $subscription1->refresh();
        $this->assertEquals(SubscriptionPaymentStatus::FAILED, $subscription1->payment_status);

        $subscription2->refresh();
        $this->assertTrue($subscription2->next_billing_date->isFuture());

        $subscription3->refresh();
        $this->assertTrue($subscription3->next_billing_date->isFuture());
    }

    /** @test */
    public function it_clears_cache_lock_after_completion()
    {
        // Create subscription
        $subscription = $this->createRenewalDueSubscription();

        // Mock payment service
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => true,
                'transaction_id' => 'test_transaction',
            ]);
        });

        // Run command
        $this->artisan('subscriptions:process-renewals')
            ->assertExitCode(0);

        // Verify lock is released
        $lockKey = "renewal_processing:{$subscription->id}";
        $this->assertFalse(Cache::has($lockKey), 'Lock should be released after processing');
    }
}
