<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitoredAcademicLesson extends EditRecord
{
    protected static string $resource = MonitoredAcademicLessonsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التغييرات بنجاح';
    }
}
