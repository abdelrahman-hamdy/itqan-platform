<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
