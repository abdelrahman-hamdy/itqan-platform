<?php

namespace App\Filament\Academy\Resources\AcademicSubjectResource\Pages;

use App\Filament\Academy\Resources\AcademicSubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSubjects extends ListRecords
{
    protected static string $resource = AcademicSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
