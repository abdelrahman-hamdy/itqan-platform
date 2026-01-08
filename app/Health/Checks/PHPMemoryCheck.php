<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class PHPMemoryCheck extends Check
{
    protected int $warnThresholdPercent = 70;

    protected int $failThresholdPercent = 90;

    public function getName(): string
    {
        return 'PHP Memory';
    }

    public function run(): Result
    {
        $limit = $this->getMemoryLimitBytes();
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $usedPercent = $limit > 0 && $limit < PHP_INT_MAX
            ? round(($usage / $limit) * 100, 1)
            : 0;

        $limitDisplay = $limit === PHP_INT_MAX ? 'Unlimited' : $this->formatBytes($limit);

        $result = Result::make()
            ->shortSummary($this->formatBytes($usage).' / '.$limitDisplay)
            ->meta([
                'limit_mb' => $limit === PHP_INT_MAX ? 'Unlimited' : round($limit / 1024 / 1024, 2),
                'usage_mb' => round($usage / 1024 / 1024, 2),
                'peak_mb' => round($peak / 1024 / 1024, 2),
                'used_percent' => $usedPercent,
            ]);

        // If memory is unlimited, always return OK
        if ($limit === PHP_INT_MAX) {
            return $result->ok();
        }

        if ($usedPercent >= $this->failThresholdPercent) {
            return $result->failed("PHP memory at {$usedPercent}% of limit");
        }

        if ($usedPercent >= $this->warnThresholdPercent) {
            return $result->warning("PHP memory at {$usedPercent}% of limit");
        }

        return $result->ok();
    }

    private function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        return round($bytes / 1024, 2).' KB';
    }

    public function warnWhenAbovePercent(int $percent): self
    {
        $this->warnThresholdPercent = $percent;

        return $this;
    }

    public function failWhenAbovePercent(int $percent): self
    {
        $this->failThresholdPercent = $percent;

        return $this;
    }
}
