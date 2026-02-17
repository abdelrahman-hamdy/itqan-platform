<?php

namespace App\Filament\Resources\TeacherEarningResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TeacherEarningResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewTeacherEarning extends ViewRecord
{
    protected static string $resource = TeacherEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
