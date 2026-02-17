<?php

namespace App\Filament\Resources\StudentProfileResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StudentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentProfile extends EditRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
