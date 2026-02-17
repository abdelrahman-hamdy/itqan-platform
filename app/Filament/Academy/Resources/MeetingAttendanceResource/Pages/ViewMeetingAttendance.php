<?php

namespace App\Filament\Academy\Resources\MeetingAttendanceResource\Pages;

use App\Filament\Academy\Resources\MeetingAttendanceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMeetingAttendance extends ViewRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
