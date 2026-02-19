<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AcademicGradeLevelResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicGradeLevels extends ListRecords
{
    protected static string $resource = AcademicGradeLevelResource::class;

    public function getTitle(): string
    {
        return 'الصفوف الدراسية';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
