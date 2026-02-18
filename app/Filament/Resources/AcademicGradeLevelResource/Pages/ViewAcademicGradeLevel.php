<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

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
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
