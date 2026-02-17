<?php

namespace App\Filament\Resources\AcademicPackageResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AcademicPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicPackages extends ListRecords
{
    protected static string $resource = AcademicPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة باقة أكاديمية جديدة'),
        ];
    }

    public function getTitle(): string
    {
        return 'الباقات الأكاديمية';
    }
}
