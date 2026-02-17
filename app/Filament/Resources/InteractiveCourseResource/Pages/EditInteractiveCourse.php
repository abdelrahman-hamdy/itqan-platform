<?php

namespace App\Filament\Resources\InteractiveCourseResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\InteractiveCourseResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditInteractiveCourse extends EditRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
