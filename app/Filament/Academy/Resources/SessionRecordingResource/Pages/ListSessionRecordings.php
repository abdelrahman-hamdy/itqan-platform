<?php

namespace App\Filament\Academy\Resources\SessionRecordingResource\Pages;

use App\Filament\Academy\Resources\SessionRecordingResource;
use Filament\Resources\Pages\ListRecords;

class ListSessionRecordings extends ListRecords
{
    protected static string $resource = SessionRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return []; // No create action for recordings - they are created automatically
    }
}
