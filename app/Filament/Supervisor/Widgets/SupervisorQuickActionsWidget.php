<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\InteractiveCourseStatus;
use App\Filament\Supervisor\Resources\ManagedAcademicTeachersResource;
use App\Filament\Supervisor\Resources\ManagedQuranTeachersResource;
use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource;
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

        // Session quick links
        if (! empty($quranTeacherIds)) {
            $actions[] = [
                'title' => 'جلسات القرآن',
                'count' => '',
                'description' => 'عرض جلسات القرآن',
                'url' => MonitoredQuranSessionsResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'gray',
            ];
        }

        if (! empty($academicTeacherIds)) {
            $actions[] = [
                'title' => 'الجلسات الأكاديمية',
                'count' => '',
                'description' => 'عرض الجلسات الأكاديمية',
                'url' => MonitoredAcademicSessionsResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'gray',
            ];
        }

        if (! empty($interactiveCourseIds)) {
            $actions[] = [
                'title' => 'جلسات الدورات',
                'count' => '',
                'description' => 'عرض جلسات الدورات',
                'url' => MonitoredInteractiveCourseSessionsResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'gray',
            ];
        }

        // Teacher management actions (if enabled)
        if ($profile->canManageTeachers()) {
            if (! empty($quranTeacherIds)) {
                $actions[] = [
                    'title' => 'معلمو القرآن',
                    'count' => count($quranTeacherIds),
                    'description' => 'معلم تحت الإدارة',
                    'url' => ManagedQuranTeachersResource::getUrl('index'),
                    'icon' => 'heroicon-o-book-open',
                    'color' => 'gray',
                ];
            }

            if (! empty($academicTeacherIds)) {
                $actions[] = [
                    'title' => 'المعلمون الأكاديميون',
                    'count' => count($academicTeacherIds),
                    'description' => 'معلم تحت الإدارة',
                    'url' => ManagedAcademicTeachersResource::getUrl('index'),
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'gray',
                ];
            }

            if (! empty($quranTeacherIds) || ! empty($academicTeacherIds)) {
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
