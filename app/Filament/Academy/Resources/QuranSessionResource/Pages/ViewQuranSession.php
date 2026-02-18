<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Shared\Actions\SessionStatusActions;
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
            SessionStatusActions::startSession(),
            SessionStatusActions::completeSession(),
            SessionStatusActions::cancelSession(role: 'admin'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
