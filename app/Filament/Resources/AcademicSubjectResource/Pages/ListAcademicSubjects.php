<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AcademicSubjectResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSubjects extends ListRecords
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'المواد الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مادة جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }

}
