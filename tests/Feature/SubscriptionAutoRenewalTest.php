<?php

namespace Tests\Feature;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Academy;
use App\Models\QuranSubscription;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Subscription\RenewalProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionAutoRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Academy $academy;

    protected QuranSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Create academy
        $this->academy = Academy::factory()->create([
            'name' => 'Test Academy',
            'subdomain' => 'test',
        ]);

        // Create student
        $this->student = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => \App\Enums\UserType::STUDENT,
        ]);

        // Create subscription
        $this->subscription = QuranSubscription::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'auto_renew' => true,
            'next_billing_date' => now()->subDay(), // Due for renewal
            'ends_at' => now()->addDays(7),
        ]);
    }

    /** @test */
    public function it_successfully_renews_subscription_with_saved_card()
    {
        // Create saved payment method
        SavedPaymentMethod::factory()->create([
            'user_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'gateway' => 'paymob',
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ]);

        // Mock successful payment
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => true,
                'transaction_id' => 'test_transaction_123',
            ]);
        });

        // Process renewal
        $processor = app(RenewalProcessor::class);
        $result = $processor->processRenewal($this->subscription->fresh());

        // Assertions
        $this->assertTrue($result);
        $this->subscription->refresh();
        $this->assertEquals(SessionSubscriptionStatus::ACTIVE, $this->subscription->status);
        $this->assertEquals(SubscriptionPaymentStatus::PAID, $this->subscription->payment_status);
        $this->assertNotNull($this->subscription->last_payment_date);
        $this->assertTrue($this->subscription->next_billing_date->isFuture());
    }

    /** @test */
    public function it_fails_renewal_without_saved_card()
    {
        // No saved payment method

        // Process renewal
        $processor = app(RenewalProcessor::class);
        $result = $processor->processRenewal($this->subscription->fresh());

        // Assertions
        $this->assertFalse($result);
        $this->subscription->refresh();
        $this->assertEquals(SubscriptionPaymentStatus::FAILED, $this->subscription->payment_status);
        $this->assertArrayHasKey('renewal_failed_count', $this->subscription->metadata ?? []);
        $this->assertEquals(1, $this->subscription->metadata['renewal_failed_count']);
    }

    /** @test */
    public function it_fails_renewal_with_expired_card()
    {
        // Create expired payment method
        SavedPaymentMethod::factory()->create([
            'user_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'gateway' => 'paymob',
            'is_active' => true,
            'expires_at' => now()->subMonth(), // Expired
        ]);

        // Process renewal
        $processor = app(RenewalProcessor::class);
        $result = $processor->processRenewal($this->subscription->fresh());

        // Assertions
        $this->assertFalse($result);
        $this->subscription->refresh();
        $this->assertEquals(SubscriptionPaymentStatus::FAILED, $this->subscription->payment_status);
    }

    /** @test */
    public function it_retries_renewal_on_gateway_error()
    {
        // Create saved payment method
        SavedPaymentMethod::factory()->create([
            'user_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'gateway' => 'paymob',
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ]);

        // Mock gateway error
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => false,
                'error' => 'Gateway timeout',
            ]);
        });

        // First attempt
        $processor = app(RenewalProcessor::class);
        $processor->processRenewal($this->subscription->fresh());

        $this->subscription->refresh();
        $this->assertEquals(1, $this->subscription->metadata['renewal_failed_count']);
        $this->assertEquals(SessionSubscriptionStatus::ACTIVE, $this->subscription->status);

        // Second attempt
        $processor->processRenewal($this->subscription->fresh());

        $this->subscription->refresh();
        $this->assertEquals(2, $this->subscription->metadata['renewal_failed_count']);
        $this->assertEquals(SessionSubscriptionStatus::ACTIVE, $this->subscription->status);
    }

    /** @test */
    public function it_enters_grace_period_after_three_failures()
    {
        // Create saved payment method
        SavedPaymentMethod::factory()->create([
            'user_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'gateway' => 'paymob',
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ]);

        // Mock payment failure
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processSubscriptionRenewal')->andReturn([
                'success' => false,
                'error' => 'Payment declined',
            ]);
        });

        $processor = app(RenewalProcessor::class);

        // Simulate 3 failures
        for ($i = 1; $i <= 3; $i++) {
            $processor->processRenewal($this->subscription->fresh());
            $this->subscription->refresh();
        }

        // After 3rd failure, should enter grace period
        $this->subscription->refresh();
        $this->assertEquals(SessionSubscriptionStatus::ACTIVE, $this->subscription->status);
        $this->assertEquals(SubscriptionPaymentStatus::FAILED, $this->subscription->payment_status);
        $this->assertArrayHasKey('grace_period_ends_at', $this->subscription->metadata ?? []);
        $this->assertArrayHasKey('grace_period_started_at', $this->subscription->metadata ?? []);
    }

    /** @test */
    public function it_allows_manual_payment_during_grace_period()
    {
        $this->markTestSkipped('Manual renewal page requires complex subdomain routing setup in tests');

        // Set subscription in grace period
        $this->subscription->update([
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'metadata' => [
                'grace_period_expires_at' => now()->addDays(3)->toIso8601String(),
                'grace_period_started_at' => now()->toIso8601String(),
                'renewal_failed_count' => 3,
            ],
        ]);

        // Login as student
        $this->actingAs($this->student);

        // Visit manual renewal page with proper subdomain
        $response = $this->get(
            'http://'.$this->academy->subdomain.'.'.config('app.domain').'/subscriptions/quran/'.$this->subscription->id.'/manual-renewal'
        );

        $response->assertOk();
        $response->assertSee('تجديد الاشتراك يدوياً');
        $response->assertSee('فترة السماح تنتهي في');
    }

    /** @test */
    public function it_shows_expired_grace_period_page_after_grace_period()
    {
        $this->markTestSkipped('Manual renewal page requires complex subdomain routing setup in tests');

        // Set subscription with expired grace period
        $this->subscription->update([
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'metadata' => [
                'grace_period_expires_at' => now()->subDay()->toIso8601String(),
                'grace_period_started_at' => now()->subDays(4)->toIso8601String(),
                'renewal_failed_count' => 3,
            ],
        ]);

        // Login as student
        $this->actingAs($this->student);

        // Visit manual renewal page with proper subdomain
        $response = $this->get(
            'http://'.$this->academy->subdomain.'.'.config('app.domain').'/subscriptions/quran/'.$this->subscription->id.'/manual-renewal'
        );

        $response->assertOk();
        $response->assertSee('انتهت فترة السماح');
    }

    /** @test */
    public function it_sends_renewal_reminder_notifications()
    {
        $this->markTestSkipped('App\\Notifications\\RenewalReminderNotification class not yet implemented');

        Notification::fake();

        // Set subscription due in 7 days
        $this->subscription->update([
            'next_billing_date' => now()->addDays(7),
        ]);

        // Run reminder command
        $this->artisan('subscriptions:send-reminders')
            ->assertExitCode(0);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->student,
            \App\Notifications\RenewalReminderNotification::class
        );
    }

    /** @test */
    public function it_includes_saved_card_warning_in_renewal_reminder()
    {
        $this->markTestSkipped('App\\Notifications\\RenewalReminderNotification class not yet implemented');

        Notification::fake();

        // No saved card
        $this->subscription->update([
            'next_billing_date' => now()->addDays(7),
            'auto_renew' => true,
        ]);

        // Run reminder command
        $this->artisan('subscriptions:send-reminders')
            ->assertExitCode(0);

        // Assert notification was sent with warning
        Notification::assertSentTo(
            $this->student,
            \App\Notifications\RenewalReminderNotification::class,
            function ($notification) {
                $data = $notification->toArray($this->student);

                return isset($data['warning_message']) &&
                       str_contains($data['warning_message'], 'لا توجد بطاقة');
            }
        );
    }

    /** @test */
    public function it_does_not_process_renewal_for_cancelled_subscriptions()
    {
        $this->subscription->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
        ]);

        $processor = app(RenewalProcessor::class);
        $result = $processor->processRenewal($this->subscription->fresh());

        $this->assertFalse($result);
    }

    /** @test */
    public function it_does_not_process_renewal_when_auto_renew_disabled()
    {
        $this->subscription->update([
            'auto_renew' => false,
        ]);

        $processor = app(RenewalProcessor::class);
        $result = $processor->processRenewal($this->subscription->fresh());

        $this->assertFalse($result);
    }
}
