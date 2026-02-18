<?php

namespace App\Filament\Academy\Resources\StudentProgressResource\Pages;

use App\Filament\Academy\Resources\StudentProgressResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\EditAction;

class ViewStudentProgress extends ViewRecord
{
    protected static string $resource = StudentProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
