<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Requests\AddStudentFeedbackRequest;
use App\Http\Requests\AssignAcademicHomeworkRequest;
use App\Http\Requests\CancelAcademicSessionRequest;
use App\Http\Requests\GradeAcademicHomeworkRequest;
use App\Http\Requests\RescheduleAcademicSessionRequest;
use App\Http\Requests\SubmitAcademicHomeworkRequest;
use App\Http\Requests\UpdateAcademicHomeworkRequest;
use App\Http\Requests\UpdateAcademicSessionEvaluationRequest;
use App\Http\Requests\UpdateAcademicSessionStatusRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Services\Attendance\AcademicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AcademicSessionController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display sessions for teacher
     */
    public function index(Request $request, $subdomain = null): View
    {
        $this->authorize('viewAny', AcademicSession::class);

        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            abort(404, 'ملف المعلم غير موجود');
        }

        $sessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->with(['student', 'academicIndividualLesson'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('session_type', $type);
            })
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return view('teacher.academic-sessions.index', compact('sessions'));
    }

    /**
     * Show session details
     */
    public function show($subdomain, $session): View
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize view access using policy
        $this->authorize('view', $session);

        // Determine view type based on user role
        $viewType = 'guest';

        if ($user->isAcademicTeacher()) {
            $teacherProfile = $user->academicTeacherProfile;
            if ($teacherProfile && (int) $session->academic_teacher_id === (int) $teacherProfile->id) {
                $viewType = 'teacher';
            }
        } elseif ($user->isStudent() && (int) $session->student_id === (int) $user->id) {
            $viewType = 'student';
        }

        $session->load([
            'student',
            'academicTeacher',
            'academicIndividualLesson.academicSubject',
            'academicIndividualLesson.academicGradeLevel',
            'studentReports',  // Needed for student-item component to show grades/reports
            'meetingAttendances',  // Needed for student-item component to show attendance
            'homeworkSubmissions',  // Needed for homework management
        ]);

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('teacher.academic-sessions.show', compact('session', 'viewType'));
    }

    /**
     * Add student feedback to session
     */
    public function addStudentFeedback(AddStudentFeedbackRequest $request, $subdomain, $session): JsonResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize view access (students need to view the session to provide feedback)
        $this->authorize('view', $session);

        // Update session with student feedback
        $session->update([
            'student_feedback' => $request->feedback,
        ]);

        return $this->success(null, 'تم إرسال تقييمك بنجاح');
    }

    /**
     * Assign homework to session (Teacher)
     */
    public function assignHomework(AssignAcademicHomeworkRequest $request, $subdomain, $session): JsonResponse|RedirectResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize update access (only teachers can assign homework)
        $this->authorize('update', $session);

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->academy_id}/academic-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $request->homework_description,
            'homework_file' => $homeworkFilePath,
        ]);

        // Create or update report for the student to track homework assignment
        $studentReport = AcademicSessionReport::firstOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
            ],
            [
                'teacher_id' => $session->academic_teacher_id,
                'academy_id' => $session->academy_id,
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'is_calculated' => true,
            ]
        );

        // Store homework description in report
        $studentReport->update([
            'homework_description' => $request->homework_description,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return $this->success(null, 'تم تعيين الواجب بنجاح');
        }

        return redirect()->back()->with('success', 'تم تعيين الواجب بنجاح');
    }

    /**
     * Update homework for academic session (Teacher)
     */
    public function updateHomework(UpdateAcademicHomeworkRequest $request, $subdomain, $session): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize update access (only teachers can update homework)
        $this->authorize('update', $session);

        // Handle file upload
        $homeworkFilePath = $session->homework_file; // Keep existing file if no new file uploaded
        if ($request->hasFile('homework_file')) {
            // Delete old file if exists
            if ($session->homework_file) {
                Storage::disk('public')->delete($session->homework_file);
            }

            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->academy_id}/academic-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $request->homework_description,
            'homework_file' => $homeworkFilePath,
        ]);

        // Update report if it exists
        $studentReport = AcademicSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport) {
            $studentReport->update([
                'homework_description' => $request->homework_description,
            ]);
        }

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return $this->success(null, 'تم تحديث الواجب بنجاح');
        }

        return redirect()->back()->with('success', 'تم تحديث الواجب بنجاح');
    }

    /**
     * Submit homework for academic session (Student)
     */
    public function submitHomework(SubmitAcademicHomeworkRequest $request, $subdomain, $session): RedirectResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize view access (students need to view the session to submit homework)
        $this->authorize('view', $session);

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->academy_id}/academic-homework/submissions",
                'public'
            );
        }

        // Get or create student report
        $studentReport = AcademicSessionReport::firstOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'teacher_id' => $session->academic_teacher_id,
                'academy_id' => $session->academy_id,
                'attendance_status' => AttendanceStatus::ABSENT->value,
                'is_calculated' => true,
            ]
        );

        // Save homework file path on report notes
        if ($homeworkFilePath) {
            $studentReport->update(['notes' => $homeworkFilePath]);
        }

        return redirect()->back()->with('success', 'تم تسليم الواجب بنجاح');
    }

    /**
     * Grade homework submission (Teacher)
     */
    public function gradeHomework(GradeAcademicHomeworkRequest $request, $subdomain, $session, $reportId): RedirectResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize update access (only teachers can grade homework)
        $this->authorize('update', $session);

        $report = AcademicSessionReport::findOrFail($reportId);

        // Verify report belongs to this session
        if ($report->session_id !== $session->id) {
            abort(404, 'التقرير غير موجود');
        }

        // Grade the homework
        $report->recordHomeworkGrade(
            grade: (float) $request->homework_grade,
            notes: $request->homework_feedback
        );

        return redirect()->back()->with('success', 'تم تقييم الواجب بنجاح');
    }

    /**
     * Update session evaluation (for teachers)
     */
    public function updateEvaluation(UpdateAcademicSessionEvaluationRequest $request, $subdomain, $session): JsonResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize update access (only teachers can update evaluation)
        $this->authorize('update', $session);

        $validated = $request->validated();

        // Handle file upload
        if ($request->hasFile('homework_file')) {
            $file = $request->file('homework_file');
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('academic-homework', $fileName, 'public');
            $validated['homework_file'] = $filePath;
        }

        $session->update($validated);

        return $this->success([
            'success' => true,
            'message' => 'تم حفظ تقييم الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Update session status
     */
    public function updateStatus(UpdateAcademicSessionStatusRequest $request, $subdomain, $session): JsonResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize update access (only teachers/admins can update session status)
        $this->authorize('update', $session);

        $validated = $request->validated();

        // Update timestamps based on status
        if ($validated['status'] === SessionStatus::ONGOING->value && ! $session->started_at) {
            $validated['started_at'] = now();
        } elseif ($validated['status'] === SessionStatus::COMPLETED->value && ! $session->ended_at) {
            $validated['ended_at'] = now();
            if ($session->started_at) {
                $validated['actual_duration_minutes'] = $session->started_at->diffInMinutes(now());
            }
        }

        $session->update($validated);

        return $this->success([
            'success' => true,
            'message' => 'تم تحديث حالة الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Reschedule session
     */
    public function reschedule(RescheduleAcademicSessionRequest $request, $subdomain, $session): JsonResponse
    {
        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize reschedule access using policy
        $this->authorize('reschedule', $session);

        $validated = $request->validated();

        $session->update([
            'rescheduled_from' => $session->scheduled_at,
            'rescheduled_to' => $validated['new_scheduled_at'],
            'scheduled_at' => $validated['new_scheduled_at'],
            'reschedule_reason' => $validated['reschedule_reason'],
            'status' => SessionStatus::SCHEDULED,
        ]);

        return $this->success([
            'success' => true,
            'message' => 'تم إعادة جدولة الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Cancel session
     */
    public function cancel(CancelAcademicSessionRequest $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (! $session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Authorize cancel access using policy
        $this->authorize('cancel', $session);

        $validated = $request->validated();

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);

        return $this->success([
            'success' => true,
            'message' => 'تم إلغاء الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Show comprehensive report for academic subscription (teacher view)
     */
    public function subscriptionReport($subdomain, $subscription): View
    {
        $user = Auth::user();

        // If $subscription is not a model instance, fetch it
        if (! $subscription instanceof AcademicSubscription) {
            $subscription = AcademicSubscription::findOrFail($subscription);
        }

        // Verify teacher has access to the subscription
        // Check through first session if available, otherwise check teacher ownership
        $firstSession = $subscription->sessions()->first();
        if ($firstSession) {
            $this->authorize('view', $firstSession);
        } else {
            // If no sessions exist, verify subscription belongs to this teacher
            $this->authorize('view', $subscription);
        }

        // Load subscription with relationships
        $subscription->load([
            'student',
            'teacher',
            'subject',
            'gradeLevel',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ]);

        $student = $subscription->student;
        $subject = $subscription->subject;

        // Get report service
        $reportService = app(AcademicReportService::class);

        // Calculate report data
        $performance = $reportService->calculatePerformance($subscription);
        $attendance = $reportService->calculateAttendance($subscription);
        $progress = $reportService->calculateProgress($subscription);

        return view('reports.academic.subscription-report-teacher', [
            'subscription' => $subscription,
            'student' => $student->user ?? $student,
            'subject' => $subject,
            'performance' => $performance,
            'attendance' => $attendance,
            'progress' => $progress,
        ]);
    }

    /**
     * Show comprehensive report for academic subscription (student view)
     */
    public function studentSubscriptionReport($subdomain, $subscription): View
    {
        $user = Auth::user();

        // If $subscription is not a model instance, fetch it
        if (! $subscription instanceof AcademicSubscription) {
            $subscription = AcademicSubscription::findOrFail($subscription);
        }

        // Verify student has access to the subscription
        // Check through first session if available, otherwise check student ownership
        $firstSession = $subscription->sessions()->first();
        if ($firstSession) {
            $this->authorize('view', $firstSession);
        } else {
            // If no sessions exist, verify subscription belongs to this student
            $this->authorize('view', $subscription);
        }

        // Load subscription with relationships
        $subscription->load([
            'student',
            'teacher.user',
            'subject',
            'gradeLevel',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ]);

        $student = $subscription->student;
        $subject = $subscription->subject;
        $teacher = $subscription->teacher;

        // Get report service
        $reportService = app(AcademicReportService::class);

        // Calculate report data
        $performance = $reportService->calculatePerformance($subscription);
        $attendance = $reportService->calculateAttendance($subscription);
        $progress = $reportService->calculateProgress($subscription);

        return view('reports.academic.subscription-report-student', compact(
            'subscription',
            'student',
            'subject',
            'teacher',
            'performance',
            'attendance',
            'progress'
        ));
    }
}
