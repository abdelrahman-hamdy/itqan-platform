<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\StudentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditStudentProfile extends EditRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
