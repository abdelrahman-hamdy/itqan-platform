<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditMonitoredInteractiveCourse extends EditRecord
{
    protected static string $resource = MonitoredInteractiveCoursesResource::class;

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
