<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\StudentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewStudentProfile extends ViewRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
