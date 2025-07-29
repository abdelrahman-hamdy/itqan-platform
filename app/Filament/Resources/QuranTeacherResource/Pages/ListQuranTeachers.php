<?php

namespace App\Filament\Resources\QuranTeacherResource\Pages;

use App\Filament\Resources\QuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranTeachers extends ListRecords
{
    protected static string $resource = QuranTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة معلم قرآن جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'معلمو القرآن الكريم';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}
