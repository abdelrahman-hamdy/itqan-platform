<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

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
            EditAction::make(),
        ];
    }
}
