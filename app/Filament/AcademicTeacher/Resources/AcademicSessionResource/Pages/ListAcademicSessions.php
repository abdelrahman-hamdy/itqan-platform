<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSessions extends ListRecords
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
