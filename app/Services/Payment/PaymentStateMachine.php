<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use InvalidArgumentException;

/**
 * State machine for payment status transitions.
 *
 * Enforces valid payment status transitions and provides
 * transition history tracking.
 */
class PaymentStateMachine
{
    /**
     * Valid status transitions.
     *
     * Each key is a current status, and its value is an array
     * of allowed next statuses.
     */
    private const TRANSITIONS = [
        'pending' => ['processing', 'completed', 'failed', 'cancelled', 'expired'],
        'processing' => ['completed', 'failed', 'cancelled'],
        'completed' => ['refunded'], // Allow refund after completion
        'failed' => ['pending'], // Allow retry
        'cancelled' => [], // Terminal state
        'expired' => ['pending'], // Allow re-initiation
        'refunded' => [], // Terminal state
    ];

    /**
     * Check if a transition is valid.
     *
     * @param  string|PaymentStatus  $from  Current status
     * @param  string|PaymentStatus  $to  Target status
     */
    public function canTransition(string|PaymentStatus $from, string|PaymentStatus $to): bool
    {
        // Convert enum to string value if needed
        $from = $from instanceof PaymentStatus ? $from->value : strtolower($from);
        $to = $to instanceof PaymentStatus ? $to->value : strtolower($to);

        // Same status is always "valid" (no-op)
        if ($from === $to) {
            return true;
        }

        $allowedTransitions = self::TRANSITIONS[$from] ?? [];

        return in_array($to, $allowedTransitions);
    }

    /**
     * Validate a transition and throw if invalid.
     *
     * @throws InvalidArgumentException If transition is not allowed
     */
    public function validateTransition(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Invalid payment status transition from '{$from}' to '{$to}'"
            );
        }
    }

    /**
     * Get allowed next statuses from current status.
     */
    public function getAllowedTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[strtolower($currentStatus)] ?? [];
    }

    /**
     * Check if a status is terminal (no further transitions allowed).
     */
    public function isTerminal(string $status): bool
    {
        return empty(self::TRANSITIONS[strtolower($status)] ?? []);
    }

    /**
     * Check if status indicates payment is successful.
     */
    public function isSuccessful(string|PaymentStatus $status): bool
    {
        $status = $status instanceof PaymentStatus ? $status->value : strtolower($status);

        return $status === 'completed';
    }

    /**
     * Check if status indicates payment failed.
     */
    public function isFailed(string $status): bool
    {
        return in_array(strtolower($status), ['failed', 'cancelled', 'expired']);
    }

    /**
     * Check if status indicates payment is in progress.
     */
    public function isInProgress(string $status): bool
    {
        return in_array(strtolower($status), ['pending', 'processing']);
    }

    /**
     * Get the appropriate status from gateway response.
     */
    public function mapGatewayStatus(string $gatewayStatus, string $gateway = 'paymob'): string
    {
        $maps = [
            'paymob' => [
                'SUCCESS' => 'completed',
                'success' => 'completed',
                'completed' => 'completed',
                'PENDING' => 'pending',
                'pending' => 'pending',
                'FAILED' => 'failed',
                'DECLINED' => 'failed',
                'VOIDED' => 'cancelled',
            ],
            // Add more gateway mappings as needed
        ];

        $map = $maps[$gateway] ?? [];

        return $map[$gatewayStatus] ?? 'pending';
    }

    /**
     * Get Arabic label for status.
     */
    public function getStatusLabel(string|PaymentStatus $status): string
    {
        $status = $status instanceof PaymentStatus ? $status->value : strtolower($status);

        return match ($status) {
            'pending' => 'قيد الانتظار',
            'processing' => 'جارٍ المعالجة',
            'completed' => 'ناجح',
            'failed' => 'فشل',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
            'expired' => 'منتهي الصلاحية',
            default => $status,
        };
    }

    /**
     * Get color class for status (for UI).
     */
    public function getStatusColor(string|PaymentStatus $status): string
    {
        $status = $status instanceof PaymentStatus ? $status->value : strtolower($status);

        return match ($status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            'refunded' => 'orange',
            'expired' => 'gray',
            default => 'gray',
        };
    }
}
