<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranTeacherProfiles extends ListRecords
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة معلم جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'معلمي القرآن الكريم';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuranTeacherProfileResource\Widgets\QuranTeachersStatsWidget::class,
        ];
    }
}
