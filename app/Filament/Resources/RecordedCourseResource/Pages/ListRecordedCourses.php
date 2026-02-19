<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\RecordedCourseResource;
use Filament\Resources\Pages\ListRecords;

class ListRecordedCourses extends ListRecords
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة دورة جديدة'),
        ];
    }

    public function getTitle(): string
    {
        return 'الدورات المسجلة';
    }
}
