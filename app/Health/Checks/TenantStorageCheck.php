<?php

namespace App\Health\Checks;

use App\Models\Academy;
use Illuminate\Support\Facades\File;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class TenantStorageCheck extends Check
{
    protected float $warnThresholdGb = 5.0;

    protected float $failThresholdGb = 10.0;

    public function getName(): string
    {
        return 'Tenant Storage';
    }

    public function run(): Result
    {
        $tenantStats = $this->getTenantStorageStats();
        $totalSize = array_sum(array_column($tenantStats, 'size_bytes'));

        $result = Result::make()
            ->shortSummary($this->formatBytes($totalSize).' total ('.count($tenantStats).' tenants)')
            ->meta([
                'total_bytes' => $totalSize,
                'total_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
                'tenant_count' => count($tenantStats),
                'largest_tenants' => array_slice($tenantStats, 0, 5),
            ]);

        $totalGb = $totalSize / 1024 / 1024 / 1024;

        if ($totalGb >= $this->failThresholdGb) {
            return $result->failed("Tenant storage exceeds {$this->failThresholdGb} GB");
        }

        if ($totalGb >= $this->warnThresholdGb) {
            return $result->warning("Tenant storage approaching {$this->warnThresholdGb} GB");
        }

        return $result->ok();
    }

    private function getTenantStorageStats(): array
    {
        $stats = [];
        $basePath = storage_path('app/tenants');

        if (! File::isDirectory($basePath)) {
            return $stats;
        }

        foreach (File::directories($basePath) as $tenantDir) {
            $size = $this->getDirectorySize($tenantDir);
            $tenantId = basename($tenantDir);
            $academy = Academy::find($tenantId);

            $stats[] = [
                'tenant_id' => $tenantId,
                'name' => $academy?->name ?? 'Unknown',
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
            ];
        }

        // Sort by size descending
        usort($stats, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        return $stats;
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
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

    public function warnWhenAboveGb(float $gb): self
    {
        $this->warnThresholdGb = $gb;

        return $this;
    }

    public function failWhenAboveGb(float $gb): self
    {
        $this->failThresholdGb = $gb;

        return $this;
    }
}
