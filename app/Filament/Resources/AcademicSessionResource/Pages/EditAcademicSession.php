<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
