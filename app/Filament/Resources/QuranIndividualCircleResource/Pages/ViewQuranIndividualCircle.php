<?php

namespace App\Filament\Resources\QuranIndividualCircleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use App\Filament\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranIndividualCircle extends ViewRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
            RestoreAction::make()
                ->label('استعادة'),
            ForceDeleteAction::make()
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
