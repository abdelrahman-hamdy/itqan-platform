<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private string $apiUrl = 'https://open.er-api.com/v6/latest/';

    private int $cacheDuration = 3600; // 1 hour in seconds
    /**
     * Get exchange rate from currency A to currency B.
     * Cached for 1 hour to stay within free API limits.
     *
     * @param  string  $from  Base currency (e.g., 'SAR')
     * @param  string  $to  Target currency (e.g., 'EGP')
     * @return float Exchange rate
     *
     * @throws Exception If API fails and no fallback available
     */
    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        // If same currency, rate is 1
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        // Try cache first (1 hour TTL)
        $cachedRate = Cache::get($cacheKey);
        if ($cachedRate !== null) {
            Log::channel('payments')->debug('Exchange rate from cache', [
                'from' => $from,
                'to' => $to,
                'rate' => $cachedRate,
                'source' => 'cache',
            ]);

            return (float) $cachedRate;
        }

        // Fetch from API
        try {
            $response = Http::timeout(5)->get($this->apiUrl.$from);

            if (! $response->successful()) {
                throw new Exception("Exchange rate API returned status {$response->status()}");
            }

            $data = $response->json();

            if (! isset($data['rates'][$to])) {
                throw new Exception("Currency {$to} not found in API response");
            }

            $rate = (float) $data['rates'][$to];

            // Cache for 1 hour
            Cache::put($cacheKey, $rate, $this->cacheDuration);

            Log::channel('payments')->info('Exchange rate fetched from API', [
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
                'source' => 'api',
                'api_updated_at' => $data['time_last_update_utc'] ?? 'unknown',
            ]);

            return $rate;

        } catch (Exception $e) {
            Log::channel('payments')->error('Exchange rate API failed', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            // Fallback to static config rates
            return $this->getFallbackRate($from, $to);
        }
    }

    /**
     * Fallback to static rates from config if API fails.
     */
    private function getFallbackRate(string $from, string $to): float
    {
        $rates = config('currencies.exchange_rates', []);

        $fromRate = $rates[$from] ?? null;
        $toRate = $rates[$to] ?? null;

        if ($fromRate === null || $toRate === null || $fromRate == 0) {
            // Ultimate fallback: Use hardcoded SAR→EGP rate
            if ($from === 'SAR' && $to === 'EGP') {
                Log::channel('payments')->warning('Using emergency fallback rate for SAR→EGP');

                return 12.69; // Emergency fallback (update periodically)
            }

            throw new Exception("No fallback rate available for {$from} to {$to}");
        }

        // Convert FROM → SAR → TO using config rates
        $amountInSar = 1 / $fromRate;
        $rate = $amountInSar * $toRate;

        Log::channel('payments')->warning('Using fallback config rates', [
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'source' => 'config_fallback',
        ]);

        return $rate;
    }

    /**
     * Get multiple rates at once (for dashboard display).
     */
    public function getRates(string $baseCurrency, array $targetCurrencies): array
    {
        $rates = [];
        foreach ($targetCurrencies as $target) {
            try {
                $rates[$target] = $this->getRate($baseCurrency, $target);
            } catch (Exception $e) {
                $rates[$target] = null;
            }
        }

        return $rates;
    }

    /**
     * Clear cached rates (useful for testing or manual refresh).
     */
    public function clearCache(string $from = null, string $to = null): void
    {
        if ($from && $to) {
            $cacheKey = "exchange_rate_{$from}_{$to}";
            Cache::forget($cacheKey);
        } else {
            // Clear all exchange rate caches
            Cache::flush(); // Or use Cache::tags() if using Redis
        }
    }
}
