<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\InteractiveCourse;
use App\Services\Attendance\InteractiveReportService;
use App\Services\HomeworkService;
use App\Services\Reports\InteractiveCourseReportService;
use App\Services\Student\StudentCourseService;
use App\Http\Requests\AddInteractiveSessionFeedbackRequest;
use App\Http\Requests\UpdateInteractiveSessionContentRequest;
use App\Http\Requests\AssignInteractiveSessionHomeworkRequest;
use App\Http\Requests\UpdateInteractiveSessionHomeworkRequest;
use App\Http\Requests\SubmitInteractiveCourseHomeworkRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Enums\SessionStatus;

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
        if (!$user->studentProfile) {
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
            abort(401, 'User not authenticated');
        }

        // Get academy from subdomain parameter
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
        }

        // Determine user type
        $userType = $user->user_type;
        $isTeacher = $userType === 'academic_teacher';
        $isStudent = $userType === 'student';

        // For students: Get course details using service
        if ($isStudent) {
            $courseData = $this->courseService->getInteractiveCourseDetails($user, $course);

            if (!$courseData) {
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

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Get academy from subdomain parameter
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
        }

        $isTeacher = $user->isAcademicTeacher();
        $isStudent = $user->isStudent();

        // For students: Use service to get session details
        if ($isStudent) {
            $sessionData = $this->courseService->getInteractiveCourseSessionDetails($user, $sessionId);

            if (!$sessionData) {
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
        $session = \App\Models\InteractiveCourseSession::with([
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

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is an academic teacher
        if (!$user->isAcademicTeacher()) {
            $this->authorize('create', \App\Models\InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
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

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is an academic teacher
        if (!$user->isAcademicTeacher()) {
            $this->authorize('create', \App\Models\InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
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

        // Get student
        $student = \App\Models\User::findOrFail($studentId);

        // Verify student is enrolled in this course
        $enrollment = $course->enrollments->first(function ($e) use ($student) {
            return $e->student?->user?->id === $student->id || $e->student_id === $student->id;
        });

        if (!$enrollment) {
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
            return !empty($session->homework_description);
        })->count();

        $homeworkSubmitted = $studentReports->whereNotNull('homework_submitted_at')->count();
        $homeworkCompletionRate = $homeworkAssigned > 0 ? round(($homeworkSubmitted / $homeworkAssigned) * 100) : 0;

        $progress['homework_assigned'] = $homeworkAssigned;
        $progress['homework_submitted'] = $homeworkSubmitted;
        $progress['homework_completion_rate'] = $homeworkCompletionRate;

        return view('reports.interactive-course.teacher-student-report', [
            'course' => $course,
            'student' => $student,
            'enrollment' => $enrollment,
            'performance' => $performance,
            'attendance' => $attendance,
            'progress' => $progress,
        ]);
    }

    /**
     * Display comprehensive report for an interactive course (Student view)
     */
    public function studentInteractiveCourseReport($subdomain, $courseId): View
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is a student
        if (!$user->isStudent()) {
            $this->authorize('viewAny', \App\Models\InteractiveCourse::class);
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
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
        if (!$studentProfile) {
            abort(404, 'Student profile not found');
        }

        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $course->id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => 'enrolled'
        ])->first();

        if (!$enrollment) {
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
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment and session completion
        $studentProfile = $user->studentProfile;
        if (!$studentProfile) {
            return $this->forbidden('Student profile not found');
        }

        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => 'enrolled'
        ])->firstOrFail();

        $statusValue = $session->status->value ?? $session->status;
        if ($statusValue !== SessionStatus::COMPLETED->value) {
            return $this->error('لا يمكن إضافة تقييم لجلسة لم تكتمل', 400);
        }

        // Update session with student feedback
        $session->update([
            'student_feedback' => $validated['feedback']
        ]);

        return $this->success(null, 'تم إرسال تقييمك بنجاح');
    }

    public function updateInteractiveSessionContent(UpdateInteractiveSessionContentRequest $request, $subdomain, $sessionId): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify teacher is assigned to this course
        if (!$user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بالوصول');
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $session->course->assigned_teacher_id !== $teacherProfile->id) {
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
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

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
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

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
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment
        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $user->id,
            'status' => 'active'
        ])->firstOrFail();

        $homework = \App\Models\InteractiveCourseHomework::findOrFail($validated['homework_id']);
        $student = $user->studentProfile;

        // Use existing HomeworkService if available
        if (class_exists(\App\Services\HomeworkService::class)) {
            $this->homeworkService->submitHomework(
                $homework,
                $student,
                $validated
            );
        } else {
            // Fallback: Direct submission creation
            $submissionData = [
                'homework_id' => $homework->id,
                'student_id' => $student->id,
                'answer_text' => $validated['answer_text'] ?? null,
                'status' => 'pending',
                'submitted_at' => now()
            ];

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $files = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('homework-submissions', 'public');
                    $files[] = $path;
                }
                $submissionData['files'] = json_encode($files);
            }

            \DB::table('interactive_course_homework_submissions')->updateOrInsert(
                [
                    'homework_id' => $homework->id,
                    'student_id' => $student->id
                ],
                $submissionData
            );
        }

        return back()->with('success', 'Homework submitted successfully');
    }
}
