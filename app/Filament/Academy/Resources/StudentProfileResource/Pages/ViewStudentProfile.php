<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use App\Filament\Academy\Resources\StudentProfileResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewStudentProfile extends ViewRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
