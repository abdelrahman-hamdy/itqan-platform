<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSessionResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
