<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SentryStatsWidget;
use Filament\Pages\Page;

class LogViewer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'أدوات المطور';

    protected static ?string $navigationLabel = 'سجلات النظام';

    protected static ?string $title = 'سجلات النظام';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.log-viewer';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SentryStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
