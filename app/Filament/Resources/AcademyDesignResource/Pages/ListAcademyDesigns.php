<?php

namespace App\Filament\Resources\AcademyDesignResource\Pages;

use App\Filament\Resources\AcademyDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademyDesigns extends ListRecords
{
    protected static string $resource = AcademyDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
