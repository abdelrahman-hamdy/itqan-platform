<?php

namespace App\Filament\Academy\Resources\AcademicPackageResource\Pages;

use App\Filament\Academy\Resources\AcademicPackageResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicPackage extends ViewRecord
{
    protected static string $resource = AcademicPackageResource::class;

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
