<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\InteractiveCourse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorInteractiveCoursesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();
        $responsibleCourseIds = $this->getResponsibleResourceIds(InteractiveCourse::class);
        $derivedCourseIds = $this->getDerivedInteractiveCourseIds();

        $allCourseIds = array_unique(array_merge($responsibleCourseIds, $derivedCourseIds));

        $query = InteractiveCourse::query()
            ->where(function ($q) use ($academicTeacherProfileIds, $allCourseIds) {
                $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds)
                  ->orWhereIn('id', $allCourseIds);
            })
            ->with(['assignedTeacher.user', 'subject', 'gradeLevel', 'enrollments']);

        if ($request->teacher_id) {
            $profileIds = \App\Models\AcademicTeacherProfile::where('user_id', $request->teacher_id)->pluck('id')->toArray();
            $query->whereIn('assigned_teacher_id', $profileIds);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $courses = $query->latest()->paginate(15)->withQueryString();

        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $teachers = User::whereIn('id', $academicTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();

        return view('supervisor.interactive-courses.index', compact('courses', 'teachers'));
    }

    public function show($subdomain, $courseId): View
    {
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();
        $responsibleCourseIds = $this->getResponsibleResourceIds(InteractiveCourse::class);
        $derivedCourseIds = $this->getDerivedInteractiveCourseIds();
        $allCourseIds = array_unique(array_merge($responsibleCourseIds, $derivedCourseIds));

        $course = InteractiveCourse::where(function ($q) use ($academicTeacherProfileIds, $allCourseIds) {
            $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds)
              ->orWhereIn('id', $allCourseIds);
        })->with([
            'assignedTeacher.user',
            'subject',
            'gradeLevel',
            'enrollments.student',
            'sessions' => fn ($q) => $q->orderBy('scheduled_date')->orderBy('scheduled_time'),
        ])->findOrFail($courseId);

        $teacher = $course->assignedTeacher?->user;

        return view('supervisor.interactive-courses.show', compact('course', 'teacher'));
    }
}
