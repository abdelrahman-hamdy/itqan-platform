<?php

namespace App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;

use App\Filament\Teacher\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentSessionReport extends EditRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
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
