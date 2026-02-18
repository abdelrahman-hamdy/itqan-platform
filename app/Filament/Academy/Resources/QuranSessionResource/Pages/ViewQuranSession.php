<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Shared\Actions\MeetingActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MeetingActions::viewMeeting('quran'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
