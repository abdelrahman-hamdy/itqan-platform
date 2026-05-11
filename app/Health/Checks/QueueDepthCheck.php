<?php

namespace App\Health\Checks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Watches Horizon-supervised queues for runaway depth.
 *
 * - >$failAt for any queue   → critical (immediate page).
 * - >$warnAt for any queue on TWO consecutive runs → medium warning.
 *   The "two consecutive runs" gate uses a tiny Redis flag so a one-off
 *   spike from a batch enqueue doesn't generate noise.
 */
class QueueDepthCheck extends Check
{
    /** @var list<string> */
    protected array $queues = ['default', 'notifications', 'messages', 'meetings'];

    protected int $warnAt = 100;

    protected int $failAt = 500;

    public function getName(): string
    {
        return 'Queue Depth';
    }

    public function run(): Result
    {
        $sizes = [];
        $maxQueue = null;
        $maxSize = 0;

        foreach ($this->queues as $queue) {
            try {
                $size = Queue::size($queue);
            } catch (\Throwable $e) {
                $size = -1;
            }

            $sizes[$queue] = $size;

            if ($size > $maxSize) {
                $maxSize = $size;
                $maxQueue = $queue;
            }
        }

        $summary = collect($sizes)
            ->map(fn ($size, $queue) => "{$queue}={$size}")
            ->implode(' ');

        $result = Result::make()
            ->shortSummary($summary)
            ->meta(['sizes' => $sizes]);

        if ($maxSize >= $this->failAt) {
            $this->clearWarnState();

            return $result->failed("Queue {$maxQueue} depth {$maxSize} (>= {$this->failAt})");
        }

        if ($maxSize >= $this->warnAt) {
            if ($this->confirmRepeatedBreach()) {
                return $result->warning("Queue {$maxQueue} depth {$maxSize} for 2 consecutive runs");
            }

            return $result->ok();
        }

        $this->clearWarnState();

        return $result->ok();
    }

    public function queues(array $queues): self
    {
        $this->queues = array_values($queues);

        return $this;
    }

    public function warnAbove(int $size): self
    {
        $this->warnAt = $size;

        return $this;
    }

    public function failAbove(int $size): self
    {
        $this->failAt = $size;

        return $this;
    }

    private function confirmRepeatedBreach(): bool
    {
        $key = 'health:queue_depth:warn_streak';
        $previous = (int) Cache::get($key, 0);
        $next = min($previous + 1, 2);

        Cache::put($key, $next, now()->addMinutes(30));

        return $next >= 2;
    }

    private function clearWarnState(): void
    {
        Cache::forget('health:queue_depth:warn_streak');
    }
}
