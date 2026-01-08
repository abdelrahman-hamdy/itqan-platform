<?php

namespace App\Health\Checks;

use Illuminate\Support\Facades\File;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class LogFilesCheck extends Check
{
    protected float $warnThresholdMb = 100;

    protected float $failThresholdMb = 500;

    public function getName(): string
    {
        return 'Log Files';
    }

    public function run(): Result
    {
        $logFiles = $this->getLogFilesStats();
        $totalSize = array_sum(array_column($logFiles, 'size_bytes'));

        $result = Result::make()
            ->shortSummary($this->formatBytes($totalSize).' ('.count($logFiles).' files)')
            ->meta([
                'total_bytes' => $totalSize,
                'total_mb' => round($totalSize / 1024 / 1024, 2),
                'file_count' => count($logFiles),
                'files' => $logFiles,
            ]);

        $totalMb = $totalSize / 1024 / 1024;

        if ($totalMb >= $this->failThresholdMb) {
            return $result->failed("Log files exceed {$this->failThresholdMb} MB - consider clearing");
        }

        if ($totalMb >= $this->warnThresholdMb) {
            return $result->warning("Log files approaching {$this->warnThresholdMb} MB");
        }

        return $result->ok();
    }

    private function getLogFilesStats(): array
    {
        $logPath = storage_path('logs');
        $files = [];

        if (! File::isDirectory($logPath)) {
            return $files;
        }

        foreach (File::files($logPath) as $file) {
            if ($file->getExtension() === 'log') {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size_bytes' => $file->getSize(),
                    'size_formatted' => $this->formatBytes($file->getSize()),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort by size descending
        usort($files, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        return $files;
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

    public function warnWhenAboveMb(float $mb): self
    {
        $this->warnThresholdMb = $mb;

        return $this;
    }

    public function failWhenAboveMb(float $mb): self
    {
        $this->failThresholdMb = $mb;

        return $this;
    }
}
