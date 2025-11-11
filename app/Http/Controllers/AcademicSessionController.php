<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->with(['student', 'academicIndividualLesson', 'interactiveCourseSession'])
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
    public function show($subdomain, $sessionId)
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
            'interactiveCourseSession.course',
        ]);

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('teacher.academic-sessions.show', compact('session', 'viewType'));
    }

    /**
     * Add student feedback to session
     */
    public function addStudentFeedback(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
     * Submit homework for academic session
     */
    public function submitHomework(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

        // Check if user is the student for this session
        if (! $user->isStudent() || (int) $session->student_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح لك بتسليم الواجب لهذه الجلسة'], 403);
        }

        // Validate request
        $request->validate([
            'homework_submission' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,txt,jpg,jpeg,png',
        ]);

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store('academic-homework', 'public');
        }

        // Create or update academic session report for homework submission
        $studentReport = \App\Models\AcademicSessionReport::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $user->id,
            ],
            [
                'homework_description' => $request->homework_submission,
                'homework_file' => $homeworkFilePath,
                'homework_submitted_at' => now(),
                'academy_id' => $session->academy_id,
                'teacher_id' => $session->academic_teacher_id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسليم الواجب بنجاح',
            'data' => [
                'submission' => $request->homework_submission,
                'file_path' => $homeworkFilePath,
            ],
        ]);
    }

    /**
     * Update session evaluation (for teachers)
     */
    public function updateEvaluation(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
            'session_grade' => 'nullable|numeric|min:0|max:10',
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
    public function updateStatus(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
            'attendance_status' => 'nullable|in:scheduled,present,absent,late,partial',
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
    public function reschedule(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
            'rescheduling_note' => 'nullable|string|max:500',
        ]);

        $session->update([
            'rescheduled_from' => $session->scheduled_at,
            'rescheduled_to' => $validated['new_scheduled_at'],
            'scheduled_at' => $validated['new_scheduled_at'],
            'reschedule_reason' => $validated['reschedule_reason'],
            'rescheduling_note' => $validated['rescheduling_note'] ?? null,
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
    public function cancel(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $session = AcademicSession::findOrFail($sessionId);

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
            'cancellation_type' => 'nullable|string|max:100',
        ]);

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancellation_type' => $validated['cancellation_type'] ?? 'teacher_cancelled',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الجلسة بنجاح',
            'session' => $session->fresh(),
        ]);
    }
}
