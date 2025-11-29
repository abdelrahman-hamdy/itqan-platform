<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Services\Attendance\AcademicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AcademicSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display sessions for teacher
     */
    public function index(Request $request, $subdomain = null)
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

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
    public function show($subdomain, $session)
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Check permissions
        $canAccess = false;
        $viewType = 'guest';

        if ($user->isAcademicTeacher()) {
            $teacherProfile = $user->academicTeacherProfile;
            if ($teacherProfile && (int) $session->academic_teacher_id === (int) $teacherProfile->id) {
                $canAccess = true;
                $viewType = 'teacher';
            }
        } elseif ($user->isStudent() && (int) $session->student_id === (int) $user->id) {
            $canAccess = true;
            $viewType = 'student';
        }

        if (! $canAccess) {
            abort(403, 'غير مسموح لك بالوصول لهذه الجلسة');
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
    public function addStudentFeedback(Request $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Check if user is the student for this session
        if (! $user->isStudent() || (int) $session->student_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح لك بإضافة تقييم لهذه الجلسة'], 403);
        }

        // Validate request
        $request->validate([
            'feedback' => 'required|string|max:1000',
        ]);

        // Update session with student feedback
        $session->update([
            'student_feedback' => $request->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تقييمك بنجاح',
        ]);
    }

    /**
     * Assign homework to session (Teacher)
     */
    public function assignHomework(\App\Http\Requests\AssignAcademicHomeworkRequest $request, $subdomain, $session)
    {
        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

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
        $studentReport = \App\Models\AcademicSessionReport::firstOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
            ],
            [
                'teacher_id' => $session->academic_teacher_id,
                'academy_id' => $session->academy_id,
                'attendance_status' => 'absent',
                'is_calculated' => true,
            ]
        );

        // Store homework description in report
        $studentReport->update([
            'homework_description' => $request->homework_description,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم تعيين الواجب بنجاح']);
        }

        return redirect()->back()->with('success', 'تم تعيين الواجب بنجاح');
    }

    /**
     * Update homework for academic session (Teacher)
     */
    public function updateHomework(Request $request, $subdomain, $session)
    {
        $validated = $request->validate([
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240'
        ]);

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

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
        $studentReport = \App\Models\AcademicSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport) {
            $studentReport->update([
                'homework_description' => $request->homework_description,
            ]);
        }

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم تحديث الواجب بنجاح']);
        }

        return redirect()->back()->with('success', 'تم تحديث الواجب بنجاح');
    }

    /**
     * Submit homework for academic session (Student)
     */
    public function submitHomework(\App\Http\Requests\SubmitAcademicHomeworkRequest $request, $subdomain, $session)
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->academy_id}/academic-homework/submissions",
                'public'
            );
        }

        // Get or create student report
        $studentReport = \App\Models\AcademicSessionReport::firstOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'teacher_id' => $session->academic_teacher_id,
                'academy_id' => $session->academy_id,
                'attendance_status' => 'absent',
                'is_calculated' => true,
            ]
        );

        // Submit homework
        $studentReport->submitHomework($homeworkFilePath);

        return redirect()->back()->with('success', 'تم تسليم الواجب بنجاح');
    }

    /**
     * Grade homework submission (Teacher)
     */
    public function gradeHomework(\App\Http\Requests\GradeAcademicHomeworkRequest $request, $subdomain, $session, $reportId)
    {
        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }
        $report = \App\Models\AcademicSessionReport::findOrFail($reportId);

        // Verify report belongs to this session
        if ($report->session_id !== $session->id) {
            abort(404, 'التقرير غير موجود');
        }

        // Grade the homework
        $report->recordHomeworkFeedback(
            grade: (float) $request->homework_grade,
            feedback: $request->homework_feedback
        );

        return redirect()->back()->with('success', 'تم تقييم الواجب بنجاح');
    }

    /**
     * Update session evaluation (for teachers)
     */
    public function updateEvaluation(Request $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Only teachers can update evaluation
        if (! $user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || (int) $session->academic_teacher_id !== (int) $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بتقييم هذه الجلسة'], 403);
        }

        $validated = $request->validate([
            'session_topics_covered' => 'nullable|string|max:1000',
            'lesson_content' => 'nullable|string|max:2000',
            'homework_description' => 'nullable|string|max:1000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120', // 5MB max
            'session_notes' => 'nullable|string|max:1000',
            'teacher_feedback' => 'nullable|string|max:1000',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'technical_issues' => 'nullable|string|max:500',
            'follow_up_required' => 'boolean',
            'follow_up_notes' => 'nullable|string|max:500',
        ]);

        // Handle file upload
        if ($request->hasFile('homework_file')) {
            $file = $request->file('homework_file');
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('academic-homework', $fileName, 'public');
            $validated['homework_file'] = $filePath;
        }

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تقييم الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Update session status
     */
    public function updateStatus(Request $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Only teachers can update status
        if (! $user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || (int) $session->academic_teacher_id !== (int) $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بتعديل هذه الجلسة'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:scheduled,ongoing,completed,cancelled,rescheduled',
            'attendance_status' => 'nullable|in:scheduled,attended,absent,late,leaved',
            'attendance_notes' => 'nullable|string|max:500',
        ]);

        // Update timestamps based on status
        if ($validated['status'] === 'ongoing' && ! $session->started_at) {
            $validated['started_at'] = now();
        } elseif ($validated['status'] === 'completed' && ! $session->ended_at) {
            $validated['ended_at'] = now();
            if ($session->started_at) {
                $validated['actual_duration_minutes'] = $session->started_at->diffInMinutes(now());
            }
        }

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Reschedule session
     */
    public function reschedule(Request $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Only teachers can reschedule
        if (! $user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || (int) $session->academic_teacher_id !== (int) $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بإعادة جدولة هذه الجلسة'], 403);
        }

        $validated = $request->validate([
            'new_scheduled_at' => 'required|date|after:now',
            'reschedule_reason' => 'required|string|max:500',
        ]);

        $session->update([
            'rescheduled_from' => $session->scheduled_at,
            'rescheduled_to' => $validated['new_scheduled_at'],
            'scheduled_at' => $validated['new_scheduled_at'],
            'reschedule_reason' => $validated['reschedule_reason'],
            'status' => 'rescheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة جدولة الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Cancel session
     */
    public function cancel(Request $request, $subdomain, $session): JsonResponse
    {
        $user = Auth::user();

        // If $session is not a model instance, fetch it
        if (!$session instanceof AcademicSession) {
            $session = AcademicSession::findOrFail($session);
        }

        // Only teachers can cancel
        if (! $user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || (int) $session->academic_teacher_id !== (int) $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بإلغاء هذه الجلسة'], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Show comprehensive report for academic subscription (teacher view)
     */
    public function subscriptionReport($subdomain, $subscription)
    {
        $user = Auth::user();

        // If $subscription is not a model instance, fetch it
        if (!$subscription instanceof AcademicSubscription) {
            $subscription = AcademicSubscription::findOrFail($subscription);
        }

        // Check permissions - only the teacher who owns this subscription can view
        if (!$user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || (int) $subscription->teacher_id !== (int) $teacherProfile->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
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

        return view('teacher.circle-report', [
            'reportType' => 'academic',
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
    public function studentSubscriptionReport($subdomain, $subscription)
    {
        $user = Auth::user();

        // If $subscription is not a model instance, fetch it
        if (!$subscription instanceof AcademicSubscription) {
            $subscription = AcademicSubscription::findOrFail($subscription);
        }

        // Check permissions - only the student who owns this subscription can view
        if (!$user->isStudent() || (int) $subscription->student_id !== (int) $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
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

        return view('student.circle-report', array_merge(compact(
            'subscription',
            'student',
            'subject',
            'teacher',
            'performance',
            'attendance',
            'progress'
        ), ['reportType' => 'academic']));
    }
}
