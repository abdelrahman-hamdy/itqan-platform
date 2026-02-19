<?php

namespace App\Services;

use App\Models\ExchangeRate;
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

            // Also persist to DB as last-known rate
            $this->persistRate($from, $to, $rate);

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
     * Fallback to static rates from config if API fails, then DB stored rate.
     */
    private function getFallbackRate(string $from, string $to): float
    {
        $rates = config('currencies.exchange_rates', []);

        $fromRate = $rates[$from] ?? null;
        $toRate = $rates[$to] ?? null;

        if ($fromRate !== null && $toRate !== null && $fromRate != 0) {
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

        // Try DB last-known rate
        $stored = ExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->first();

        if ($stored) {
            Log::channel('payments')->warning('Using stored DB rate as fallback', [
                'from' => $from,
                'to' => $to,
                'rate' => $stored->rate,
                'fetched_at' => $stored->fetched_at,
                'source' => 'db_fallback',
            ]);

            return (float) $stored->rate;
        }

        throw new Exception("No exchange rate available for {$from} to {$to}. Please run exchange-rates:refresh command.");
    }

    /**
     * Persist the fetched exchange rate to the database as a last-known rate.
     */
    private function persistRate(string $from, string $to, float $rate): void
    {
        try {
            ExchangeRate::updateOrCreate(
                ['from_currency' => $from, 'to_currency' => $to],
                ['rate' => $rate, 'fetched_at' => now()]
            );
        } catch (\Exception $e) {
            Log::channel('payments')->warning('Failed to persist exchange rate to DB', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the stored DB rate for display purposes (e.g. Filament admin panel).
     *
     * @return array{rate: float, fetched_at: \Illuminate\Support\Carbon}|null
     */
    public function getStoredRate(string $from, string $to): ?array
    {
        $stored = ExchangeRate::where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to))
            ->first();

        if (! $stored) {
            return null;
        }

        return [
            'rate' => (float) $stored->rate,
            'fetched_at' => $stored->fetched_at,
        ];
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
     * When no params are provided, does nothing to avoid flushing the entire cache.
     */
    public function clearCache(?string $from = null, ?string $to = null): void
    {
        if ($from && $to) {
            $cacheKey = "exchange_rate_" . strtoupper($from) . "_" . strtoupper($to);
            Cache::forget($cacheKey);
        }
        // No-op when called without params - do NOT flush entire cache
    }
}
