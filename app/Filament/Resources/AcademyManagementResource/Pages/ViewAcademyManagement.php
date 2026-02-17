<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewAcademyManagement extends ViewRecord
{
    protected static string $resource = AcademyManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
