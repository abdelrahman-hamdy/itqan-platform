<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Services\Payment\Gateways\PaymobGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Payment Gateway Manager following Laravel's Manager pattern.
 *
 * Provides a unified interface for interacting with different
 * payment gateways. Use $this->driver('paymob') to get a specific
 * gateway or $this->driver() for the default.
 *
 * @method PaymentGatewayInterface driver(string $driver = null)
 */
class PaymentGatewayManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('payments.default', 'paymob');
    }

    /**
     * Create Paymob gateway driver.
     */
    protected function createPaymobDriver(): PaymentGatewayInterface
    {
        $config = $this->config->get('payments.gateways.paymob', []);

        return new PaymobGateway($config);
    }

    /**
     * Create Tap gateway driver (placeholder for future).
     */
    protected function createTapDriver(): PaymentGatewayInterface
    {
        // TODO: Implement TapGateway when needed
        throw new InvalidArgumentException('Tap gateway is not yet implemented');
    }

    /**
     * Create Moyasar gateway driver (placeholder for future).
     */
    protected function createMoyasarDriver(): PaymentGatewayInterface
    {
        // TODO: Implement MoyasarGateway when needed
        throw new InvalidArgumentException('Moyasar gateway is not yet implemented');
    }

    /**
     * Get all available gateway names.
     *
     * @return array<string>
     */
    public function getAvailableGateways(): array
    {
        return array_keys($this->config->get('payments.gateways', []));
    }

    /**
     * Get all configured (active) gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getConfiguredGateways(): array
    {
        $gateways = [];

        foreach ($this->getAvailableGateways() as $name) {
            try {
                $gateway = $this->driver($name);
                if ($gateway->isConfigured()) {
                    $gateways[$name] = $gateway;
                }
            } catch (InvalidArgumentException $e) {
                // Gateway not implemented yet
                continue;
            }
        }

        return $gateways;
    }

    /**
     * Get the gateway for a specific payment method.
     *
     * @param string $method e.g., 'card', 'wallet', 'apple_pay'
     */
    public function getGatewayForMethod(string $method): ?PaymentGatewayInterface
    {
        foreach ($this->getConfiguredGateways() as $gateway) {
            if (in_array($method, $gateway->getSupportedMethods())) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Check if a specific gateway is available and configured.
     */
    public function hasGateway(string $name): bool
    {
        try {
            return $this->driver($name)->isConfigured();
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
