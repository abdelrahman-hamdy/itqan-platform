<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranCircles extends ListRecords
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء حلقة جديدة'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'حلقاتي الجماعية',
        ];
    }
}
