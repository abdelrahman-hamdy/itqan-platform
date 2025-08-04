<?php

namespace App\Filament\Resources\SuperAdminQuranTeacherResource\Pages;

use App\Filament\Resources\SuperAdminQuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSuperAdminQuranTeacher extends ViewRecord
{
    protected static string $resource = SuperAdminQuranTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}