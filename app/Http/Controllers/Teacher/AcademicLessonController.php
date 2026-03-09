<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcademicLessonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show form to create a new academic lesson
     */
    public function create($subdomain): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, __('errors.teacher_profile_not_found'));
        }

        $lesson = null;
        $isEdit = false;

        // Get students scoped to academy
        $students = User::where('academy_id', $academy->id)
            ->where('user_type', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get subjects and grade levels scoped to academy
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('teacher.lessons.academic-lesson-form', compact(
            'lesson', 'isEdit', 'academy', 'students', 'subjects', 'gradeLevels', 'teacherProfile'
        ));
    }

    /**
     * Store a new academic lesson
     */
    public function store(Request $request, $subdomain): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, __('errors.teacher_profile_not_found'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'student_id' => 'required|exists:users,id',
            'academic_subject_id' => 'nullable|exists:academic_subjects,id',
            'academic_grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'total_sessions' => 'nullable|integer|min:1|max:200',
            'default_duration_minutes' => 'nullable|integer|min:15|max:180',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string|max:500',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        // Verify student belongs to academy
        $student = User::where('id', $validated['student_id'])
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        // Verify subject belongs to academy if provided
        if (! empty($validated['academic_subject_id'])) {
            AcademicSubject::where('id', $validated['academic_subject_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();
        }

        // Verify grade level belongs to academy if provided
        if (! empty($validated['academic_grade_level_id'])) {
            AcademicGradeLevel::where('id', $validated['academic_grade_level_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();
        }

        $lesson = AcademicIndividualLesson::create([
            'academy_id' => $academy->id,
            'academic_teacher_id' => $teacherProfile->id,
            'student_id' => $validated['student_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'academic_subject_id' => $validated['academic_subject_id'] ?? null,
            'academic_grade_level_id' => $validated['academic_grade_level_id'] ?? null,
            'total_sessions' => $validated['total_sessions'] ?? null,
            'default_duration_minutes' => $validated['default_duration_minutes'] ?? 60,
            'learning_objectives' => $validated['learning_objectives'] ?? null,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'status' => LessonStatus::ACTIVE,
            'sessions_scheduled' => 0,
            'sessions_completed' => 0,
            'sessions_remaining' => $validated['total_sessions'] ?? 0,
            'progress_percentage' => 0,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('teacher.academic.lessons.index', ['subdomain' => $academy->subdomain])
            ->with('success', __('teacher.lesson_form.created_success'));
    }

    /**
     * Show form to edit an existing academic lesson
     */
    public function edit($subdomain, $lessonId): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, __('errors.teacher_profile_not_found'));
        }

        $lesson = AcademicIndividualLesson::where('id', $lessonId)
            ->where('academic_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['student', 'academicSubject', 'academicGradeLevel'])
            ->firstOrFail();

        $isEdit = true;

        // Get students scoped to academy
        $students = User::where('academy_id', $academy->id)
            ->where('user_type', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get subjects and grade levels scoped to academy
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('teacher.lessons.academic-lesson-form', compact(
            'lesson', 'isEdit', 'academy', 'students', 'subjects', 'gradeLevels', 'teacherProfile'
        ));
    }

    /**
     * Update an existing academic lesson
     */
    public function update(Request $request, $subdomain, $lessonId): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, __('errors.teacher_profile_not_found'));
        }

        $lesson = AcademicIndividualLesson::where('id', $lessonId)
            ->where('academic_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'student_id' => 'required|exists:users,id',
            'academic_subject_id' => 'nullable|exists:academic_subjects,id',
            'academic_grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'total_sessions' => 'nullable|integer|min:1|max:200',
            'default_duration_minutes' => 'nullable|integer|min:15|max:180',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string|max:500',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        // Verify student belongs to academy
        User::where('id', $validated['student_id'])
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        // Verify subject belongs to academy if provided
        if (! empty($validated['academic_subject_id'])) {
            AcademicSubject::where('id', $validated['academic_subject_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();
        }

        // Verify grade level belongs to academy if provided
        if (! empty($validated['academic_grade_level_id'])) {
            AcademicGradeLevel::where('id', $validated['academic_grade_level_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();
        }

        $sessionsRemaining = $validated['total_sessions']
            ? max(0, $validated['total_sessions'] - ($lesson->sessions_completed ?? 0))
            : $lesson->sessions_remaining;

        $lesson->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'student_id' => $validated['student_id'],
            'academic_subject_id' => $validated['academic_subject_id'] ?? null,
            'academic_grade_level_id' => $validated['academic_grade_level_id'] ?? null,
            'total_sessions' => $validated['total_sessions'] ?? $lesson->total_sessions,
            'default_duration_minutes' => $validated['default_duration_minutes'] ?? $lesson->default_duration_minutes,
            'learning_objectives' => $validated['learning_objectives'] ?? $lesson->learning_objectives,
            'admin_notes' => $validated['admin_notes'] ?? $lesson->admin_notes,
            'sessions_remaining' => $sessionsRemaining,
            'updated_by' => $user->id,
        ]);

        return redirect()
            ->route('teacher.academic.lessons.index', ['subdomain' => $academy->subdomain])
            ->with('success', __('teacher.lesson_form.updated_success'));
    }
}
