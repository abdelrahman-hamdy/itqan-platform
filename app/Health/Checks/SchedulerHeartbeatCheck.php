<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Reads the heartbeat written by Spatie's `health:schedule-check-heartbeat`
 * artisan command. The 2026-04-20 mutex incident would have surfaced
 * through a stale beat here — see feedback_schedule_mutex_short_ttl in
 * memory.
 *
 * @see \Spatie\Health\Checks\Checks\ScheduleCheck::getCacheKey()
 */
class SchedulerHeartbeatCheck extends Check
{
    private const HEARTBEAT_CACHE_KEY = 'health:checks:schedule:latestHeartbeatAt';

    protected int $maxAgeMinutes = 2;

    public function getName(): string
    {
        return 'Scheduler Heartbeat';
    }

    public function maxAgeInMinutes(int $minutes): self
    {
        $this->maxAgeMinutes = $minutes;

        return $this;
    }

    public function run(): Result
    {
        $lastBeat = cache()->get(self::HEARTBEAT_CACHE_KEY);

        if ($lastBeat === null) {
            return Result::make()
                ->shortSummary('no heartbeat recorded yet')
                ->meta(['cache_key' => self::HEARTBEAT_CACHE_KEY])
                ->failed('Scheduler has never emitted a heartbeat');
        }

        $ageSeconds = max(0, now()->timestamp - (int) $lastBeat);
        $ageMinutes = (int) floor($ageSeconds / 60);

        $meta = [
            'cache_key' => self::HEARTBEAT_CACHE_KEY,
            'last_heartbeat_at_utc' => gmdate('Y-m-d H:i:s', (int) $lastBeat),
            'age_seconds' => $ageSeconds,
            'age_minutes' => $ageMinutes,
        ];

        $result = Result::make()
            ->shortSummary("last beat {$ageSeconds}s ago")
            ->meta($meta);

        if ($ageMinutes > $this->maxAgeMinutes) {
            return $result->failed(
                "Scheduler heartbeat stale — last beat {$ageMinutes}m ago (max {$this->maxAgeMinutes}m)"
            );
        }

        return $result->ok();
    }
}
