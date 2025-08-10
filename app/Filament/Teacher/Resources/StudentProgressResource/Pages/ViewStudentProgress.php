<?php

namespace App\Filament\Teacher\Resources\StudentProgressResource\Pages;

use App\Filament\Teacher\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentProgress extends ViewRecord
{
    protected static string $resource = StudentProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تحديث التقدم'),
        ];
    }
}