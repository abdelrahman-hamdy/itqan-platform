<?php

namespace App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;

use App\Filament\Teacher\Resources\QuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranTrialRequests extends ListRecords
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Teachers cannot create trial requests, only respond to them
        ];
    }
}