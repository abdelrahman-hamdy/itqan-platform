<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    /**
     * Get navigation items for a specific role.
     */
    public function getNavItems(string $role, ?User $user = null): array
    {
        return match ($role) {
            'teacher' => $this->getTeacherNavItems($user),
            'parent' => $this->getParentNavItems(),
            default => $this->getStudentNavItems(),
        };
    }

    /**
     * Get student navigation items.
     */
    protected function getStudentNavItems(): array
    {
        $items = [];

        if (Route::has('quran-circles.index')) {
            $items[] = [
                'route' => 'quran-circles.index',
                'label' => __('sessions.navigation.quran_circles'),
                'activeRoutes' => ['quran-circles.index', 'quran-circles.show'],
                'color' => 'green',
            ];
        }

        if (Route::has('quran-teachers.index')) {
            $items[] = [
                'route' => 'quran-teachers.index',
                'label' => __('sessions.navigation.quran_teachers'),
                'activeRoutes' => ['quran-teachers.index', 'quran-teachers.show'],
                'color' => 'yellow',
            ];
        }

        if (Route::has('interactive-courses.index')) {
            $items[] = [
                'route' => 'interactive-courses.index',
                'label' => __('sessions.navigation.interactive_courses'),
                'activeRoutes' => ['interactive-courses.index', 'interactive-courses.show'],
                'color' => 'blue',
            ];
        }

        if (Route::has('academic-teachers.index')) {
            $items[] = [
                'route' => 'academic-teachers.index',
                'label' => __('sessions.navigation.academic_teachers'),
                'activeRoutes' => ['academic-teachers.index', 'academic-teachers.show'],
                'color' => 'violet',
            ];
        }

        if (Route::has('courses.index')) {
            $items[] = [
                'route' => 'courses.index',
                'label' => __('sessions.navigation.recorded_courses'),
                'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn', 'lessons.show'],
                'color' => 'cyan',
            ];
        }

        return $items;
    }

    /**
     * Get teacher navigation items.
     */
    protected function getTeacherNavItems(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isQuranTeacher()) {
            return [
                [
                    'href' => '/teacher-panel/quran-sessions',
                    'label' => __('sessions.navigation.session_schedule'),
                    'icon' => 'ri-calendar-schedule-line',
                    'activeRoutes' => [],
                ],
                [
                    'href' => '/teacher-panel/quran-trial-requests',
                    'label' => __('sessions.navigation.trial_sessions'),
                    'icon' => 'ri-user-add-line',
                    'activeRoutes' => [],
                ],
                [
                    'href' => '/teacher-panel/quran-session-reports',
                    'label' => __('sessions.navigation.session_reports'),
                    'icon' => 'ri-file-chart-line',
                    'activeRoutes' => [],
                ],
            ];
        }

        if ($user->isAcademicTeacher()) {
            return [
                [
                    'href' => '/academic-teacher-panel/academic-sessions',
                    'label' => __('sessions.navigation.session_schedule'),
                    'icon' => 'ri-calendar-schedule-line',
                    'activeRoutes' => [],
                ],
                [
                    'href' => '/academic-teacher-panel/homework-submissions',
                    'label' => __('sessions.navigation.homework'),
                    'icon' => 'ri-file-list-3-line',
                    'activeRoutes' => [],
                ],
                [
                    'href' => '/academic-teacher-panel/academic-session-reports',
                    'label' => __('sessions.navigation.session_reports'),
                    'icon' => 'ri-file-chart-line',
                    'activeRoutes' => [],
                ],
            ];
        }

        return [];
    }

    /**
     * Get parent navigation items.
     */
    protected function getParentNavItems(): array
    {
        $items = [];

        if (Route::has('parent.dashboard')) {
            $items[] = [
                'route' => 'parent.dashboard',
                'label' => __('common.navigation.home'),
                'icon' => 'ri-dashboard-line',
                'activeRoutes' => ['parent.dashboard'],
            ];
        }

        if (Route::has('parent.sessions.upcoming')) {
            $items[] = [
                'route' => 'parent.sessions.upcoming',
                'label' => __('sessions.navigation.upcoming_sessions'),
                'icon' => 'ri-calendar-event-line',
                'activeRoutes' => ['parent.sessions.*'],
            ];
        }

        if (Route::has('parent.subscriptions.index')) {
            $items[] = [
                'route' => 'parent.subscriptions.index',
                'label' => __('sessions.navigation.subscriptions'),
                'icon' => 'ri-file-list-line',
                'activeRoutes' => ['parent.subscriptions.*'],
            ];
        }

        if (Route::has('parent.reports.progress')) {
            $items[] = [
                'route' => 'parent.reports.progress',
                'label' => __('sessions.navigation.reports'),
                'icon' => 'ri-bar-chart-line',
                'activeRoutes' => ['parent.reports.*'],
            ];
        }

        return $items;
    }

    /**
     * Get user profile information for display.
     */
    public function getUserDisplayInfo(string $role, ?User $user): array
    {
        if (! $user) {
            return [
                'displayName' => $role === 'parent' ? __('sessions.roles.parent') : ($role === 'teacher' ? __('sessions.roles.teacher') : __('sessions.roles.guest')),
                'roleLabel' => $role === 'parent' ? __('sessions.roles.parent') : ($role === 'teacher' ? __('sessions.roles.teacher') : __('sessions.roles.student')),
                'avatarType' => $role,
                'gender' => 'male',
            ];
        }

        return match ($role) {
            'student' => $this->getStudentDisplayInfo($user),
            'parent' => $this->getParentDisplayInfo($user),
            'teacher' => $this->getTeacherDisplayInfo($user),
            default => $this->getStudentDisplayInfo($user),
        };
    }

    protected function getStudentDisplayInfo(User $user): array
    {
        $profile = $user->studentProfile;

        return [
            'displayName' => $profile?->first_name ?? $user->name ?? __('sessions.roles.guest'),
            'roleLabel' => __('sessions.roles.student'),
            'avatarType' => 'student',
            'gender' => $profile?->gender ?? $user->gender ?? 'male',
        ];
    }

    protected function getParentDisplayInfo(User $user): array
    {
        $profile = $user->parentProfile;

        return [
            'displayName' => $profile?->getFullNameAttribute() ?? $user->name ?? __('sessions.roles.parent'),
            'roleLabel' => __('sessions.roles.parent'),
            'avatarType' => 'parent',
            'gender' => $profile?->gender ?? $user->gender ?? 'male',
        ];
    }

    protected function getTeacherDisplayInfo(User $user): array
    {
        $isQuranTeacher = $user->isQuranTeacher();
        $profile = $isQuranTeacher ? $user->quranTeacherProfile : $user->academicTeacherProfile;

        return [
            'displayName' => $profile?->first_name ?? $user->name ?? __('sessions.roles.teacher'),
            'roleLabel' => $isQuranTeacher ? __('sessions.roles.quran_teacher') : __('sessions.roles.academic_teacher'),
            'avatarType' => $isQuranTeacher ? 'quran_teacher' : 'academic_teacher',
            'gender' => $profile?->gender ?? $user->gender ?? 'male',
        ];
    }

    /**
     * Check if a route is active based on current route name.
     */
    public function isRouteActive(string $currentRoute, array $activeRoutes): bool
    {
        foreach ($activeRoutes as $activeRoute) {
            if (str_contains($activeRoute, '*')) {
                $pattern = str_replace('*', '', $activeRoute);
                if (str_starts_with($currentRoute, $pattern)) {
                    return true;
                }
            } elseif ($currentRoute === $activeRoute) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the color classes for a navigation item.
     */
    public function getNavItemColors(array $item): array
    {
        $color = $item['color'] ?? 'gray';

        return [
            'active' => "text-{$color}-600",
            'hover' => "hover:text-{$color}-600 hover:bg-gray-100",
            'mobileActive' => "text-{$color}-600 bg-{$color}-50",
            'mobileHover' => "hover:text-{$color}-600 hover:bg-{$color}-50",
        ];
    }

    /**
     * Get the icon for a navigation item.
     */
    public function getNavItemIcon(array $item): string
    {
        if (isset($item['icon'])) {
            return $item['icon'];
        }

        $route = $item['route'] ?? '';

        return match ($route) {
            'courses.index' => 'ri-play-circle-line',
            'quran-circles.index' => 'ri-quill-pen-line',
            'quran-teachers.index' => 'ri-user-star-line',
            'interactive-courses.index' => 'ri-live-line',
            'academic-teachers.index' => 'ri-user-settings-line',
            default => 'ri-arrow-left-line',
        };
    }
}
