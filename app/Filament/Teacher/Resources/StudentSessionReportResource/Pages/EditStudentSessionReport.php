<?php

namespace App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Teacher\Resources\StudentSessionReportResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditStudentSessionReport extends EditRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'تعديل تقرير '.($this->getRecord()->student->name ?? 'الطالب');
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'تقارير الطلاب',
            '' => $this->getBreadcrumb(),
        ];
    }
}
