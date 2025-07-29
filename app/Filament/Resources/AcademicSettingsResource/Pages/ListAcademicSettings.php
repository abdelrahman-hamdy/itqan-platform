<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSettings extends ListRecords
{
    protected static string $resource = AcademicSettingsResource::class;

    public function getTitle(): string
    {
        return 'الإعدادات الأكاديمية';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة إعدادات جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }
}
