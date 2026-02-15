<?php

namespace App\Filament\Resources\TeacherEarningResource\Pages;

use App\Filament\Resources\TeacherEarningResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherEarning extends ViewRecord
{
    protected static string $resource = TeacherEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
