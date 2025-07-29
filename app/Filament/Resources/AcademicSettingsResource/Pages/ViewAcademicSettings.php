<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSettings extends ViewRecord
{
    protected static string $resource = AcademicSettingsResource::class;

    public function getTitle(): string
    {
        return 'عرض الإعدادات الأكاديمية';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل الإعدادات'),
        ];
    }
} 