<?php

namespace App\Filament\Academy\Resources\MeetingAttendanceResource\Pages;

use App\Filament\Academy\Resources\MeetingAttendanceResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewMeetingAttendance extends ViewRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
