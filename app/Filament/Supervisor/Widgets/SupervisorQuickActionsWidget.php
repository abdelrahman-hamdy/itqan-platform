<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\InteractiveCourseStatus;
use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource;
use App\Filament\Supervisor\Resources\ManagedTeachersResource;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SupervisorQuickActionsWidget extends Widget
{
    protected static bool $isDiscoverable = false;

    protected string $view = 'filament.supervisor.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return [
                'actions' => [],
                'hasResponsibilities' => false,
            ];
        }

        // Get assigned teacher IDs
        $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
        $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
        $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

        $actions = [];

        // Quran Group Circles
        if (! empty($quranTeacherIds)) {
            $groupCircles = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)->where('status', true)->count();

            $actions[] = [
                'title' => 'الحلقات الجماعية',
                'count' => $groupCircles,
                'description' => 'حلقة نشطة',
                'url' => MonitoredGroupCirclesResource::getUrl('index'),
                'icon' => 'heroicon-o-user-group',
                'color' => 'success',
            ];

            // Quran Individual Circles
            $individualCircles = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)->where('is_active', true)->count();

            $actions[] = [
                'title' => 'الحلقات الفردية',
                'count' => $individualCircles,
                'description' => 'حلقة نشطة',
                'url' => MonitoredIndividualCirclesResource::getUrl('index'),
                'icon' => 'heroicon-o-user',
                'color' => 'info',
            ];
        }

        // Academic Lessons
        if (! empty($academicTeacherIds)) {
            $profileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->pluck('id');
            $activeLessons = AcademicIndividualLesson::whereIn('academic_teacher_id', $profileIds)->where('status', 'active')->count();

            $actions[] = [
                'title' => 'الدروس الأكاديمية',
                'count' => $activeLessons,
                'description' => 'درس نشط',
                'url' => MonitoredAcademicLessonsResource::getUrl('index'),
                'icon' => 'heroicon-o-academic-cap',
                'color' => 'warning',
            ];
        }

        // Interactive Courses
        if (! empty($interactiveCourseIds)) {
            $activeCourses = InteractiveCourse::whereIn('id', $interactiveCourseIds)
                ->whereIn('status', [InteractiveCourseStatus::PUBLISHED, InteractiveCourseStatus::ACTIVE])
                ->count();

            $actions[] = [
                'title' => 'الدورات التفاعلية',
                'count' => $activeCourses,
                'description' => 'دورة نشطة',
                'url' => MonitoredInteractiveCoursesResource::getUrl('index'),
                'icon' => 'heroicon-o-video-camera',
                'color' => 'primary',
            ];
        }

        // All Sessions (unified)
        if (! empty($quranTeacherIds) || ! empty($academicTeacherIds) || ! empty($interactiveCourseIds)) {
            $actions[] = [
                'title' => 'جميع الجلسات',
                'count' => '',
                'description' => 'عرض جميع الجلسات',
                'url' => MonitoredAllSessionsResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'gray',
            ];
        }

        // Teacher management actions (if enabled)
        if ($profile->canManageTeachers()) {
            $totalTeachers = count($quranTeacherIds) + count($academicTeacherIds);

            if ($totalTeachers > 0) {
                $actions[] = [
                    'title' => 'إدارة المعلمين',
                    'count' => $totalTeachers,
                    'description' => 'معلم تحت الإدارة',
                    'url' => ManagedTeachersResource::getUrl('index'),
                    'icon' => 'heroicon-o-users',
                    'color' => 'gray',
                ];

                $actions[] = [
                    'title' => 'أرباح المعلمين',
                    'count' => '',
                    'description' => 'عرض جميع الأرباح',
                    'url' => ManagedTeacherEarningsResource::getUrl('index'),
                    'icon' => 'heroicon-o-currency-dollar',
                    'color' => 'gray',
                ];
            }
        }

        return [
            'actions' => $actions,
            'hasResponsibilities' => ! empty($actions),
        ];
    }
}
