<?php

namespace App\Filament\Resources\AcademicTeacherProfileResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AcademicTeacherProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicTeacherProfiles extends ListRecords
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مدرس جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'المدرسين الأكاديميين';
    }
}
