<?php

namespace App\Filament\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranIndividualCircle extends ViewRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\DeleteAction::make()
                ->label('حذف'),
            Actions\RestoreAction::make()
                ->label('استعادة'),
            Actions\ForceDeleteAction::make()
                ->label('حذف نهائي'),
        ];
    }

    public function getHeading(): string
    {
        return $this->getRecord()->name ?? 'حلقة فردية';
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
