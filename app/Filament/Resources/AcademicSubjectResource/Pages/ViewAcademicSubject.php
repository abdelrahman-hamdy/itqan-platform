<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubject extends ViewRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'عرض المادة الأكاديمية';

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
