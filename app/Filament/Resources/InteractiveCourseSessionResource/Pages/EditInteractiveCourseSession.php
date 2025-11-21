<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
