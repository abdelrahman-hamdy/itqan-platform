<?php

namespace App\Services\Subscription;

use Illuminate\Support\Facades\Cache;

/**
 * Tiny day-bucketed counter for subscription renewal failures. Reset by
 * the 36-hour TTL so SendExpiryRemindersCommand at 08:00 KSA can read
 * "yesterday's" total reliably even if the daily job is briefly delayed.
 */
class SubscriptionFailureCounter
{
    private const TTL_SECONDS = 129600;

    public static function recordFailure(?string $date = null): void
    {
        $key = self::key($date);

        Cache::add($key, 0, self::TTL_SECONDS);
        Cache::increment($key);
    }

    public static function countFor(?string $date = null): int
    {
        return (int) Cache::get(self::key($date), 0);
    }

    public static function key(?string $date = null): string
    {
        return 'sub_renewal_failures:'.($date ?? now()->toDateString());
    }
}
