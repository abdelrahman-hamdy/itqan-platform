<?php

namespace App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Academy\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Shared\Actions\SessionStatusActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            SessionStatusActions::startSession(),
            SessionStatusActions::completeSession(),
            SessionStatusActions::cancelSession(role: 'admin'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
