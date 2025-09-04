<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AcademicTeacherOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (!$teacherProfile) {
            return [];
        }

        // Get statistics
        $totalIndividualLessons = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)->count();
        $activeIndividualLessons = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();
        
        $totalSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)->count();
        $completedSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', 'completed')
            ->count();

        $assignedCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)->count();

        return [
            Stat::make('الدروس الفردية', $totalIndividualLessons)
                ->description($activeIndividualLessons . ' نشط')
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),

            Stat::make('إجمالي الجلسات', $totalSessions)
                ->description($completedSessions . ' مكتملة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('الدورات المكلف بها', $assignedCourses)
                ->description('دورات تفاعلية')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}
