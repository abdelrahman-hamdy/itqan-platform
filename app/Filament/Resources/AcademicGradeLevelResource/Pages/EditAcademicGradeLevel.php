<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicGradeLevel extends EditRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

    public function getTitle(): string
    {
        return 'تعديل الصف الدراسي';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
