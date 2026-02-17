<?php

namespace App\Filament\Resources\AcademicPackageResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicPackage extends ViewRecord
{
    protected static string $resource = AcademicPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'عرض الباقة الأكاديمية';
    }
}
