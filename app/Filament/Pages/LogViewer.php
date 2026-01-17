<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'سجلات النظام';

    protected static ?string $title = 'سجلات النظام';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.log-viewer';

    public string $selectedFile = '';

    public string $logContent = '';

    public array $logFiles = [];

    public int $lines = 100;

    public function mount(): void
    {
        $this->loadLogFiles();

        // Select the most recent log file by default
        if (! empty($this->logFiles)) {
            $this->selectedFile = $this->logFiles[0]['path'];
            $this->loadLogContent();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    public function loadLogFiles(): void
    {
        $logPath = storage_path('logs');

        if (! File::isDirectory($logPath)) {
            $this->logFiles = [];
            return;
        }

        $files = File::files($logPath);

        $this->logFiles = collect($files)
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $this->formatFileSize($file->getSize()),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->toArray();
    }

    public function loadLogContent(): void
    {
        if (empty($this->selectedFile) || ! File::exists($this->selectedFile)) {
            $this->logContent = 'No log file selected or file not found.';
            return;
        }

        // Read last N lines
        $content = $this->tailFile($this->selectedFile, $this->lines);
        $this->logContent = $content ?: 'Log file is empty.';
    }

    public function selectFile(string $path): void
    {
        $this->selectedFile = $path;
        $this->loadLogContent();
    }

    public function refresh(): void
    {
        $this->loadLogFiles();
        $this->loadLogContent();
    }

    public function clearLog(): void
    {
        if (! empty($this->selectedFile) && File::exists($this->selectedFile)) {
            File::put($this->selectedFile, '');
            $this->logContent = 'Log file cleared.';
        }
    }

    public function updatedLines(): void
    {
        $this->loadLogContent();
    }

    protected function tailFile(string $filepath, int $lines = 100): string
    {
        $file = new \SplFileObject($filepath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);

        $result = [];
        $file->seek($startLine);

        while (! $file->eof()) {
            $line = $file->fgets();
            if ($line !== false) {
                $result[] = $line;
            }
        }

        return implode('', $result);
    }

    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExternalLogViewerUrl(): string
    {
        return url('/log-viewer');
    }
}
