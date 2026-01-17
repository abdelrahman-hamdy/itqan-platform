<?php

namespace App\Filament\Widgets;

use App\Services\SentryService;
use Filament\Widgets\Widget;

class SentryStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.sentry-stats-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public array $stats = [];

    public bool $showIssues = true;

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $service = app(SentryService::class);
        $this->stats = $service->getStats();
    }

    public function refresh(): void
    {
        $service = app(SentryService::class);
        $this->stats = $service->getStats(forceRefresh: true);
    }

    public function toggleIssues(): void
    {
        $this->showIssues = ! $this->showIssues;
    }

    public function getHealthStatus(): string
    {
        if (! $this->isConfigured()) {
            return 'unknown';
        }

        $errors24h = $this->stats['error_count_24h'] ?? 0;
        $unresolvedIssues = $this->stats['total_issues'] ?? 0;

        if ($errors24h === 0 && $unresolvedIssues === 0) {
            return 'healthy';
        } elseif ($errors24h < 50 && $unresolvedIssues < 5) {
            return 'warning';
        }

        return 'critical';
    }

    public function getHealthLabel(): string
    {
        return match ($this->getHealthStatus()) {
            'healthy' => __('سليم'),
            'warning' => __('تحذير'),
            'critical' => __('حرج'),
            default => __('غير معروف'),
        };
    }

    public function getHealthColor(): string
    {
        return match ($this->getHealthStatus()) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function getTrendIcon(): string
    {
        $trend = $this->stats['trend'] ?? 'stable';

        return match ($trend) {
            'up' => 'heroicon-m-arrow-trending-up',
            'down' => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus',
        };
    }

    public function getTrendColor(): string
    {
        $trend = $this->stats['trend'] ?? 'stable';

        return match ($trend) {
            'up' => 'text-red-500',
            'down' => 'text-green-500',
            default => 'text-gray-400',
        };
    }

    public function isConfigured(): bool
    {
        return ($this->stats['configured'] ?? false) && ! isset($this->stats['error']);
    }

    public function hasError(): bool
    {
        return isset($this->stats['error']);
    }

    public function getError(): ?string
    {
        return $this->stats['error'] ?? null;
    }

    public function getSentryUrl(): ?string
    {
        return $this->stats['sentry_url'] ?? null;
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
