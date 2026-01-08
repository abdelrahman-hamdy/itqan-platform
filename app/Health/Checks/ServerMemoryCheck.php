<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class ServerMemoryCheck extends Check
{
    protected int $warnThresholdPercent = 80;

    protected int $failThresholdPercent = 95;

    public function getName(): string
    {
        return 'Server Memory';
    }

    public function run(): Result
    {
        $memInfo = $this->getMemoryInfo();
        $usedPercent = $memInfo['used_percent'];

        $result = Result::make()
            ->shortSummary("{$usedPercent}% used ({$memInfo['used_gb']} GB / {$memInfo['total_gb']} GB)")
            ->meta($memInfo);

        if ($usedPercent >= $this->failThresholdPercent) {
            return $result->failed("Critical: Server memory at {$usedPercent}%");
        }

        if ($usedPercent >= $this->warnThresholdPercent) {
            return $result->warning("Warning: Server memory at {$usedPercent}%");
        }

        return $result->ok();
    }

    private function getMemoryInfo(): array
    {
        // Linux: Parse /proc/meminfo
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available);

            $totalKb = (int) ($total[1] ?? 0);
            $availableKb = (int) ($available[1] ?? 0);
            $usedKb = $totalKb - $availableKb;

            return [
                'total_gb' => round($totalKb / 1024 / 1024, 2),
                'used_gb' => round($usedKb / 1024 / 1024, 2),
                'available_gb' => round($availableKb / 1024 / 1024, 2),
                'used_percent' => $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0,
            ];
        }

        // macOS: Use vm_stat command
        if (PHP_OS_FAMILY === 'Darwin') {
            return $this->getMacOSMemoryInfo();
        }

        // Fallback for unsupported systems
        return [
            'total_gb' => 0,
            'used_gb' => 0,
            'available_gb' => 0,
            'used_percent' => 0,
            'note' => 'Memory info not available on this system',
        ];
    }

    private function getMacOSMemoryInfo(): array
    {
        // Get total physical memory
        $totalMemory = (int) shell_exec('sysctl -n hw.memsize');
        $totalKb = $totalMemory / 1024;

        // Parse vm_stat output
        $vmStat = shell_exec('vm_stat');
        $pageSize = 16384; // Default macOS page size

        if (preg_match('/page size of (\d+) bytes/', $vmStat, $matches)) {
            $pageSize = (int) $matches[1];
        }

        // Extract pages
        preg_match('/Pages free:\s+(\d+)/', $vmStat, $free);
        preg_match('/Pages active:\s+(\d+)/', $vmStat, $active);
        preg_match('/Pages inactive:\s+(\d+)/', $vmStat, $inactive);
        preg_match('/Pages speculative:\s+(\d+)/', $vmStat, $speculative);
        preg_match('/Pages wired down:\s+(\d+)/', $vmStat, $wired);

        $freePages = (int) ($free[1] ?? 0);
        $speculativePages = (int) ($speculative[1] ?? 0);

        $freeBytes = ($freePages + $speculativePages) * $pageSize;
        $availableKb = $freeBytes / 1024;
        $usedKb = $totalKb - $availableKb;

        return [
            'total_gb' => round($totalKb / 1024 / 1024, 2),
            'used_gb' => round($usedKb / 1024 / 1024, 2),
            'available_gb' => round($availableKb / 1024 / 1024, 2),
            'used_percent' => $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0,
        ];
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
