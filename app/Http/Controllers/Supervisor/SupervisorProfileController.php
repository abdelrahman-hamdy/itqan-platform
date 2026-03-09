<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\UserType;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\User;
use Illuminate\Http\Request;

class SupervisorProfileController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain)
    {
        $user = auth()->user();
        $profile = $user->supervisorProfile;

        if ($this->isAdminUser()) {
            $quranTeacherCount = User::where('user_type', UserType::QURAN_TEACHER->value)->count();
            $academicTeacherCount = User::where('user_type', UserType::ACADEMIC_TEACHER->value)->count();
            $interactiveCourseCount = InteractiveCourse::count();
        } else {
            $quranTeacherCount = $profile ? $profile->quranTeachers()->count() : 0;
            $academicTeacherCount = $profile ? $profile->academicTeachers()->count() : 0;
            $interactiveCourseCount = $profile ? ($profile->interactiveCourses()->count() + $profile->getDerivedInteractiveCoursesCount()) : 0;
        }

        return view('supervisor.profile', compact(
            'user',
            'profile',
            'quranTeacherCount',
            'academicTeacherCount',
            'interactiveCourseCount',
        ));
    }
}
