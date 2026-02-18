<?php

namespace App\Filament\Academy\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Academy\Resources\AcademicGradeLevelResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicGradeLevel extends ViewRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

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
