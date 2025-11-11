<?php

namespace App\Filament\AcademicTeacher\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\AcademicTeacher\Widgets\AcademicTeacherOverviewWidget;
use App\Filament\AcademicTeacher\Widgets\AcademicCalendarWidget;
use App\Filament\AcademicTeacher\Widgets\RecentAcademicSessionsWidget;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'لوحة التحكم الأكاديمية';
    protected static ?string $title = 'لوحة المعلم الأكاديمي';
    protected static ?string $navigationGroup = 'لوحة التحكم';
    protected static ?string $description = 'مرحباً بك في لوحة التحكم الأكاديمية - إدارة دروسك الفردية والدورات التفاعلية';

    public function mount(): void
    {
        $user = Auth::user();
        
        // Only allow academic teachers
        if (!$user->isAcademicTeacher()) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة المعلم الأكاديمي');
        }
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        
        // Only show academic widgets for academic teachers
        if (!$user->isAcademicTeacher()) {
            return [];
        }
        
        return [
            AcademicTeacherOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        $user = Auth::user();
        
        // Only show academic widgets for academic teachers
        if (!$user->isAcademicTeacher()) {
            return [];
        }
        
        return [
            AcademicCalendarWidget::class,
            RecentAcademicSessionsWidget::class,
        ];
    }
}
