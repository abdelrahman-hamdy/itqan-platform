<?php

namespace App\Filament\Resources\SuperAdminQuranTeacherResource\Pages;

use App\Filament\Resources\SuperAdminQuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperAdminQuranTeacher extends EditRecord
{
    protected static string $resource = SuperAdminQuranTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}