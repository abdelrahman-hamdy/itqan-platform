<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'سجلات النظام';

    protected static ?string $title = 'سجلات النظام';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.log-viewer';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    public function getLogViewerUrl(): string
    {
        return url('/log-viewer');
    }
}
