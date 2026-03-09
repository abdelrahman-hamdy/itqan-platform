<?php

namespace App\Http\Controllers\Supervisor;

use Illuminate\Http\Request;

class SupervisorProfileController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain)
    {
        $user = auth()->user();
        $profile = $user->supervisorProfile;

        $quranTeacherCount = $profile ? $profile->quranTeachers()->count() : 0;
        $academicTeacherCount = $profile ? $profile->academicTeachers()->count() : 0;
        $interactiveCourseCount = $profile ? ($profile->interactiveCourses()->count() + $profile->getDerivedInteractiveCoursesCount()) : 0;

        return view('supervisor.profile', compact(
            'user',
            'profile',
            'quranTeacherCount',
            'academicTeacherCount',
            'interactiveCourseCount',
        ));
    }
}
