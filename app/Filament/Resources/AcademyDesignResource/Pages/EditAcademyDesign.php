<?php

namespace App\Filament\Resources\AcademyDesignResource\Pages;

use App\Filament\Resources\AcademyDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademyDesign extends EditRecord
{
    protected static string $resource = AcademyDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
