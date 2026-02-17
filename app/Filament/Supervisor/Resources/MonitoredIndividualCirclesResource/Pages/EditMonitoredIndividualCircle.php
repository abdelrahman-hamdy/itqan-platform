<?php

namespace App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditMonitoredIndividualCircle extends EditRecord
{
    protected static string $resource = MonitoredIndividualCirclesResource::class;

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

    public function getHeading(): string
    {
        return 'تعديل: ' . ($this->getRecord()->name ?? 'حلقة فردية');
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->name ?? 'حلقة فردية';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'الحلقات الفردية',
            '' => $this->getBreadcrumb(),
        ];
    }
}
