<?php

namespace App\Filament\Resources\AcademicTeacherProfileResource\Pages;

use App\Filament\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicTeacherProfile extends EditRecord
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
