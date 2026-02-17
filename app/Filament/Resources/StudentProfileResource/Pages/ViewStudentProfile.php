<?php

namespace App\Filament\Resources\StudentProfileResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\StudentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewStudentProfile extends ViewRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
