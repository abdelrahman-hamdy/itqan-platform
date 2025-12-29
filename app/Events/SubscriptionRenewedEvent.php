<?php

namespace App\Events;

use App\Models\BaseSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a subscription is successfully renewed.
 *
 * Use cases:
 * - Send renewal confirmation to student/parent
 * - Notify teacher of continued subscription
 * - Update session schedules
 * - Log renewal for analytics
 */
class SubscriptionRenewedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly BaseSubscription $subscription,
        public readonly string $subscriptionType,
        public readonly bool $wasAutoRenewal = false
    ) {}

    /**
     * Get the subscription model.
     */
    public function getSubscription(): BaseSubscription
    {
        return $this->subscription;
    }

    /**
     * Get the subscription type (quran, academic, course).
     */
    public function getSubscriptionType(): string
    {
        return $this->subscriptionType;
    }

    /**
     * Check if this was an automatic renewal.
     */
    public function wasAutoRenewal(): bool
    {
        return $this->wasAutoRenewal;
    }
}
