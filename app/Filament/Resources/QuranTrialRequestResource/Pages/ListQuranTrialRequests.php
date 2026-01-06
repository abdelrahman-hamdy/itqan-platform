<?php

namespace App\Filament\Resources\QuranTrialRequestResource\Pages;

use App\Filament\Resources\QuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranTrialRequests extends ListRecords
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
