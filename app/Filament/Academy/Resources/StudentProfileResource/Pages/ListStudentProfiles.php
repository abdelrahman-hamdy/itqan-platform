<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use App\Filament\Academy\Resources\StudentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentProfiles extends ListRecords
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
