<?php

namespace App\Filament\Academy\Resources\AcademicSubjectResource\Pages;

use App\Filament\Academy\Resources\AcademicSubjectResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubject extends ViewRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
