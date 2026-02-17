<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSessionReport extends EditRecord
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
