<?php

namespace App\Services\Payment\DTOs;

/**
 * Data Transfer Object for per-academy payment settings.
 *
 * Handles parsing of payment_settings JSON from Academy model
 * and provides methods for gateway configuration resolution.
 */
readonly class AcademyPaymentSettings
{
    public function __construct(
        public ?string $defaultGateway = null,
        public array $enabledGateways = [],
        public array $gatewayConfigs = [],
    ) {}

    /**
     * Create from array (typically from Academy model's payment_settings).
     */
    public static function fromArray(?array $data): self
    {
        if (empty($data)) {
            return new self;
        }

        // Extract gateway-specific configs
        $gatewayConfigs = [];
        $knownKeys = ['default_gateway', 'enabled_gateways'];

        foreach ($data as $key => $value) {
            if (! in_array($key, $knownKeys) && is_array($value)) {
                $gatewayConfigs[$key] = $value;
            }
        }

        return new self(
            defaultGateway: $data['default_gateway'] ?? null,
            enabledGateways: $data['enabled_gateways'] ?? [],
            gatewayConfigs: $gatewayConfigs,
        );
    }

    /**
     * Check if a specific gateway is enabled for this academy.
     *
     * If enabledGateways is empty, all gateways are considered enabled.
     */
    public function isGatewayEnabled(string $gateway): bool
    {
        if (empty($this->enabledGateways)) {
            return true; // All gateways enabled by default
        }

        return in_array($gateway, $this->enabledGateways);
    }

    /**
     * Get the list of enabled gateways.
     *
     * If no gateways are explicitly enabled, returns empty array
     * meaning all configured gateways should be available.
     */
    public function getEnabledGateways(): array
    {
        return $this->enabledGateways;
    }

    /**
     * Get the default gateway for this academy.
     *
     * Falls back to global config if not set.
     */
    public function getDefaultGateway(): ?string
    {
        return $this->defaultGateway;
    }

    /**
     * Check if this academy uses global credentials for a gateway.
     */
    public function usesGlobalCredentials(string $gateway): bool
    {
        $config = $this->gatewayConfigs[$gateway] ?? [];

        return ($config['use_global'] ?? true) === true;
    }

    /**
     * Get academy-specific configuration for a gateway.
     *
     * Returns only the academy-specific overrides, not merged with global.
     */
    public function getGatewayConfig(string $gateway): array
    {
        return $this->gatewayConfigs[$gateway] ?? [];
    }

    /**
     * Get merged gateway configuration (academy-specific + global fallback).
     *
     * Smart merge logic:
     * 1. If use_global is false, use academy credentials with global as fallback
     * 2. If use_global is true (or not set), check if academy has credentials anyway
     *    and use them (this handles the case where user enters credentials but
     *    forgets to turn off use_global toggle)
     *
     * @param  array  $globalConfig  The global configuration from config/payments.php
     * @return array Merged configuration
     */
    public function getMergedGatewayConfig(string $gateway, array $globalConfig): array
    {
        $academyConfig = $this->getGatewayConfig($gateway);

        // Smart detection: If academy has any credentials, use them regardless of use_global
        // This handles the common case where user enters credentials but forgets to toggle use_global off
        $hasAcademyCredentials = ! empty($academyConfig['secret_key'])
            || ! empty($academyConfig['api_key'])
            || ! empty($academyConfig['public_key']);

        // If using global credentials AND academy doesn't have any credentials, return global config
        if ($this->usesGlobalCredentials($gateway) && ! $hasAcademyCredentials) {
            return $globalConfig;
        }

        // Merge: academy-specific values override global values
        $merged = $globalConfig;

        // Override with academy-specific credentials
        if (! empty($academyConfig['api_key'])) {
            $merged['api_key'] = $academyConfig['api_key'];
        }

        if (! empty($academyConfig['secret_key'])) {
            $merged['secret_key'] = $academyConfig['secret_key'];
        }

        // For Paymob, also handle public_key and hmac_secret
        if (! empty($academyConfig['public_key'])) {
            $merged['public_key'] = $academyConfig['public_key'];
        }

        if (! empty($academyConfig['hmac_secret'])) {
            $merged['hmac_secret'] = $academyConfig['hmac_secret'];
        }

        // Handle Paymob integration IDs (card, wallet, etc.)
        if (! empty($academyConfig['card_integration_id'])) {
            $merged['integration_ids'] = $merged['integration_ids'] ?? [];
            $merged['integration_ids']['card'] = $academyConfig['card_integration_id'];
        }

        if (! empty($academyConfig['wallet_integration_id'])) {
            $merged['integration_ids'] = $merged['integration_ids'] ?? [];
            $merged['integration_ids']['wallet'] = $academyConfig['wallet_integration_id'];
        }

        // Override enabled payment methods if specified
        if (! empty($academyConfig['enabled_methods'])) {
            $merged['enabled_methods'] = $academyConfig['enabled_methods'];
        }

        return $merged;
    }

    /**
     * Check if the academy has any custom payment settings configured.
     */
    public function hasCustomSettings(): bool
    {
        return $this->defaultGateway !== null
            || ! empty($this->enabledGateways)
            || ! empty($this->gatewayConfigs);
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->defaultGateway !== null) {
            $data['default_gateway'] = $this->defaultGateway;
        }

        if (! empty($this->enabledGateways)) {
            $data['enabled_gateways'] = $this->enabledGateways;
        }

        foreach ($this->gatewayConfigs as $gateway => $config) {
            $data[$gateway] = $config;
        }

        return $data;
    }
}
