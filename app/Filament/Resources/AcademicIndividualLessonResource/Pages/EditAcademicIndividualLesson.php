<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicIndividualLesson extends EditRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
