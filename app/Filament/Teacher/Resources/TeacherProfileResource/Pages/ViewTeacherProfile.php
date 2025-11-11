<?php

namespace App\Filament\Teacher\Resources\TeacherProfileResource\Pages;

use App\Filament\Teacher\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherProfile extends ViewRecord
{
    protected static string $resource = TeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل الملف الشخصي'),
        ];
    }
}