<?php

namespace App\Filament\Resources\StudentProfileResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StudentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentProfiles extends ListRecords
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
