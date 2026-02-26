<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\BaseSession;

/**
 * Data Transfer Object for session operation results
 *
 * Used by SessionManagementService to return structured results for
 * session operations like creation, updates, status changes, and cancellations.
 *
 * @property-read bool $success Whether the operation was successful
 * @property-read BaseSession|null $session The session instance
 * @property-read string $operation Operation type (create, update, cancel, reschedule, etc.)
 * @property-read string|null $previousStatus Previous session status (if applicable)
 * @property-read string|null $newStatus New session status (if applicable)
 * @property-read string $message Human-readable operation result message
 * @property-read array $metadata Additional operation metadata
 */
readonly class SessionOperationResult
{
    public function __construct(
        public bool $success,
        public ?BaseSession $session,
        public string $operation,
        public ?string $previousStatus = null,
        public ?string $newStatus = null,
        public string $message = '',
        public array $metadata = [],
    ) {}

    /**
     * Create a successful operation result
     */
    public static function success(
        BaseSession $session,
        string $operation,
        ?string $message = null,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        array $metadata = []
    ): self {
        $defaultMessage = $message ?? ucfirst($operation).' completed successfully';

        return new self(
            success: true,
            session: $session,
            operation: $operation,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            message: $defaultMessage,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed operation result
     */
    public static function failure(
        string $operation,
        string $message,
        ?BaseSession $session = null,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            session: $session,
            operation: $operation,
            previousStatus: null,
            newStatus: null,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Create result for session creation
     */
    public static function created(
        BaseSession $session,
        ?string $message = null,
        array $metadata = []
    ): self {
        return self::success(
            session: $session,
            operation: 'create',
            message: $message ?? 'Session created successfully',
            newStatus: $session->status?->value ?? 'scheduled',
            metadata: $metadata,
        );
    }

    /**
     * Create result for session update
     */
    public static function updated(
        BaseSession $session,
        ?string $message = null,
        array $metadata = []
    ): self {
        return self::success(
            session: $session,
            operation: 'update',
            message: $message ?? 'Session updated successfully',
            metadata: $metadata,
        );
    }

    /**
     * Create result for session cancellation
     */
    public static function cancelled(
        BaseSession $session,
        string $previousStatus,
        ?string $message = null,
        array $metadata = []
    ): self {
        return self::success(
            session: $session,
            operation: 'cancel',
            message: $message ?? 'Session cancelled successfully',
            previousStatus: $previousStatus,
            newStatus: 'cancelled',
            metadata: $metadata,
        );
    }

    /**
     * Create result for session rescheduling
     */
    public static function rescheduled(
        BaseSession $session,
        ?string $message = null,
        array $metadata = []
    ): self {
        return self::success(
            session: $session,
            operation: 'reschedule',
            message: $message ?? 'Session rescheduled successfully',
            metadata: array_merge(['rescheduled_at' => now()], $metadata),
        );
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            session: isset($data['session']) && $data['session'] instanceof \App\Models\BaseSession
                ? $data['session']
                : null,
            operation: $data['operation'] ?? 'unknown',
            previousStatus: $data['previousStatus'] ?? $data['previous_status'] ?? null,
            newStatus: $data['newStatus'] ?? $data['new_status'] ?? null,
            message: $data['message'] ?? '',
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'session_id' => $this->session?->id,
            'operation' => $this->operation,
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
        return ! $this->success;
    }

    /**
     * Check if status changed
     */
    public function hasStatusChanged(): bool
    {
        return $this->previousStatus !== null
            && $this->newStatus !== null
            && $this->previousStatus !== $this->newStatus;
    }

    /**
     * Get operation type
     */
    public function getOperationType(): string
    {
        return $this->operation;
    }
}
