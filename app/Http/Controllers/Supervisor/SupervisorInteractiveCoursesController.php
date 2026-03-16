<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
            'sessions' => fn ($q) => $q->orderBy('scheduled_at'),
        ])->findOrFail($courseId);

        $teacher = $course->assignedTeacher?->user;
        $isAdmin = $this->isAdminUser();

        // Get available students for enrollment (admin only)
        $availableStudents = collect();
        if ($isAdmin) {
            $enrolledStudentProfileIds = $course->enrollments->pluck('student_id')->toArray();
            $academyId = $this->getAcademyId();
            $availableStudents = User::where('user_type', 'student')
                ->where('academy_id', $academyId)
                ->whereHas('studentProfile', function ($q) use ($enrolledStudentProfileIds) {
                    if (! empty($enrolledStudentProfileIds)) {
                        $q->whereNotIn('id', $enrolledStudentProfileIds);
                    }
                })
                ->with('studentProfile')
                ->limit(100)
                ->get();
        }

        return view('supervisor.interactive-courses.show', compact('course', 'teacher', 'isAdmin', 'availableStudents'));
    }

    public function addEnrollment(Request $request, $subdomain, $courseId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $course = $this->findCourseInScope($courseId);

        $validated = $request->validate([
            'student_id' => 'required|exists:student_profiles,id',
        ]);

        // Check if already enrolled
        $existing = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $validated['student_id'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', __('supervisor.interactive_courses.already_enrolled'));
        }

        InteractiveCourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $validated['student_id'],
            'academy_id' => $course->academy_id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return redirect()->back()->with('success', __('supervisor.interactive_courses.enrollment_added'));
    }

    public function removeEnrollment(Request $request, $subdomain, $courseId, $enrollmentId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $course = $this->findCourseInScope($courseId);

        $enrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->findOrFail($enrollmentId);

        $enrollment->delete();

        return redirect()->back()->with('success', __('supervisor.interactive_courses.enrollment_removed'));
    }

    private function findCourseInScope($courseId): InteractiveCourse
    {
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();
        $responsibleCourseIds = $this->getResponsibleResourceIds(InteractiveCourse::class);
        $derivedCourseIds = $this->getDerivedInteractiveCourseIds();
        $allCourseIds = array_unique(array_merge($responsibleCourseIds, $derivedCourseIds));

        return InteractiveCourse::where(function ($q) use ($academicTeacherProfileIds, $allCourseIds) {
            $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds)
              ->orWhereIn('id', $allCourseIds);
        })->findOrFail($courseId);
    }
}
