<?php

namespace App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Academy\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\DeleteAction;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
