<?php

namespace App\Filament\Academy\Resources\AcademicSubjectResource\Pages;

use App\Filament\Academy\Resources\AcademicSubjectResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicSubject extends ViewRecord
{
    protected static string $resource = AcademicSubjectResource::class;

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
