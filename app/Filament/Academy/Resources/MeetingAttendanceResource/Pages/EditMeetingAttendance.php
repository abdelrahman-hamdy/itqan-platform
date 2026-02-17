<?php

namespace App\Filament\Academy\Resources\MeetingAttendanceResource\Pages;

use App\Filament\Academy\Resources\MeetingAttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeetingAttendance extends EditRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
