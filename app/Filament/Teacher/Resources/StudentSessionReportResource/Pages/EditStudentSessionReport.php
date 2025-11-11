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
        $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

        $breadcrumbs = [
            route('teacher.profile', ['subdomain' => $subdomain]) => 'ملفي الشخصي',
        ];

        // Add parent breadcrumbs
        $parentBreadcrumbs = parent::getBreadcrumbs();

        // Skip the first item (dashboard) and use our custom profile link instead
        $filteredBreadcrumbs = array_slice($parentBreadcrumbs, 1, null, true);

        return array_merge($breadcrumbs, $filteredBreadcrumbs);
    }
}
