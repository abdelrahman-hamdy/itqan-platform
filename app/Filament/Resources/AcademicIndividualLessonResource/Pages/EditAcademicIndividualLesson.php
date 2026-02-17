<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicIndividualLesson extends EditRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
