<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

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

    public array $sentryData = [];

    public function mount(): void
    {
        $this->loadLogFiles();
        $this->loadSentryData();

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

    public function loadSentryData(): void
    {
        $this->sentryData = Cache::remember('sentry_stats_log_viewer', 300, function () {
            return $this->fetchSentryStats();
        });
    }

    public function refreshSentry(): void
    {
        Cache::forget('sentry_stats_log_viewer');
        $this->loadSentryData();
    }

    private function fetchSentryStats(): array
    {
        $orgSlug = config('sentry-dashboard.organization_slug');
        $projectSlug = config('sentry-dashboard.project_slug');
        $authToken = config('sentry-dashboard.auth_token');

        // Check if Sentry is properly configured
        if (! $orgSlug || ! $projectSlug || ! $authToken) {
            return [
                'configured' => false,
                'error' => 'Sentry not configured. Set SENTRY_ORG_SLUG, SENTRY_PROJECT_SLUG, and SENTRY_AUTH_TOKEN.',
            ];
        }

        try {
            // Fetch recent issues
            $response = Http::withToken($authToken)
                ->timeout(10)
                ->get("https://sentry.io/api/0/projects/{$orgSlug}/{$projectSlug}/issues/", [
                    'query' => 'is:unresolved',
                    'statsPeriod' => '24h',
                    'limit' => 10,
                ]);

            if (! $response->successful()) {
                return [
                    'configured' => true,
                    'error' => 'Failed to fetch Sentry data: ' . $response->status(),
                ];
            }

            $issues = $response->json();

            // Calculate error count
            $errorCount24h = collect($issues)->sum(fn ($issue) => $issue['count'] ?? 0);

            // Get recent issues
            $recentIssues = collect($issues)->take(5)->map(fn ($issue) => [
                'title' => $issue['title'] ?? 'Unknown',
                'count' => $issue['count'] ?? 0,
                'lastSeen' => isset($issue['lastSeen']) ? Carbon::parse($issue['lastSeen'])->diffForHumans() : 'Unknown',
                'level' => $issue['level'] ?? 'error',
                'permalink' => $issue['permalink'] ?? '#',
            ])->toArray();

            return [
                'configured' => true,
                'total_issues' => count($issues),
                'recent_issues' => $recentIssues,
                'error_count_24h' => $errorCount24h,
                'last_seen' => $issues[0]['lastSeen'] ?? null,
                'sentry_url' => "https://sentry.io/organizations/{$orgSlug}/issues/?project={$projectSlug}",
            ];
        } catch (\Exception $e) {
            return [
                'configured' => true,
                'error' => 'Error connecting to Sentry: ' . $e->getMessage(),
            ];
        }
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
}
