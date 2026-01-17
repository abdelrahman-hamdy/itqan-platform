<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'سجلات النظام';

    protected static ?string $title = 'سجلات النظام';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.log-viewer';

    public array $sentryData = [];

    public function mount(): void
    {
        $this->loadSentryData();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin();
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
}
