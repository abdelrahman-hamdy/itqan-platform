<?php

namespace App\Filament\Academy\Resources\AcademicSessionResource\Pages;

use App\Filament\Academy\Resources\AcademicSessionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Shared\Actions\MeetingActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MeetingActions::viewMeeting('academic'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
