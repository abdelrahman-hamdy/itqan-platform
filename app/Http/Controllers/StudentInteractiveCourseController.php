<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\InteractiveCourseSession;
use App\Models\User;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseHomework;
use App\Enums\HomeworkSubmissionStatus;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Http\Requests\AddInteractiveSessionFeedbackRequest;
use App\Http\Requests\AssignInteractiveSessionHomeworkRequest;
use App\Http\Requests\SubmitInteractiveCourseHomeworkRequest;
use App\Http\Requests\UpdateInteractiveSessionContentRequest;
use App\Http\Requests\UpdateInteractiveSessionHomeworkRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\InteractiveCourse;
use App\Services\AcademyContextService;
use App\Services\Attendance\InteractiveReportService;
use App\Services\HomeworkService;
use App\Services\Reports\InteractiveCourseReportService;
use App\Services\Student\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentInteractiveCourseController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected StudentCourseService $courseService,
        protected InteractiveCourseReportService $interactiveReportService,
        protected InteractiveReportService $attendanceReportService,
        protected HomeworkService $homeworkService
    ) {}

    public function interactiveCourses(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        // Ensure user has a student profile
        if (! $user->studentProfile) {
            return redirect()->route('student.profile')
                ->with('error', 'يجب إكمال الملف الشخصي للطالب أولاً');
        }

        // Get interactive courses using service
        $courses = $this->courseService->getInteractiveCourses($user, $request, 12);

        // Get enrolled courses count using service
        $enrolledCoursesCount = $this->courseService->getEnrolledCoursesCount($user);

        // Get filter options using service
        $filterOptions = $this->courseService->getCourseFilterOptions($user);

        return view('student.interactive-courses', [
            'courses' => $courses,
            'enrolledCoursesCount' => $enrolledCoursesCount,
            'subjects' => $filterOptions['subjects'],
            'gradeLevels' => $filterOptions['gradeLevels'],
        ]);
    }

    public function showInteractiveCourse($subdomain, $course): View
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Get academy from subdomain parameter
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [Academy::class, $academy]);
        }

        // Determine user type
        $userType = $user->user_type;
        $isTeacher = $userType === UserType::ACADEMIC_TEACHER->value;
        $isStudent = $userType === UserType::STUDENT->value;

        // For students: Get course details using service
        if ($isStudent) {
            $courseData = $this->courseService->getInteractiveCourseDetails($user, $course);

            if (! $courseData) {
                abort(404, 'Course not found');
            }

            // Authorize viewing the course
            $this->authorize('view', $courseData['course']);

            return view('student.interactive-course-detail', [
                'course' => $courseData['course'],
                'isEnrolled' => $courseData['isEnrolled'],
                'enrollmentStats' => $courseData['enrollmentStats'],
                'teacherData' => [],
                'userType' => $userType,
                'isTeacher' => false,
                'isStudent' => true,
                'upcomingSessions' => $courseData['upcomingSessions'],
                'pastSessions' => $courseData['pastSessions'],
                'student' => $user->studentProfile,
            ]);
        }

        // For teachers: Load course with teacher-specific logic (kept inline as teacher logic is different)
        $courseModel = InteractiveCourse::where('id', $course)
            ->where('academy_id', $academy->id)
            ->with([
                'assignedTeacher.user',
                'subject',
                'gradeLevel',
                'enrollments.student.user',
                'sessions' => function ($query) {
                    $query->orderBy('scheduled_at');
                },
            ])
            ->firstOrFail();

        // Authorize viewing the course (includes assigned teacher check)
        $this->authorize('view', $courseModel);

        // Teacher data
        $teacherData = [
            'total_students' => $courseModel->enrollments->count(),
            'total_sessions' => $courseModel->sessions->count(),
            'completed_sessions' => $courseModel->sessions->where('status', SessionStatus::COMPLETED->value)->count(),
            'upcoming_sessions' => $courseModel->sessions->where('session_date', '>', now())->count(),
        ];

        // Separate sessions
        $now = now();
        $upcomingSessions = $courseModel->sessions->filter(function ($session) use ($now) {
            $scheduledDateTime = $session->scheduled_at;
            $statusValue = $session->status->value ?? $session->status;

            return $scheduledDateTime && ($scheduledDateTime->gte($now) || $statusValue === SessionStatus::ONGOING->value);
        })->values();

        $pastSessions = $courseModel->sessions->filter(function ($session) use ($now) {
            $scheduledDateTime = $session->scheduled_at;
            $statusValue = $session->status->value ?? $session->status;

            return $scheduledDateTime && $scheduledDateTime->lt($now) && $statusValue !== SessionStatus::ONGOING->value;
        })->sortByDesc(function ($session) {
            return $session->scheduled_at ? $session->scheduled_at->timestamp : 0;
        })->values();

        return view('teacher.interactive-course-detail', [
            'course' => $courseModel,
            'isEnrolled' => false,
            'enrollmentStats' => [],
            'teacherData' => $teacherData,
            'userType' => $userType,
            'isTeacher' => true,
            'isStudent' => false,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'student' => null,
        ]);
    }

    public function showInteractiveCourseSession($subdomain, $sessionId): View
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Get academy from subdomain parameter
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [Academy::class, $academy]);
        }

        $isTeacher = $user->isAcademicTeacher();
        $isStudent = $user->isStudent();

        // For students: Use service to get session details
        if ($isStudent) {
            $sessionData = $this->courseService->getInteractiveCourseSessionDetails($user, $sessionId);

            if (! $sessionData) {
                abort(404, 'Session not found');
            }

            // Authorize viewing the session
            $this->authorize('view', $sessionData['session']);

            return view('student.interactive-course-sessions.show', [
                'session' => $sessionData['session'],
                'attendance' => $sessionData['attendance'],
                'homeworkSubmission' => $sessionData['homeworkSubmission'],
                'student' => $sessionData['student'],
                'enrollment' => $sessionData['enrollment'],
                'viewType' => 'student',
            ]);
        }

        // For teachers: Keep inline logic (teacher-specific access control)
        $session = InteractiveCourseSession::with([
            'course.assignedTeacher.user',
            'course.subject',
            'course.gradeLevel',
            'course.enrolledStudents.student.user',
            'homework',
            'attendances',
            'meetingAttendances',
        ])->findOrFail($sessionId);

        // Authorize viewing the session (includes all necessary checks)
        $this->authorize('view', $session);

        return view('teacher.interactive-course-sessions.show', [
            'session' => $session,
            'viewType' => 'teacher',
        ]);
    }

    /**
     * Display comprehensive report for an interactive course (Teacher view)
     */
    public function interactiveCourseReport($subdomain, $courseId): View
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Verify user is an academic teacher
        if (! $user->isAcademicTeacher()) {
            $this->authorize('create', InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [Academy::class, $academy]);
        }

        // Load course with relationships
        $course = InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'enrollments.student.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Authorize viewing the course (includes assigned teacher check)
        $this->authorize('view', $course);

        // Get report service
        $reportService = $this->interactiveReportService;

        // Generate comprehensive report using DTOs
        $reportData = $reportService->getCourseOverviewReport($course);

        return view('reports.interactive-course.course-overview', $reportData);
    }

    /**
     * Display individual student report for an interactive course (Teacher view)
     */
    public function interactiveCourseStudentReport($subdomain, $courseId, $studentId): View
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Verify user is an academic teacher, supervisor, or admin
        $isSupervisorOrAdmin = $user->isSuperAdmin() || $user->isAdmin() || $user->isAcademyAdmin() || $user->isSupervisor();
        if (! $user->isAcademicTeacher() && ! $isSupervisorOrAdmin) {
            $this->authorize('create', InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [Academy::class, $academy]);
        }

        // Load course with relationships
        $course = InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'enrollments.student.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Authorize viewing the course (supervisors/admins can view all courses)
        if (! $isSupervisorOrAdmin) {
            $this->authorize('view', $course);
        }

        // Get student
        $student = User::findOrFail($studentId);

        // Verify student is enrolled in this course
        $enrollment = $course->enrollments->first(function ($e) use ($student) {
            return $e->student?->user?->id === $student->id || $e->student_id === $student->id;
        });

        if (! $enrollment) {
            abort(404, 'الطالب غير مسجل في هذا الكورس');
        }

        // Get report service
        $reportService = $this->attendanceReportService;

        // Calculate metrics for this specific student
        $performance = $reportService->calculatePerformance($course, $student->id);
        $attendance = $reportService->calculateAttendance($course, $student->id);
        $progress = $reportService->calculateProgress($course);

        // Add homework metrics for this student
        $studentReports = $course->sessions->flatMap(function ($session) use ($student) {
            return $session->studentReports->where('student_id', $student->id);
        });

        $homeworkAssigned = $course->sessions->filter(function ($session) {
            return ! empty($session->homework_description);
        })->count();

        $homeworkSubmitted = $studentReports->whereNotNull('homework_submitted_at')->count();
        $homeworkCompletionRate = $homeworkAssigned > 0 ? round(($homeworkSubmitted / $homeworkAssigned) * 100) : 0;

        $progress['homework_assigned'] = $homeworkAssigned;
        $progress['homework_submitted'] = $homeworkSubmitted;
        $progress['homework_completion_rate'] = $homeworkCompletionRate;

        $layoutType = str_starts_with(request()->route()->getName(), 'manage.') ? 'supervisor' : 'teacher';

        return view('reports.interactive-course.teacher-student-report', [
            'course' => $course,
            'student' => $student,
            'enrollment' => $enrollment,
            'performance' => $performance,
            'attendance' => $attendance,
            'progress' => $progress,
            'layoutType' => $layoutType,
        ]);
    }

    /**
     * Display comprehensive report for an interactive course (Student view)
     */
    public function studentInteractiveCourseReport($subdomain, $courseId): View
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Verify user is a student
        if (! $user->isStudent()) {
            $this->authorize('viewAny', InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [Academy::class, $academy]);
        }

        // Load course with relationships
        $course = InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Authorize viewing the course (includes enrollment check for students)
        $this->authorize('view', $course);

        // Verify student is enrolled in this course
        $studentProfile = $user->studentProfile;
        if (! $studentProfile) {
            abort(404, 'Student profile not found');
        }

        $enrollment = InteractiveCourseEnrollment::where([
            'course_id' => $course->id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => EnrollmentStatus::ENROLLED,
        ])->first();

        if (! $enrollment) {
            abort(404, 'Enrollment not found');
        }

        // Get report service
        $reportService = $this->interactiveReportService;

        // Generate student report using DTOs
        $reportData = $reportService->getStudentReport($course, $studentProfile);

        return view('reports.interactive-course.student-report', $reportData);
    }

    public function addInteractiveSessionFeedback(AddInteractiveSessionFeedbackRequest $request, $subdomain, $sessionId): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment and session completion
        $studentProfile = $user->studentProfile;
        if (! $studentProfile) {
            return $this->forbidden('Student profile not found');
        }

        $enrollment = InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => EnrollmentStatus::ENROLLED,
        ])->firstOrFail();

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue !== SessionStatus::COMPLETED->value) {
            return $this->error('لا يمكن إضافة تقييم لجلسة لم تكتمل', 400);
        }

        // Update session with student feedback
        $session->update([
            'student_feedback' => $validated['feedback'],
        ]);

        return $this->success(null, 'تم إرسال تقييمك بنجاح');
    }

    public function updateInteractiveSessionContent(UpdateInteractiveSessionContentRequest $request, $subdomain, $sessionId): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = InteractiveCourseSession::findOrFail($sessionId);

        // Verify teacher is assigned to this course
        if (! $user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بالوصول');
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || $session->course->assigned_teacher_id !== $teacherProfile->id) {
            return $this->forbidden('غير مسموح لك بتعديل هذه الجلسة');
        }

        // Update session
        $session->update($validated);

        return $this->success(
            ['session' => $session->fresh()],
            'تم حفظ المحتوى بنجاح'
        );
    }

    public function assignInteractiveSessionHomework(AssignInteractiveSessionHomeworkRequest $request, $subdomain, $sessionId): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = InteractiveCourseSession::findOrFail($sessionId);

        // Authorize updating the session (includes teacher check)
        $this->authorize('update', $session);

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->course->academy_id}/interactive-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $validated['homework_description'],
            'homework_file' => $homeworkFilePath,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return $this->success(null, 'تم تعيين الواجب بنجاح');
        }

        return redirect()->back()->with('success', 'تم تعيين الواجب بنجاح');
    }

    /**
     * Update homework for interactive session (Teacher)
     */
    public function updateInteractiveSessionHomework(UpdateInteractiveSessionHomeworkRequest $request, $subdomain, $sessionId): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = InteractiveCourseSession::findOrFail($sessionId);

        // Authorize updating the session (includes teacher check)
        $this->authorize('update', $session);

        // Handle file upload
        $homeworkFilePath = $session->homework_file; // Keep existing file if no new file uploaded
        if ($request->hasFile('homework_file')) {
            // Delete old file if exists
            if ($session->homework_file) {
                Storage::disk('public')->delete($session->homework_file);
            }

            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->course->academy_id}/interactive-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $validated['homework_description'],
            'homework_file' => $homeworkFilePath,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return $this->success(null, 'تم تحديث الواجب بنجاح');
        }

        return redirect()->back()->with('success', 'تم تحديث الواجب بنجاح');
    }

    public function submitInteractiveCourseHomework(SubmitInteractiveCourseHomeworkRequest $request, $subdomain, $sessionId): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment
        $enrollment = InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $user->id,
            'status' => 'active',
        ])->firstOrFail();

        $homework = InteractiveCourseHomework::findOrFail($validated['homework_id']);
        $student = $user->studentProfile;

        // Use existing HomeworkService if available
        if (class_exists(HomeworkService::class)) {
            $this->homeworkService->submitHomework(
                $homework,
                $student,
                $validated
            );
        } else {
            // Fallback: Use model directly with correct column names
            $submissionData = [
                'academy_id' => $homework->academy_id,
                'interactive_course_homework_id' => $homework->id,
                'interactive_course_session_id' => $homework->interactive_course_session_id,
                'student_id' => $student->id,
                'submission_text' => $validated['answer_text'] ?? null,
                'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
                'submitted_at' => now(),
                'max_score' => $homework->max_score ?? 10,
            ];

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $files = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('homework-submissions', 'public');
                    $files[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
                $submissionData['submission_files'] = $files;
            }

            InteractiveCourseHomeworkSubmission::updateOrCreate(
                [
                    'interactive_course_homework_id' => $homework->id,
                    'student_id' => $student->id,
                ],
                $submissionData
            );
        }

        return back()->with('success', 'Homework submitted successfully');
    }

    /**
     * Show form to create a new interactive course session
     */
    public function createSession($subdomain): View
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

        $session = null;
        $isEdit = false;

        // Get teacher's interactive courses
        $courses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->orderBy('title')
            ->get(['id', 'title', 'session_duration_minutes', 'total_sessions']);

        return view('teacher.sessions.interactive-form', compact(
            'session', 'isEdit', 'academy', 'courses', 'teacherProfile'
        ));
    }

    /**
     * Store a new interactive course session
     */
    public function storeSession(Request $request, $subdomain): RedirectResponse
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
            'course_id' => 'required|exists:interactive_courses,id',
            'session_number' => 'nullable|integer|min:1',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lesson_content' => 'nullable|string|max:5000',
            'homework_assigned' => 'nullable|boolean',
            'homework_description' => 'nullable|string|max:2000',
            'homework_file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        // Verify course belongs to this teacher
        $course = InteractiveCourse::where('id', $validated['course_id'])
            ->where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        // Handle homework file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$academy->id}/interactive-homework",
                'public'
            );
        }

        // Convert scheduled_at from academy timezone to UTC
        $scheduledAt = Carbon::parse($validated['scheduled_at'], AcademyContextService::getTimezone());

        // Auto-calculate session number if not provided
        $sessionNumber = $validated['session_number'] ?? (
            InteractiveCourseSession::where('course_id', $course->id)->max('session_number') + 1
        );

        $session = InteractiveCourseSession::create([
            'academy_id' => $academy->id,
            'course_id' => $course->id,
            'session_number' => $sessionNumber,
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => AcademyContextService::toUtcForStorage($scheduledAt),
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'lesson_content' => $validated['lesson_content'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'homework_assigned' => $validated['homework_assigned'] ?? false,
            'homework_description' => $validated['homework_description'] ?? null,
            'homework_file' => $homeworkFilePath,
            'meeting_auto_generated' => true,
        ]);

        return redirect()
            ->route('teacher.interactive-sessions.show', ['subdomain' => $academy->subdomain, 'session' => $session->id])
            ->with('success', __('teacher.session_form.created_success'));
    }

    /**
     * Show form to edit an interactive course session
     */
    public function editSession($subdomain, $sessionId): View
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

        $session = InteractiveCourseSession::where('id', $sessionId)
            ->whereHas('course', function ($query) use ($teacherProfile, $academy) {
                $query->where('assigned_teacher_id', $teacherProfile->id)
                    ->where('academy_id', $academy->id);
            })
            ->with('course')
            ->firstOrFail();

        // Only SCHEDULED sessions can be edited
        if ($session->status !== SessionStatus::SCHEDULED) {
            return redirect()
                ->route('teacher.interactive-sessions.show', ['subdomain' => $academy->subdomain, 'session' => $session->id])
                ->with('error', __('teacher.session_form.only_scheduled_editable'));
        }

        $isEdit = true;

        // Get teacher's interactive courses
        $courses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->orderBy('title')
            ->get(['id', 'title', 'session_duration_minutes', 'total_sessions']);

        return view('teacher.sessions.interactive-form', compact(
            'session', 'isEdit', 'academy', 'courses', 'teacherProfile'
        ));
    }

    /**
     * Update an interactive course session
     */
    public function updateSession(Request $request, $subdomain, $sessionId): RedirectResponse
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

        $session = InteractiveCourseSession::where('id', $sessionId)
            ->whereHas('course', function ($query) use ($teacherProfile, $academy) {
                $query->where('assigned_teacher_id', $teacherProfile->id)
                    ->where('academy_id', $academy->id);
            })
            ->firstOrFail();

        // Only SCHEDULED sessions can be edited
        if ($session->status !== SessionStatus::SCHEDULED) {
            return redirect()
                ->route('teacher.interactive-sessions.show', ['subdomain' => $academy->subdomain, 'session' => $session->id])
                ->with('error', __('teacher.session_form.only_scheduled_editable'));
        }

        $validated = $request->validate([
            'session_number' => 'nullable|integer|min:1',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lesson_content' => 'nullable|string|max:5000',
            'homework_assigned' => 'nullable|boolean',
            'homework_description' => 'nullable|string|max:2000',
            'homework_file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        // Handle homework file upload
        $homeworkFilePath = $session->homework_file;
        if ($request->hasFile('homework_file')) {
            if ($session->homework_file) {
                Storage::disk('public')->delete($session->homework_file);
            }
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$academy->id}/interactive-homework",
                'public'
            );
        }

        // Convert scheduled_at from academy timezone to UTC
        $scheduledAt = Carbon::parse($validated['scheduled_at'], AcademyContextService::getTimezone());

        $session->update([
            'session_number' => $validated['session_number'] ?? $session->session_number,
            'scheduled_at' => AcademyContextService::toUtcForStorage($scheduledAt),
            'title' => $validated['title'] ?? $session->title,
            'description' => $validated['description'] ?? $session->description,
            'lesson_content' => $validated['lesson_content'] ?? $session->lesson_content,
            'duration_minutes' => $validated['duration_minutes'],
            'homework_assigned' => $validated['homework_assigned'] ?? false,
            'homework_description' => $validated['homework_description'] ?? $session->homework_description,
            'homework_file' => $homeworkFilePath,
        ]);

        return redirect()
            ->route('teacher.interactive-sessions.show', ['subdomain' => $academy->subdomain, 'session' => $session->id])
            ->with('success', __('teacher.session_form.updated_success'));
    }
}
