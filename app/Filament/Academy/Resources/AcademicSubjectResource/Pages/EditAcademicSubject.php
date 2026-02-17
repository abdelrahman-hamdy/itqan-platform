<?php

namespace App\Filament\Academy\Resources\AcademicSubjectResource\Pages;

use App\Filament\Academy\Resources\AcademicSubjectResource;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademicSubject extends EditRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
