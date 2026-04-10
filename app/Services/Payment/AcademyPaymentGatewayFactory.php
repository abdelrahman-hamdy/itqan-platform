<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Models\Academy;
use App\Models\User;
use App\Services\Payment\DTOs\AcademyPaymentSettings;
use App\Services\Payment\Gateways\EasyKashGateway;
use App\Services\Payment\Gateways\PaymobGateway;
use App\Services\Payment\Gateways\TapGateway;
use Exception;
use InvalidArgumentException;

/**
 * Factory for creating academy-aware payment gateway instances.
 *
 * This factory resolves the correct gateway configuration by merging
 * academy-specific settings with global configuration.
 */
class AcademyPaymentGatewayFactory
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private UserCountryResolver $countryResolver,
    ) {}

    /**
     * Get a payment gateway instance for the given academy.
     *
     * @param  Academy  $academy  The academy context
     * @param  string|null  $gatewayName  Specific gateway name (optional)
     *
     * @throws InvalidArgumentException If gateway is not enabled or not found
     */
    public function getGateway(Academy $academy, ?string $gatewayName = null): PaymentGatewayInterface
    {
        $settings = $academy->getPaymentSettings();
        $fallbackDefault = config('payments.default', 'paymob');

        // Determine which gateway to use
        $gateway = $gatewayName
            ?? $settings->getDefaultGateway()
            ?? $fallbackDefault;

        // Check if gateway is enabled for this academy
        if (! $settings->isGatewayEnabled($gateway, $fallbackDefault)) {
            throw new InvalidArgumentException(
                "Payment gateway '{$gateway}' is not enabled for academy '{$academy->name}'"
            );
        }

        // Get merged configuration
        $globalConfig = config("payments.gateways.{$gateway}", []);
        $mergedConfig = $settings->getMergedGatewayConfig($gateway, $globalConfig);

        // Create gateway instance with merged config
        return $this->createGatewayInstance($gateway, $mergedConfig);
    }

    /**
     * Get all available gateways for an academy.
     *
     * When a user is provided, gateways are additionally filtered by the
     * academy's per-gateway country policy (`allowed_countries` /
     * `blocked_countries` under each gateway key in `payment_settings`),
     * intersected with each gateway's baseline `getSupportedCountries()`.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAvailableGatewaysForAcademy(Academy $academy, ?User $user = null): array
    {
        $settings = $academy->getPaymentSettings();
        $fallbackDefault = config('payments.default', 'paymob');
        $availableGateways = [];

        $userCountry = $user !== null
            ? $this->countryResolver->resolve($user, $academy)
            : null;

        // Get all configured gateways from global config
        $allGateways = array_keys(config('payments.gateways', []));

        foreach ($allGateways as $gatewayName) {
            // Skip if not enabled for this academy
            if (! $settings->isGatewayEnabled($gatewayName, $fallbackDefault)) {
                continue;
            }

            try {
                $gateway = $this->getGateway($academy, $gatewayName);

                // Only include if properly configured
                if (! $gateway->isConfigured()) {
                    continue;
                }

                // Apply country filter when we know the user's country.
                if ($userCountry !== null && ! $this->isGatewayAllowedForCountry($gateway, $userCountry, $settings)) {
                    continue;
                }

                $availableGateways[$gatewayName] = $gateway;
            } catch (Exception $e) {
                // Gateway not available, skip it
                continue;
            }
        }

        return $availableGateways;
    }

    /**
     * Decide whether a gateway is allowed for a given ISO alpha-2 country
     * under an academy's configured policy.
     *
     * Rules:
     *  - If the academy configured an `allowed_countries` list, the user's
     *    country must be in it (further intersected with the gateway's own
     *    supported countries when that baseline is non-empty).
     *  - Else if the academy configured a `blocked_countries` list, the user's
     *    country must not be in it, AND must still be supported by the gateway.
     *  - Else, fall back to the gateway's own baseline (empty baseline = allow).
     */
    private function isGatewayAllowedForCountry(
        PaymentGatewayInterface $gateway,
        string $country,
        AcademyPaymentSettings $settings,
    ): bool {
        $baseline = $gateway->getSupportedCountries();
        $cfg = $settings->getGatewayConfig($gateway->getName());
        $allowed = is_array($cfg['allowed_countries'] ?? null) ? $cfg['allowed_countries'] : [];
        $blocked = is_array($cfg['blocked_countries'] ?? null) ? $cfg['blocked_countries'] : [];

        if (! empty($allowed)) {
            $effective = ! empty($baseline)
                ? array_values(array_intersect($allowed, $baseline))
                : $allowed;

            return in_array($country, $effective, true);
        }

        if (! empty($blocked)) {
            if (in_array($country, $blocked, true)) {
                return false;
            }

            return empty($baseline) || in_array($country, $baseline, true);
        }

        return empty($baseline) || in_array($country, $baseline, true);
    }

    /**
     * Get the default gateway for an academy.
     */
    public function getDefaultGatewayForAcademy(Academy $academy): PaymentGatewayInterface
    {
        return $this->getGateway($academy);
    }

    /**
     * Check if a gateway is available and configured for an academy.
     */
    public function hasGateway(Academy $academy, string $gatewayName): bool
    {
        try {
            $gateway = $this->getGateway($academy, $gatewayName);

            return $gateway->isConfigured();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get available payment methods across all enabled gateways for an academy.
     *
     * @return array<string, array{gateway: string, display_name: string}>
     */
    public function getAvailablePaymentMethods(Academy $academy): array
    {
        $methods = [];
        $gateways = $this->getAvailableGatewaysForAcademy($academy);

        foreach ($gateways as $gatewayName => $gateway) {
            foreach ($gateway->getSupportedMethods() as $method) {
                if (! isset($methods[$method])) {
                    $methods[$method] = [
                        'gateway' => $gatewayName,
                        'display_name' => $gateway->getDisplayName(),
                    ];
                }
            }
        }

        return $methods;
    }

    /**
     * Create a gateway instance with the given configuration.
     */
    private function createGatewayInstance(string $gateway, array $config): PaymentGatewayInterface
    {
        return match ($gateway) {
            'paymob' => new PaymobGateway($config),
            'easykash' => new EasyKashGateway($config),
            'tap' => new TapGateway($config),
            default => throw new InvalidArgumentException(
                "Unknown payment gateway: {$gateway}"
            ),
        };
    }
}
