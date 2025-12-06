<?php

namespace App\Services\Payment;

use App\Enums\PaymentResultStatus;
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
        'pending' => ['processing', 'failed', 'cancelled', 'expired'],
        'processing' => ['success', 'failed', 'cancelled'],
        'success' => ['refunded', 'partially_refunded'],
        'failed' => ['pending'], // Allow retry
        'cancelled' => [], // Terminal state
        'refunded' => [], // Terminal state
        'partially_refunded' => ['refunded', 'partially_refunded'],
        'expired' => ['pending'], // Allow re-initiation
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransition(string $from, string $to): bool
    {
        $from = strtolower($from);
        $to = strtolower($to);

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
    public function isSuccessful(string $status): bool
    {
        return in_array(strtolower($status), ['success', 'partially_refunded']);
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
     * Check if refund is allowed for this status.
     */
    public function canRefund(string $status): bool
    {
        return in_array(strtolower($status), ['success', 'partially_refunded']);
    }

    /**
     * Get the appropriate status from gateway response.
     */
    public function mapGatewayStatus(string $gatewayStatus, string $gateway = 'paymob'): string
    {
        $maps = [
            'paymob' => [
                'SUCCESS' => 'success',
                'success' => 'success',
                'PENDING' => 'pending',
                'pending' => 'pending',
                'FAILED' => 'failed',
                'DECLINED' => 'failed',
                'VOIDED' => 'cancelled',
                'REFUNDED' => 'refunded',
            ],
            // Add more gateway mappings as needed
        ];

        $map = $maps[$gateway] ?? [];

        return $map[$gatewayStatus] ?? 'pending';
    }

    /**
     * Get Arabic label for status.
     */
    public function getStatusLabel(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'قيد الانتظار',
            'processing' => 'جارٍ المعالجة',
            'success' => 'ناجح',
            'failed' => 'فشل',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
            'partially_refunded' => 'مسترد جزئياً',
            'expired' => 'منتهي الصلاحية',
            default => $status,
        };
    }

    /**
     * Get color class for status (for UI).
     */
    public function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'success' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            'refunded' => 'purple',
            'partially_refunded' => 'purple',
            'expired' => 'gray',
            default => 'gray',
        };
    }
}
