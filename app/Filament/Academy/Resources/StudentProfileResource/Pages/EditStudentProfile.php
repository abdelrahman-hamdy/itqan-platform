<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use App\Filament\Academy\Resources\StudentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentProfile extends EditRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
