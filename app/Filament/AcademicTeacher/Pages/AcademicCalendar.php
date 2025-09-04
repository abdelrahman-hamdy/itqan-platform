<?php

namespace App\Filament\AcademicTeacher\Pages;

use Filament\Pages\Page;
use App\Filament\AcademicTeacher\Widgets\AcademicFullCalendarWidget;

class AcademicCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'التقويم الأكاديمي';
    protected static ?string $title = 'التقويم الأكاديمي';
    protected static ?string $navigationGroup = 'جلساتي';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.academic-teacher.pages.academic-calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            AcademicFullCalendarWidget::class,
        ];
    }
}
