<?php

namespace App\Filament\Resources\MeetingAttendanceResource\Pages;

use App\Filament\Resources\MeetingAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetingAttendances extends ListRecords
{
    protected static string $resource = MeetingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
