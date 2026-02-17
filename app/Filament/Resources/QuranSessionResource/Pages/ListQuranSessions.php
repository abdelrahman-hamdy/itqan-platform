<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranSessions extends ListRecords
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة جلسة'),
        ];
    }
}
