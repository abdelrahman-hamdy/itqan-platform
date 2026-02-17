<?php

namespace App\Filament\Resources\MeetingAttendanceResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\MeetingAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingAttendance extends EditRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
