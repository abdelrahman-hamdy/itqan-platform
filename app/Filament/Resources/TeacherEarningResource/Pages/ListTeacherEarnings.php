<?php

namespace App\Filament\Resources\TeacherEarningResource\Pages;

use App\Filament\Resources\TeacherEarningResource;
use Filament\Resources\Pages\ListRecords;

class ListTeacherEarnings extends ListRecords
{
    protected static string $resource = TeacherEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
