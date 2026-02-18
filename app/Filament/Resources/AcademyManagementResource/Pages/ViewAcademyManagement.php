<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademyManagement extends ViewRecord
{
    protected static string $resource = AcademyManagementResource::class;

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
