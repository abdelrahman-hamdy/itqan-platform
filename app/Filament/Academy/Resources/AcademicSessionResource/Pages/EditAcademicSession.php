<?php

namespace App\Filament\Academy\Resources\AcademicSessionResource\Pages;

use App\Filament\Academy\Resources\AcademicSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\DeleteAction;

class EditAcademicSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
