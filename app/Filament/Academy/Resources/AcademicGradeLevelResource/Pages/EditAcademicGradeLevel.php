<?php

namespace App\Filament\Academy\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Academy\Resources\AcademicGradeLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademicGradeLevel extends EditRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

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
