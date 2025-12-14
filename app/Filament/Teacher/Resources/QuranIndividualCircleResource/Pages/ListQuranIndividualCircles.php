<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranIndividualCircles extends ListRecords
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة حلقة فردية'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'حلقاتي الفردية',
        ];
    }
}
