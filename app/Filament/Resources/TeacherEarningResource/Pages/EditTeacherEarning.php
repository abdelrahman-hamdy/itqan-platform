<?php

namespace App\Filament\Resources\TeacherEarningResource\Pages;

use App\Filament\Resources\TeacherEarningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherEarning extends EditRecord
{
    protected static string $resource = TeacherEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
