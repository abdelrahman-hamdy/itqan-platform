<?php

namespace App\Filament\Resources\AcademicTeacherResource\Pages;

use App\Filament\Resources\AcademicTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicTeachers extends ListRecords
{
    protected static string $resource = AcademicTeacherResource::class;

    public function getTitle(): string
    {
        return 'المعلمون الأكاديميون';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة معلم جديد')
                ->icon('heroicon-o-plus'),
        ];
    }
}
