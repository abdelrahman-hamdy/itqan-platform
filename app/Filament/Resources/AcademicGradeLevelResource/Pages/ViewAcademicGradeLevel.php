<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicGradeLevel extends ViewRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

    public function getTitle(): string
    {
        return 'عرض الصف الدراسي';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
