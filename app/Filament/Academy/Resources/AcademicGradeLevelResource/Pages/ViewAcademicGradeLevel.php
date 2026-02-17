<?php

namespace App\Filament\Academy\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Academy\Resources\AcademicGradeLevelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicGradeLevel extends ViewRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
