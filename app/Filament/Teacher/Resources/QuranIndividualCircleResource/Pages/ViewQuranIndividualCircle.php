<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
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
            static::getResource()::getUrl() => 'حلقاتي الفردية',
            '' => $this->getBreadcrumb(),
        ];
    }
}
