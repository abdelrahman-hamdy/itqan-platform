<?php

namespace App\Services\Payment;

use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Support\Facades\Cache;

/**
 * Sliding 5-minute failure counter per gateway. Built around 1-minute
 * Redis buckets so the counter is bounded and self-cleaning — we sum the
 * last 5 buckets and never need a cleanup job. The counter is bumped
 * from bootstrap/app.php's report() callback; the threshold check pages
 * Telegram with severity=crit (which bypasses the channel's own
 * rate-limit). A separate cool-down key prevents repeat pages while the
 * storm is ongoing.
 */
class PaymentFailureStormDetector
{
    /**
     * Threshold count within the sliding 5-min window that constitutes a
     * "storm" for a single gateway.
     */
    public const THRESHOLD = 10;

    /**
     * Bucket size in seconds (one bucket per minute).
     */
    private const BUCKET_SECONDS = 60;

    /**
     * Number of buckets summed in the sliding window.
     */
    private const WINDOW_BUCKETS = 5;

    /**
     * Bucket TTL slack — keep each bucket alive past the end of the
     * window so summation reads them. Used internally by Cache::increment
     * via add+increment.
     */
    private const BUCKET_TTL_SECONDS = 360;

    /**
     * Cool-down after a storm alert fires (seconds). Prevents the same
     * gateway from re-paging on every subsequent failure inside the same
     * window.
     */
    private const COOLDOWN_SECONDS = 600;

    public function recordFailure(PaymentException $e): void
    {
        $gateway = $e->getGatewayName();

        if (empty($gateway)) {
            return;
        }

        $bucket = $this->currentBucket();
        $key = "payment_fail:{$gateway}:{$bucket}";

        // Cache::add is a no-op when the key exists; it seeds the bucket
        // with a TTL so the subsequent INCR persists correctly.
        Cache::add($key, 0, self::BUCKET_TTL_SECONDS);
        $bucketCount = (int) Cache::increment($key);

        // Only sum the sliding window once this bucket alone could
        // plausibly bring the total to threshold. Below the per-bucket
        // floor a storm is mathematically impossible, so skip the 5 GETs.
        $minPerBucket = (int) ceil(self::THRESHOLD / self::WINDOW_BUCKETS);
        if ($bucketCount < $minPerBucket) {
            return;
        }

        $total = $this->windowTotal($gateway);

        if ($total >= self::THRESHOLD) {
            $this->maybeAlert($gateway, $total);
        }
    }

    public function windowTotal(string $gateway): int
    {
        $current = $this->currentBucket();
        $total = 0;

        for ($i = 0; $i < self::WINDOW_BUCKETS; $i++) {
            $bucket = $current - $i;
            $total += (int) Cache::get("payment_fail:{$gateway}:{$bucket}", 0);
        }

        return $total;
    }

    private function maybeAlert(string $gateway, int $count): void
    {
        $cooldownKey = "payment_storm_alerted:{$gateway}";

        if (! Cache::add($cooldownKey, 1, self::COOLDOWN_SECONDS)) {
            return;
        }

        alert_telegram(
            'crit',
            'payment-storm',
            "{$gateway}: {$count} failures in last 5 min"
        );
    }

    private function currentBucket(): int
    {
        return (int) floor(time() / self::BUCKET_SECONDS);
    }
}
