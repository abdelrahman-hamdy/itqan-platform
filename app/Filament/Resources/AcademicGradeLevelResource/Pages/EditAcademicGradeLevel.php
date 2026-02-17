<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
