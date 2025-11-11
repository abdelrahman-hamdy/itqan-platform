<?php

namespace App\Filament\Resources\InteractiveCourseResource\Pages;

use App\Filament\Resources\InteractiveCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveCourse extends EditRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
