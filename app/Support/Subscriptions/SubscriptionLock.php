<?php

namespace App\Support\Subscriptions;

use App\Exceptions\Subscription\SubscriptionLockTimeout;
use App\Models\BaseSubscription;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Per-subscription advisory lock — the single gate every subscription
 * mutator passes through (INV-C1).
 *
 * Backed by Laravel's atomic-lock facility: Redis-backed in prod (per
 * config/cache.php), file-backed in local dev. Lock key is keyed on the
 * subscription's morph class + id so QuranSubscription#42 and
 * AcademicSubscription#42 do NOT collide.
 *
 * Web/API mutators use {@see for()} — block up to $waitTimeoutSeconds,
 * raise {@see SubscriptionLockTimeout} on expiry.
 *
 * Cron mutators use {@see tryFor()} — non-blocking by design: on timeout
 * the cron skips the sub and audit-logs `cron_skipped_locked` per INV-C3.
 *
 * The lock TTL itself is 30s; long enough for any single mutator's
 * transaction, short enough that a crashed worker can't starve the
 * subscription forever (closes the schedule-mutex-TTL footgun called
 * out in MEMORY.md → feedback_schedule_mutex_short_ttl).
 */
final class SubscriptionLock
{
    /**
     * TTL of the held lock, in seconds. Hard ceiling on how long a single
     * mutator may run; anything longer is a bug.
     */
    private const LOCK_TTL_SECONDS = 30;

    /**
     * Acquire the per-subscription lock, run $work, return its result.
     *
     * Blocks for up to $waitTimeoutSeconds waiting for the lock. On
     * expiry, raises SubscriptionLockTimeout — caller bubbles it up
     * (typically a 503 in the HTTP layer).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $work
     * @return TReturn
     *
     * @throws SubscriptionLockTimeout
     */
    public static function for(
        BaseSubscription $sub,
        callable $work,
        int $waitTimeoutSeconds = 5,
    ): mixed {
        $lock = Cache::lock(self::keyFor($sub), self::LOCK_TTL_SECONDS);

        try {
            return $lock->block($waitTimeoutSeconds, $work);
        } catch (LockTimeoutException $e) {
            throw new SubscriptionLockTimeout($sub, $waitTimeoutSeconds, $e);
        }
    }

    /**
     * Non-blocking variant for cron paths (INV-C3).
     *
     * Attempts to acquire the lock for up to $maxWaitSeconds. If
     * successful, runs $work and returns its result. If the timeout
     * expires, returns boolean `false` so the caller can log
     * `cron_skipped_locked` and move on without raising.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $work
     * @return TReturn|false
     */
    public static function tryFor(
        BaseSubscription $sub,
        callable $work,
        int $maxWaitSeconds = 2,
    ): mixed {
        $lock = Cache::lock(self::keyFor($sub), self::LOCK_TTL_SECONDS);

        try {
            return $lock->block($maxWaitSeconds, $work);
        } catch (LockTimeoutException) {
            return false;
        } catch (Throwable $e) {
            // Anything thrown by $work itself: release the lock (Laravel
            // does this in `block`, but be explicit) and re-raise.
            throw $e;
        }
    }

    /**
     * Cache key for the subscription's advisory lock. Exposed for tests
     * + the audit-log writer.
     */
    public static function keyFor(BaseSubscription $sub): string
    {
        return sprintf('subscription:lock:%s:%s', $sub->getMorphClass(), $sub->getKey());
    }
}
