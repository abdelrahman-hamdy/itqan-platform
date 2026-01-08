<?php

namespace App\Health\Checks;

use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrarySizeCheck extends Check
{
    protected float $warnThresholdGb = 10.0;

    protected float $failThresholdGb = 20.0;

    public function getName(): string
    {
        return 'Media Library';
    }

    public function run(): Result
    {
        $stats = $this->getMediaStats();

        $result = Result::make()
            ->shortSummary("{$stats['total_formatted']} ({$stats['count']} files)")
            ->meta($stats);

        $totalGb = $stats['total_bytes'] / 1024 / 1024 / 1024;

        if ($totalGb >= $this->failThresholdGb) {
            return $result->failed("Media library exceeds {$this->failThresholdGb} GB");
        }

        if ($totalGb >= $this->warnThresholdGb) {
            return $result->warning("Media library approaching {$this->warnThresholdGb} GB");
        }

        return $result->ok();
    }

    private function getMediaStats(): array
    {
        $totalSize = Media::sum('size');
        $count = Media::count();

        // Get breakdown by collection
        $byCollection = Media::select('collection_name', DB::raw('SUM(size) as total_size'), DB::raw('COUNT(*) as count'))
            ->groupBy('collection_name')
            ->orderByDesc('total_size')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'collection' => $row->collection_name,
                'size_bytes' => (int) $row->total_size,
                'size_formatted' => $this->formatBytes((int) $row->total_size),
                'count' => $row->count,
            ])
            ->toArray();

        return [
            'total_bytes' => (int) $totalSize,
            'total_formatted' => $this->formatBytes((int) $totalSize),
            'total_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
            'count' => $count,
            'by_collection' => $byCollection,
        ];
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
