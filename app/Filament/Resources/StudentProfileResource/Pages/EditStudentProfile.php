<?php

namespace App\Filament\Resources\StudentProfileResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StudentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
