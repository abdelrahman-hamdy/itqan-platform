<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditQuranIndividualCircle extends EditRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
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
            static::getResource()::getUrl() => 'حلقاتي الفردية',
            '' => $this->getBreadcrumb(),
        ];
    }
}
