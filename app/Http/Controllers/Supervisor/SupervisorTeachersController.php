<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\View\View;

class SupervisorTeachersController extends BaseSupervisorWebController
{
    public function index($subdomain = null): View
    {
        $profile = $this->getCurrentSupervisorProfile();

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        // Load Quran teachers
        if (!empty($quranTeacherIds)) {
            $quranTeachers = User::whereIn('id', $quranTeacherIds)
                ->with('quranTeacherProfile')
                ->get()
                ->map(function ($user) {
                    $activeCircles = QuranCircle::where('quran_teacher_id', $user->id)
                        ->where('status', 'active')->count();
                    $activeIndividual = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                        ->where('is_active', true)->count();

                    return [
                        'user' => $user,
                        'type' => 'quran',
                        'type_label' => __('supervisor.teachers.teacher_type_quran'),
                        'code' => $user->quranTeacherProfile?->teacher_code ?? '',
                        'active_entities' => $activeCircles + $activeIndividual,
                        'entity_route' => 'supervisor.group-circles.index',
                    ];
                });
            $teachers = $teachers->merge($quranTeachers);
        }

        // Load Academic teachers
        if (!empty($academicTeacherIds)) {
            $academicTeachers = User::whereIn('id', $academicTeacherIds)
                ->with('academicTeacherProfile')
                ->get()
                ->map(function ($user) {
                    $profileId = $user->academicTeacherProfile?->id;
                    $activeLessons = $profileId
                        ? AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                            ->where('status', 'active')->count()
                        : 0;

                    return [
                        'user' => $user,
                        'type' => 'academic',
                        'type_label' => __('supervisor.teachers.teacher_type_academic'),
                        'code' => $user->academicTeacherProfile?->teacher_code ?? '',
                        'active_entities' => $activeLessons,
                        'entity_route' => 'supervisor.academic-lessons.index',
                    ];
                });
            $teachers = $teachers->merge($academicTeachers);
        }

        return view('supervisor.teachers.index', compact('teachers'));
    }
}
