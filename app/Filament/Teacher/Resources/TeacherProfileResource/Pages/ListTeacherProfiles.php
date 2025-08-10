<?php

namespace App\Filament\Teacher\Resources\TeacherProfileResource\Pages;

use App\Filament\Teacher\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherProfiles extends ListRecords
{
    protected static string $resource = TeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}