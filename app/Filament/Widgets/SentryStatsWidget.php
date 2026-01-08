<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SentryStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.sentry-stats-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    protected function getViewData(): array
    {
        return Cache::remember('sentry_stats', 300, function () {
            return $this->fetchSentryStats();
        });
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
                'error' => 'Sentry not fully configured. Please set SENTRY_ORG_SLUG, SENTRY_PROJECT_SLUG, and SENTRY_AUTH_TOKEN in your .env file.',
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
                    'error' => 'Failed to fetch Sentry data: '.$response->status(),
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
                'error' => 'Error connecting to Sentry: '.$e->getMessage(),
            ];
        }
    }
}
