<?php

namespace App\Filament\Widgets;

use App\Models\Academy;
use App\Models\User;
use App\Models\RecordedCourse;
use App\Models\AcademicTeacher;
use App\Services\AcademyContextService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        
        // If super admin has selected an academy, show academy-specific stats
        if ($currentAcademy) {
            return $this->getAcademyStats($currentAcademy);
        }
        
        // If super admin with no academy selected or system overview, show platform stats
        if (AcademyContextService::isSuperAdmin()) {
            return $this->getPlatformStats();
        }
        
        // Fallback - should not happen
        return [];
    }
    
    private function getAcademyStats(Academy $academy): array
    {
        $academyUsers = User::where('academy_id', $academy->id)->count();
        $academyTeachers = User::where('academy_id', $academy->id)->where('role', 'teacher')->count();
        $academyStudents = User::where('academy_id', $academy->id)->where('role', 'student')->count();
        $academyCourses = RecordedCourse::where('academy_id', $academy->id)->count();
        
        return [
            Stat::make('مستخدمي الأكاديمية', number_format($academyUsers))
                ->description($academy->name)
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('المعلمين', number_format($academyTeachers))
                ->description('معلمين نشطين')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
                
            Stat::make('الطلاب', number_format($academyStudents))
                ->description('طلاب مسجلين')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
                
            Stat::make('الدورات المسجلة', number_format($academyCourses))
                ->description('دورات متاحة')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('warning'),
        ];
    }
    
    private function getPlatformStats(): array
    {
        $totalAcademies = Academy::count();
        $activeAcademies = Academy::where('status', 'active')->where('is_active', true)->count();
        $totalUsers = User::count();
        $totalRevenue = Academy::sum('total_revenue');
        
        // Users by role
        $teachers = User::where('role', 'teacher')->count();
        $students = User::where('role', 'student')->count();
        $parents = User::where('role', 'parent')->count();
        
        return [
            Stat::make('إجمالي الأكاديميات', $totalAcademies)
                ->description($activeAcademies . ' نشطة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart([7, 12, 18, 15, 22, 28, $totalAcademies]),
                
            Stat::make('إجمالي المستخدمين', number_format($totalUsers))
                ->description($teachers . ' معلم، ' . $students . ' طالب')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([150, 280, 420, 680, 920, 1200, $totalUsers]),
                
            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ر.س')
                ->description('من جميع الأكاديميات')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->chart([5000, 8000, 12000, 18000, 25000, 32000, $totalRevenue]),
                
            Stat::make('الآباء المسجلين', number_format($parents))
                ->description('حسابات أولياء الأمور')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([50, 85, 120, 180, 250, 320, $parents]),
        ];
    }
    
    public static function canView(): bool
    {
        // Show for super admin always, or for users with academy context
        return AcademyContextService::isSuperAdmin() || AcademyContextService::getCurrentAcademy() !== null;
    }
} 