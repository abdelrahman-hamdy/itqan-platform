<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\View\View;

class SupervisorDashboardController extends BaseSupervisorWebController
{
    public function index($subdomain = null): View
    {
        $user = auth()->user();
        $profile = $this->getCurrentSupervisorProfile();

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        // Teacher counts
        $quranTeachersCount = count($quranTeacherIds);
        $academicTeachersCount = count($academicTeacherIds);
        $totalTeachers = $quranTeachersCount + $academicTeachersCount;

        // Active entities
        $activeCircles = 0;
        $activeLessons = 0;

        if (!empty($quranTeacherIds)) {
            $activeCircles += QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('status', 'active')->count();
            $activeCircles += QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('is_active', true)->count();
        }

        if (!empty($academicTeacherProfileIds)) {
            $activeLessons += AcademicIndividualLesson::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->where('status', 'active')->count();
        }

        // Sessions today
        $today = now()->startOfDay();
        $endOfDay = now()->endOfDay();
        $sessionsToday = 0;

        if (!empty($quranTeacherIds)) {
            $sessionsToday += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
        }
        if (!empty($academicTeacherProfileIds)) {
            $sessionsToday += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
        }

        // Sessions this week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $sessionsThisWeek = 0;

        if (!empty($quranTeacherIds)) {
            $sessionsThisWeek += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->count();
        }
        if (!empty($academicTeacherProfileIds)) {
            $sessionsThisWeek += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->count();
        }

        // Upcoming sessions (next 5)
        $upcomingSessions = collect();

        if (!empty($quranTeacherIds)) {
            $quranUpcoming = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('scheduled_at', '>', now())
                ->with(['quranTeacher', 'student', 'circle', 'individualCircle'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'quran',
                    'title' => $s->circle?->name ?? $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->quranTeacher?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $upcomingSessions = $upcomingSessions->merge($quranUpcoming);
        }

        if (!empty($academicTeacherProfileIds)) {
            $academicUpcoming = AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->where('scheduled_at', '>', now())
                ->with(['academicTeacher.user', 'student'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'academic',
                    'title' => $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->academicTeacher?->user?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $upcomingSessions = $upcomingSessions->merge($academicUpcoming);
        }

        $upcomingSessions = $upcomingSessions->sortBy('scheduled_at')->take(5);

        return view('supervisor.dashboard', compact(
            'user',
            'profile',
            'totalTeachers',
            'quranTeachersCount',
            'academicTeachersCount',
            'activeCircles',
            'activeLessons',
            'sessionsToday',
            'sessionsThisWeek',
            'upcomingSessions',
        ));
    }
}
