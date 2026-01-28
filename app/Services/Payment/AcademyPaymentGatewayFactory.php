<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Models\Academy;
use App\Services\Payment\DTOs\AcademyPaymentSettings;
use App\Services\Payment\Gateways\EasyKashGateway;
use App\Services\Payment\Gateways\PaymobGateway;
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
    ) {}

    /**
     * Get a payment gateway instance for the given academy.
     *
     * @param  Academy  $academy  The academy context
     * @param  string|null  $gatewayName  Specific gateway name (optional)
     * @return PaymentGatewayInterface
     *
     * @throws InvalidArgumentException If gateway is not enabled or not found
     */
    public function getGateway(Academy $academy, ?string $gatewayName = null): PaymentGatewayInterface
    {
        $settings = $academy->getPaymentSettings();

        // Determine which gateway to use
        $gateway = $gatewayName
            ?? $settings->getDefaultGateway()
            ?? config('payments.default', 'paymob');

        // Check if gateway is enabled for this academy
        if (! $settings->isGatewayEnabled($gateway)) {
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
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAvailableGatewaysForAcademy(Academy $academy): array
    {
        $settings = $academy->getPaymentSettings();
        $availableGateways = [];

        // Get all configured gateways from global config
        $allGateways = array_keys(config('payments.gateways', []));

        foreach ($allGateways as $gatewayName) {
            // Skip if not enabled for this academy
            if (! $settings->isGatewayEnabled($gatewayName)) {
                continue;
            }

            try {
                $gateway = $this->getGateway($academy, $gatewayName);

                // Only include if properly configured
                if ($gateway->isConfigured()) {
                    $availableGateways[$gatewayName] = $gateway;
                }
            } catch (\Exception $e) {
                // Gateway not available, skip it
                continue;
            }
        }

        return $availableGateways;
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
        } catch (\Exception $e) {
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
            default => throw new InvalidArgumentException(
                "Unknown payment gateway: {$gateway}"
            ),
        };
    }
}
