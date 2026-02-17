<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Spatie\Health\Enums\Status;
use Spatie\Health\ResultStores\ResultStore;

class HealthOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.health-overview-widget';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        $checkResults = app(ResultStore::class)->latestResults();
        $results = $checkResults?->storedCheckResults ?? collect();

        $summary = [
            'ok' => 0,
            'warning' => 0,
            'failed' => 0,
            'total' => $results->count(),
        ];

        foreach ($results as $result) {
            match ($result->status) {
                Status::ok()->value => $summary['ok']++,
                Status::warning()->value => $summary['warning']++,
                Status::failed()->value, Status::crashed()->value => $summary['failed']++,
                default => null,
            };
        }

        return [
            'checkResults' => $results,
            'summary' => $summary,
            'lastRanAt' => $checkResults?->finishedAt ? Carbon::parse($checkResults->finishedAt) : null,
        ];
    }
}
