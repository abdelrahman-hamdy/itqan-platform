<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\InteractiveCourseStatus;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorInteractiveCoursesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $baseQuery = $this->scopedCourseQuery();

        if ($request->teacher_id) {
            $profileIds = AcademicTeacherProfile::where('user_id', $request->teacher_id)->pluck('id')->toArray();
            $baseQuery->whereIn('assigned_teacher_id', $profileIds);
        }

        // Compute stats on base query before pagination filters
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', InteractiveCourseStatus::ACTIVE)->count(),
            'completed' => (clone $baseQuery)->where('status', InteractiveCourseStatus::COMPLETED)->count(),
            'totalEnrolled' => (int) InteractiveCourseEnrollment::whereIn('course_id', (clone $baseQuery)->select('id'))->count(),
        ];

        $query = clone $baseQuery;
        $query->with(['assignedTeacher.user', 'subject', 'gradeLevel', 'enrollments']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->grade_level_id) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        $courses = $query->latest()->paginate(15)->withQueryString();

        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $teachers = User::whereIn('id', $academicTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();

        $academyId = $this->getAcademyId();
        $subjects = AcademicSubject::where('academy_id', $academyId)->orderBy('name')->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academyId)->orderBy('name')->get();
        $canCreate = $this->canManageInteractiveCourses();

        return view('supervisor.interactive-courses.index', compact(
            'courses', 'teachers', 'stats', 'subjects', 'gradeLevels', 'canCreate'
        ));
    }

    public function show($subdomain, $courseId): View
    {
        $course = $this->findCourseInScope($courseId);
        $course->load([
            'assignedTeacher.user',
            'subject',
            'gradeLevel',
            'enrollments.student',
            'sessions' => fn ($q) => $q->orderBy('scheduled_at'),
        ]);

        $teacher = $course->assignedTeacher?->user;
        $isAdmin = $this->isAdminUser();
        $canManage = $this->canManageInteractiveCourses();

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

        // Get academic teachers for change-teacher modal
        $academicTeachers = collect();
        if ($canManage) {
            $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
            $academicTeachers = User::whereIn('id', $academicTeacherIds)
                ->with('academicTeacherProfile:id,user_id')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']);
        }

        // Load certificates for enrolled students
        $enrolledStudentIds = $course->enrollments->pluck('student_id')->toArray();
        $certificates = \App\Models\Certificate::whereIn('student_id', $enrolledStudentIds)
            ->where('certificate_type', 'interactive_course')
            ->with('student')
            ->latest('issued_at')
            ->get();

        return view('supervisor.interactive-courses.show', compact(
            'course', 'teacher', 'isAdmin', 'canManage', 'availableStudents', 'academicTeachers', 'certificates'
        ));
    }

    public function create($subdomain = null): View
    {
        if (! $this->canManageInteractiveCourses()) {
            abort(403);
        }

        $academyId = $this->getAcademyId();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = User::whereIn('id', $academicTeacherIds)
            ->with('academicTeacherProfile:id,user_id')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'profile_id' => $u->academicTeacherProfile?->id,
                'name' => $u->name,
            ])->toArray();

        $subjects = AcademicSubject::where('academy_id', $academyId)->orderBy('name')->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academyId)->orderBy('name')->get();

        return view('supervisor.interactive-courses.create', compact(
            'teachers', 'subjects', 'gradeLevels'
        ));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageInteractiveCourses()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'assigned_teacher_id' => 'required|exists:academic_teacher_profiles,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'grade_level_id' => 'required|exists:academic_grade_levels,id',
            'total_sessions' => 'required|integer|min:1|max:200',
            'sessions_per_week' => 'required|integer|min:1|max:7',
            'session_duration_minutes' => 'required|integer|in:15,30,45,60,90',
            'max_students' => 'required|integer|min:1|max:50',
            'difficulty_level' => 'nullable|string',
            'student_price' => 'required|numeric|min:0',
            'teacher_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string|in:fixed_amount,per_student,per_session',
            'start_date' => 'required|date',
            'enrollment_deadline' => 'nullable|date|before_or_equal:start_date',
            'is_published' => 'required|in:0,1',
            'recording_enabled' => 'required|in:0,1',
            'show_recording_to_teacher' => 'required|in:0,1',
            'show_recording_to_student' => 'required|in:0,1',
        ]);

        $validated['is_published'] = (bool) $validated['is_published'];
        $validated['recording_enabled'] = (bool) $validated['recording_enabled'];
        $validated['show_recording_to_teacher'] = (bool) $validated['show_recording_to_teacher'];
        $validated['show_recording_to_student'] = (bool) $validated['show_recording_to_student'];
        $validated['status'] = InteractiveCourseStatus::PUBLISHED;

        // Calculate duration_weeks
        $validated['duration_weeks'] = (int) ceil($validated['total_sessions'] / $validated['sessions_per_week']);

        $course = new InteractiveCourse($validated);
        $course->academy_id = $this->getAcademyId();
        $course->save();

        $subdomain = $subdomain ?? request()->route('subdomain');

        return redirect()
            ->route('manage.interactive-courses.show', ['subdomain' => $subdomain, 'course' => $course->id])
            ->with('success', __('supervisor.interactive_courses.course_created'));
    }

    public function update(Request $request, $subdomain, $courseId): RedirectResponse
    {
        if (! $this->canManageInteractiveCourses()) {
            abort(403);
        }

        $course = $this->findCourseInScope($courseId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'max_students' => 'required|integer|min:1|max:50',
            'total_sessions' => 'required|integer|min:1|max:200',
            'session_duration_minutes' => 'required|integer|in:15,30,45,60,90',
            'difficulty_level' => 'nullable|string',
            'student_price' => 'required|numeric|min:0',
            'teacher_payment' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string|in:fixed_amount,per_student,per_session',
            'recording_enabled' => 'required|in:0,1',
            'show_recording_to_teacher' => 'required|in:0,1',
            'show_recording_to_student' => 'required|in:0,1',
            'supervisor_notes' => 'nullable|string|max:2000',
        ]);

        $validated['recording_enabled'] = (bool) $validated['recording_enabled'];
        $validated['show_recording_to_teacher'] = (bool) $validated['show_recording_to_teacher'];
        $validated['show_recording_to_student'] = (bool) $validated['show_recording_to_student'];

        $course->update($validated);

        return redirect()->back()->with('success', __('supervisor.interactive_courses.course_updated'));
    }

    public function changeTeacher(Request $request, $subdomain, $courseId): RedirectResponse
    {
        if (! $this->canManageInteractiveCourses()) {
            abort(403);
        }

        $course = $this->findCourseInScope($courseId);

        $request->validate([
            'assigned_teacher_id' => 'required|integer|exists:academic_teacher_profiles,id',
        ]);

        // Verify the teacher is in scope
        $allowedProfileIds = $this->getAssignedAcademicTeacherProfileIds();
        if (! in_array((int) $request->assigned_teacher_id, $allowedProfileIds, true)) {
            abort(403);
        }

        $course->update(['assigned_teacher_id' => $request->assigned_teacher_id]);

        return redirect()->back()->with('success', __('supervisor.interactive_courses.teacher_changed'));
    }

    public function destroy($subdomain, $courseId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $course = $this->findCourseInScope($courseId);
        $course->delete();

        return redirect()
            ->route('manage.interactive-courses.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.interactive_courses.course_deleted'));
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

    /**
     * Scoped query: admins see all courses, supervisors see only their assigned teachers' courses.
     */
    private function scopedCourseQuery()
    {
        $query = InteractiveCourse::query();

        if (! $this->isAdminUser()) {
            $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();
            $responsibleCourseIds = $this->getResponsibleResourceIds(InteractiveCourse::class);
            $derivedCourseIds = $this->getDerivedInteractiveCourseIds();
            $allCourseIds = array_unique(array_merge($responsibleCourseIds, $derivedCourseIds));

            $query->where(function ($q) use ($academicTeacherProfileIds, $allCourseIds) {
                $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds)
                    ->orWhereIn('id', $allCourseIds);
            });
        }

        return $query;
    }

    private function findCourseInScope($courseId): InteractiveCourse
    {
        return $this->scopedCourseQuery()->findOrFail($courseId);
    }
}
