<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

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
