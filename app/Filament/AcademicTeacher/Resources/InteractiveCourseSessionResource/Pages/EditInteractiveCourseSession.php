<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
