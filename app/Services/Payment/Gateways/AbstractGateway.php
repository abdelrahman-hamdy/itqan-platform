<?php

namespace App\Services\Payment\Gateways;

use Exception;
use App\Contracts\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for payment gateway implementations.
 *
 * Provides common functionality and enforces consistent behavior
 * across all gateway implementations.
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the gateway identifier name.
     */
    abstract public function getName(): string;

    /**
     * Get the human-readable display name (Arabic).
     */
    abstract public function getDisplayName(): string;

    /**
     * Check if the gateway is properly configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->getRequiredConfigKeys())
            && collect($this->getRequiredConfigKeys())
                ->every(fn ($key) => ! empty($this->config[$key]));
    }

    /**
     * Get required configuration keys for this gateway.
     *
     * @return array<string>
     */
    abstract protected function getRequiredConfigKeys(): array;

    /**
     * Get the base URL for the gateway API.
     */
    public function getBaseUrl(): string
    {
        if ($this->isSandbox()) {
            return $this->config['sandbox_url'] ?? $this->config['base_url'] ?? '';
        }

        return $this->config['base_url'] ?? '';
    }

    /**
     * Check if running in sandbox/test mode.
     */
    public function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? true);
    }

    /**
     * Get a configuration value.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Make an HTTP request to the gateway API.
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($endpoint, '/');

        $defaultHeaders = $this->getDefaultHeaders();
        $allHeaders = array_merge($defaultHeaders, $headers);

        Log::debug("Gateway {$this->getName()} request", [
            'method' => $method,
            'url' => $url,
            'data' => $this->sanitizeLogData($data),
        ]);

        try {
            $response = Http::withHeaders($allHeaders)
                ->timeout(30)
                ->retry(2, 100)
                ->{strtolower($method)}($url, $data);

            $responseData = $response->json() ?? [];

            Log::debug("Gateway {$this->getName()} response", [
                'status' => $response->status(),
                'data' => $this->sanitizeLogData($responseData),
            ]);

            if (! $response->successful()) {
                Log::error("Gateway {$this->getName()} error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $responseData,
                'raw' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error("Gateway {$this->getName()} exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get default headers for API requests.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Sanitize data for logging (remove sensitive info).
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = [
            'api_key', 'secret_key', 'password', 'token',
            'card_number', 'cvv', 'cvc', 'expiry',
            'pan', 'card_pan',
        ];

        return collect($data)->map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                return '***REDACTED***';
            }

            if (is_array($value)) {
                return $this->sanitizeLogData($value);
            }

            return $value;
        })->all();
    }
}
