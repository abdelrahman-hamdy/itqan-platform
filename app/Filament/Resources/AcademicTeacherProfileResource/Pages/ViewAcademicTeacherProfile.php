<?php

namespace App\Filament\Resources\AcademicTeacherProfileResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicTeacherProfile extends ViewRecord
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
