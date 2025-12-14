<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\SupervisorProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SupervisorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (!$profile) {
            return [
                Stat::make('غير مصرح', 'لا يوجد ملف مشرف')
                    ->description('يرجى التواصل مع المسؤول')
                    ->color('danger'),
            ];
        }

        $academyId = $profile->academy_id;
        $assignedTeachers = $profile->assigned_teachers ?? [];

        // Build base queries
        $circlesQuery = QuranCircle::where('academy_id', $academyId);
        $sessionsQuery = QuranSession::where('academy_id', $academyId);

        // Filter by assigned teachers if set
        if (!empty($assignedTeachers)) {
            $circlesQuery->whereIn('quran_teacher_id', $assignedTeachers);
            $sessionsQuery->whereIn('quran_teacher_id', $assignedTeachers);
        }

        // Calculate stats
        $totalCircles = (clone $circlesQuery)->count();
        $activeCircles = (clone $circlesQuery)->where('status', true)->count();

        $todaySessions = (clone $sessionsQuery)
            ->whereDate('scheduled_at', today())
            ->count();

        $completedThisWeek = (clone $sessionsQuery)
            ->where('status', SessionStatus::COMPLETED->value)
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $ongoingSessions = (clone $sessionsQuery)
            ->where('status', SessionStatus::ONGOING->value)
            ->count();

        $totalStudents = (clone $circlesQuery)->sum('enrolled_students');

        return [
            Stat::make('الحلقات النشطة', $activeCircles)
                ->description('من إجمالي ' . $totalCircles . ' حلقة')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, $activeCircles]),

            Stat::make('جلسات اليوم', $todaySessions)
                ->description('جارية الآن: ' . $ongoingSessions)
                ->descriptionIcon('heroicon-o-video-camera')
                ->color($ongoingSessions > 0 ? 'warning' : 'primary'),

            Stat::make('المكتملة هذا الأسبوع', $completedThisWeek)
                ->description('جلسات مكتملة')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('إجمالي الطلاب', $totalStudents)
                ->description('في الحلقات المراقبة')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('info'),
        ];
    }
}
