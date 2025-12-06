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
     * Get Arabic label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::REDIRECT => 'إعادة توجيه',
            self::IFRAME => 'نموذج مضمن',
            self::API_ONLY => 'API مباشر',
        };
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
}
