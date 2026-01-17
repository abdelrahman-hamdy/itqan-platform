<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SentryService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const CACHE_KEY = 'sentry_dashboard_stats';

    /**
     * Check if Sentry is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->getOrgSlug()
            && $this->getProjectSlug()
            && $this->getAuthToken();
    }

    /**
     * Get Sentry dashboard URL
     */
    public function getSentryUrl(): string
    {
        return "https://sentry.io/organizations/{$this->getOrgSlug()}/issues/?project={$this->getProjectSlug()}";
    }

    /**
     * Get all stats with caching
     */
    public function getStats(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->fetchAllStats();
        });
    }

    /**
     * Clear cached stats
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Fetch all stats from Sentry API
     */
    private function fetchAllStats(): array
    {
        if (! $this->isConfigured()) {
            return [
                'configured' => false,
                'error' => 'Sentry not configured. Set SENTRY_ORG_SLUG, SENTRY_PROJECT_SLUG, and SENTRY_AUTH_TOKEN in .env',
            ];
        }

        try {
            // Fetch issues and stats in parallel would be ideal, but for simplicity we'll do sequential
            $issuesData = $this->fetchIssues();
            $statsData = $this->fetchProjectStats();

            if (isset($issuesData['error'])) {
                return $issuesData;
            }

            return array_merge($issuesData, $statsData, [
                'configured' => true,
                'sentry_url' => $this->getSentryUrl(),
                'fetched_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return [
                'configured' => true,
                'error' => 'Error connecting to Sentry: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Fetch recent issues from Sentry
     */
    private function fetchIssues(): array
    {
        $response = Http::withToken($this->getAuthToken())
            ->timeout(10)
            ->get($this->buildApiUrl('/issues/'), [
                'query' => 'is:unresolved',
                'statsPeriod' => '24h',
                'limit' => 10,
            ]);

        if (! $response->successful()) {
            return [
                'configured' => true,
                'error' => 'Failed to fetch Sentry data: HTTP '.$response->status(),
            ];
        }

        $issues = $response->json();

        // Calculate stats from issues
        $errorCount24h = collect($issues)->sum(fn ($issue) => $issue['count'] ?? 0);

        // Get unique users affected (approximate from issues metadata)
        $affectedUsers = collect($issues)->sum(fn ($issue) => $issue['userCount'] ?? 0);

        // Calculate trend (compare first half vs second half of issues by count)
        $trend = $this->calculateTrend($issues);

        // Format recent issues
        $recentIssues = collect($issues)->take(5)->map(fn ($issue) => [
            'id' => $issue['id'] ?? null,
            'title' => $issue['title'] ?? 'Unknown',
            'culprit' => $issue['culprit'] ?? null,
            'count' => $issue['count'] ?? 0,
            'userCount' => $issue['userCount'] ?? 0,
            'lastSeen' => isset($issue['lastSeen']) ? Carbon::parse($issue['lastSeen'])->diffForHumans() : 'Unknown',
            'firstSeen' => isset($issue['firstSeen']) ? Carbon::parse($issue['firstSeen'])->diffForHumans() : 'Unknown',
            'level' => $issue['level'] ?? 'error',
            'permalink' => $issue['permalink'] ?? '#',
            'shortId' => $issue['shortId'] ?? null,
        ])->toArray();

        return [
            'total_issues' => count($issues),
            'error_count_24h' => $errorCount24h,
            'affected_users' => $affectedUsers,
            'trend' => $trend,
            'recent_issues' => $recentIssues,
            'last_seen' => $issues[0]['lastSeen'] ?? null,
        ];
    }

    /**
     * Fetch project stats (crash-free sessions, etc.)
     */
    private function fetchProjectStats(): array
    {
        try {
            // Fetch session stats for crash-free rate
            $response = Http::withToken($this->getAuthToken())
                ->timeout(10)
                ->get($this->buildApiUrl('/sessions/'), [
                    'field' => 'crash_free_rate',
                    'statsPeriod' => '24h',
                    'interval' => '1d',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $crashFreeRate = $this->extractCrashFreeRate($data);

                return [
                    'crash_free_rate' => $crashFreeRate,
                ];
            }
        } catch (\Exception $e) {
            // Silently fail for optional stats
        }

        return [
            'crash_free_rate' => null,
        ];
    }

    /**
     * Extract crash-free rate from sessions response
     */
    private function extractCrashFreeRate(array $data): ?float
    {
        // Sentry API returns crash_free_rate in groups array
        $groups = $data['groups'] ?? [];
        if (empty($groups)) {
            return null;
        }

        $totals = $groups[0]['totals'] ?? [];
        $rate = $totals['crash_free_rate'] ?? null;

        return $rate !== null ? round($rate * 100, 1) : null;
    }

    /**
     * Calculate error trend (up, down, or stable)
     */
    private function calculateTrend(array $issues): string
    {
        if (count($issues) < 2) {
            return 'stable';
        }

        // Compare recent activity - if most issues have recent lastSeen, trend is up
        $recentCount = 0;
        $cutoff = now()->subHours(6);

        foreach (array_slice($issues, 0, 5) as $issue) {
            if (isset($issue['lastSeen'])) {
                $lastSeen = Carbon::parse($issue['lastSeen']);
                if ($lastSeen->isAfter($cutoff)) {
                    $recentCount++;
                }
            }
        }

        if ($recentCount >= 3) {
            return 'up';
        } elseif ($recentCount <= 1) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Build Sentry API URL
     */
    private function buildApiUrl(string $endpoint): string
    {
        $orgSlug = $this->getOrgSlug();
        $projectSlug = $this->getProjectSlug();

        return "https://sentry.io/api/0/projects/{$orgSlug}/{$projectSlug}{$endpoint}";
    }

    private function getOrgSlug(): ?string
    {
        return config('sentry-dashboard.organization_slug');
    }

    private function getProjectSlug(): ?string
    {
        return config('sentry-dashboard.project_slug');
    }

    private function getAuthToken(): ?string
    {
        return config('sentry-dashboard.auth_token');
    }
}
