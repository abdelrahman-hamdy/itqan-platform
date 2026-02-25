<?php

namespace App\Services\Payment\Gateways\Paymob;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for all Paymob API communication.
 *
 * Provides authentication token retrieval and a shared request() method
 * used by all Paymob sub-services.
 */
class PaymobApiClient
{
    public function __construct(protected array $config) {}

    /**
     * Get the base URL for Paymob API.
     */
    public function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'https://accept.paymob.com';
    }

    /**
     * Get an authentication token using the API key.
     */
    public function getAuthToken(): ?string
    {
        $apiKey = $this->config['api_key'] ?? null;
        if (empty($apiKey)) {
            Log::channel('payments')->error('Paymob API key not configured');

            return null;
        }

        $response = $this->request('POST', '/api/auth/tokens', [
            'api_key' => $apiKey,
        ]);

        if ($response['success'] && ! empty($response['data']['token'])) {
            return $response['data']['token'];
        }

        Log::channel('payments')->error('Paymob auth failed', [
            'response' => $response,
        ]);

        return null;
    }

    /**
     * Make an HTTP request to the Paymob API.
     */
    public function request(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($endpoint, '/');

        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);

        try {
            // Only retry idempotent GET requests. Retrying POST/PUT can cause duplicate
            // charges if the first request was processed but the response timed out.
            $http = Http::withHeaders($allHeaders)->timeout(30);
            if (strtoupper($method) === 'GET') {
                $http = $http->retry(2, 100);
            }
            $response = $http->{strtolower($method)}($url, $data);

            $responseData = $response->json() ?? [];

            if (! $response->successful()) {
                Log::error('PaymobApiClient error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'endpoint' => $endpoint,
                ]);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $responseData,
                'raw' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('PaymobApiClient exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}
