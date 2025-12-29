<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\BaseSubscription;

/**
 * Data Transfer Object for Subscription Toggle Results
 *
 * Represents the result of toggling a subscription's active status,
 * pausing/resuming, or changing subscription state.
 *
 * @property-read bool $success Whether the operation was successful
 * @property-read BaseSubscription|null $subscription The subscription instance
 * @property-read string|null $previousStatus Previous subscription status
 * @property-read string|null $newStatus New subscription status
 * @property-read string $message Human-readable operation result message
 */
readonly class SubscriptionToggleResult
{
    public function __construct(
        public bool $success,
        public ?BaseSubscription $subscription = null,
        public ?string $previousStatus = null,
        public ?string $newStatus = null,
        public string $message = '',
        public array $metadata = [],
    ) {}

    /**
     * Create a successful toggle result
     */
    public static function success(
        BaseSubscription $subscription,
        string $previousStatus,
        string $newStatus,
        ?string $message = null,
        array $metadata = []
    ): self {
        $defaultMessage = $message ?? 'تم تحديث حالة الاشتراك بنجاح';

        return new self(
            success: true,
            subscription: $subscription,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            message: $defaultMessage,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed toggle result
     */
    public static function failure(
        string $message,
        ?BaseSubscription $subscription = null,
        ?string $previousStatus = null,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            subscription: $subscription,
            previousStatus: $previousStatus,
            newStatus: null,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            subscription: $data['subscription'] ?? null,
            previousStatus: $data['previousStatus'] ?? $data['previous_status'] ?? null,
            newStatus: $data['newStatus'] ?? $data['new_status'] ?? null,
            message: $data['message'] ?? '',
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array for JSON responses
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'subscription_id' => $this->subscription?->id,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if the operation was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the operation failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if status actually changed
     */
    public function hasStatusChanged(): bool
    {
        return $this->success && $this->previousStatus !== $this->newStatus;
    }
}
