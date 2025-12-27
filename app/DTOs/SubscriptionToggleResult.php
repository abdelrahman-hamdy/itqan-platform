<?php

namespace App\DTOs;

/**
 * Data Transfer Object for Subscription Toggle Results
 *
 * Represents the result of toggling a subscription's active status,
 * pausing/resuming, or changing subscription state.
 */
class SubscriptionToggleResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $message = null,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a successful toggle result
     */
    public static function success(
        string $previousStatus,
        string $newStatus,
        ?string $message = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            message: $message ?? 'تم تحديث حالة الاشتراك بنجاح',
            metadata: $metadata,
        );
    }

    /**
     * Create a failed toggle result
     */
    public static function failure(
        string $currentStatus,
        string $errorMessage,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            previousStatus: $currentStatus,
            newStatus: $currentStatus,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Check if status actually changed
     */
    public function statusChanged(): bool
    {
        return $this->success && $this->previousStatus !== $this->newStatus;
    }

    /**
     * Convert to array for JSON responses
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
        ];

        if ($this->success) {
            $result['message'] = $this->message;
        } else {
            $result['error'] = $this->errorMessage;
        }

        if (! empty($this->metadata)) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }
}
