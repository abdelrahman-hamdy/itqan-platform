<?php

namespace App\Enums;

/**
 * Defines the payment flow type for a gateway.
 *
 * Different gateways require different user interaction patterns:
 * - REDIRECT: User is redirected to gateway's hosted page
 * - IFRAME: Gateway's payment form embedded in an iframe
 * - API_ONLY: Direct API integration, no user redirect needed
 */
enum PaymentFlowType: string
{
    case REDIRECT = 'redirect';
    case IFRAME = 'iframe';
    case API_ONLY = 'api_only';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.payment_flow_type.' . $this->value);
    }

    /**
     * Check if this flow requires user redirect.
     */
    public function requiresRedirect(): bool
    {
        return $this === self::REDIRECT;
    }

    /**
     * Check if this flow can be embedded in page.
     */
    public function canEmbed(): bool
    {
        return $this === self::IFRAME;
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
