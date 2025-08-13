<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuranSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show session details for student
     */
    public function showForStudent(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Check if user is a student
        if ($user->user_type !== 'student') {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'quranTeacher',
                'individualCircle.subscription.package',
                'circle',
                'homework',
                'progress'
            ])
            ->first();

        if (!$session) {
            abort(404, 'الجلسة غير موجودة أو غير مصرح لك بالوصول إليها');
        }

        return view('student.session-detail', compact('session', 'academy'));
    }

    /**
     * Show session details for teacher
     */
    public function showForTeacher(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Check if user is a Quran teacher
        if ($user->user_type !== 'quran_teacher') {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        // Get teacher profile
        $teacherProfile = $user->quranTeacherProfile;
        if (!$teacherProfile) {
            abort(403, 'ملف المعلم غير موجود');
        }

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $user->id)
            ->with([
                'student',
                'individualCircle.subscription.package',
                'circle',
                'quranTeacher',
                'homework',
                'progress'
            ])
            ->first();

        if (!$session) {
            abort(404, 'الجلسة غير موجودة أو غير مصرح لك بالوصول إليها');
        }

        return view('teacher.session-detail', compact('session', 'academy'));
    }

    /**
     * Update session notes (for teachers)
     */
    public function updateNotes(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $request->validate([
            'teacher_notes' => 'nullable|string|max:2000',
            'student_progress' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
        ]);

        $session->update([
            'teacher_notes' => $request->teacher_notes,
            'student_progress' => $request->student_progress,
            'homework_assigned' => $request->homework_assigned,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الملاحظات بنجاح'
        ]);
    }

    /**
     * Mark session as completed
     */
    public function markCompleted(Request $request, $subdomain, $sessionId)
    {
        Log::info('=== MARK COMPLETED METHOD REACHED ===', [
            'session_id' => $sessionId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => Auth::id()
        ]);
        
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        if ($session->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'الجلسة مكتملة بالفعل'], 400);
        }

        $result = $session->markAsCompleted();
        
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إكمال هذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء الجلسة بنجاح'
        ]);
    }

    /**
     * Mark session as cancelled
     */
    public function markCancelled(Request $request, $subdomain, $sessionId)
    {
        Log::info('=== MARK CANCELLED METHOD REACHED ===', [
            'session_id' => $sessionId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => Auth::id()
        ]);
        
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $session->markAsCancelled(
            $request->reason,
            $user->name
        );
        
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إلغاء هذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الجلسة بنجاح'
        ]);
    }

    /**
     * Mark session as absent (individual circles only)
     */
    public function markAbsent(Request $request, $subdomain, $sessionId)
    {
        Log::info('=== MARK ABSENT METHOD REACHED ===', [
            'session_id' => $sessionId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => Auth::id()
        ]);
        
        $user = Auth::user();
        
        // Enhanced debugging - let's see exactly what's happening
        Log::info('Mark Absent Debug - Enhanced', [
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'user_name' => $user->name,
            'session_id' => $sessionId,
            'is_quran_teacher' => $user->isQuranTeacher(),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ]);
        
        // Check if the session exists at all
        $sessionExists = QuranSession::where('id', $sessionId)->first();
        if (!$sessionExists) {
            Log::error('Session not found in database', ['session_id' => $sessionId]);
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة في قاعدة البيانات'], 404);
        }
        
        Log::info('Session found', [
            'session' => $sessionExists->only(['id', 'session_type', 'quran_teacher_id', 'student_id', 'status'])
        ]);
        
        $session = QuranSession::where('id', $sessionId)
            ->where('session_type', 'individual') // Only for individual sessions
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (!$session) {
            // Let's debug why the session wasn't found
            $allUserSessions = QuranSession::where('quran_teacher_id', $user->id)->get(['id', 'session_type', 'status']);
            $individualSessions = QuranSession::where('session_type', 'individual')->where('id', $sessionId)->get(['id', 'quran_teacher_id', 'session_type']);
            
            Log::error('Session access failed', [
                'searched_session_id' => $sessionId,
                'user_sessions_count' => $allUserSessions->count(),
                'user_sessions' => $allUserSessions->toArray(),
                'individual_session_check' => $individualSessions->toArray()
            ]);
            
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة أو ليست جلسة فردية'], 404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $session->markAsAbsent($request->reason);
        
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تسجيل غياب لهذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل غياب الطالب بنجاح'
        ]);
    }

    /**
     * Get available status actions for a session
     */
    public function getStatusActions(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $statusData = $session->getStatusDisplayData();
        $actions = [];

        // Complete action
        if ($statusData['can_complete']) {
            $actions[] = [
                'action' => 'complete',
                'label' => 'إنهاء الجلسة',
                'icon' => 'ri-check-line',
                'color' => 'green',
                'url' => route('teacher.sessions.complete', ['subdomain' => $user->academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT'
            ];
        }

        // Cancel action
        if ($statusData['can_cancel']) {
            $actions[] = [
                'action' => 'cancel',
                'label' => 'إلغاء الجلسة',
                'icon' => 'ri-close-line',
                'color' => 'red',
                'url' => route('teacher.sessions.cancel', ['subdomain' => $user->academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT',
                'confirm' => true
            ];
        }

        // Absent action (individual circles only)
        if ($session->session_type === 'individual' && $statusData['can_complete']) {
            $actions[] = [
                'action' => 'absent',
                'label' => 'تسجيل غياب الطالب',
                'icon' => 'ri-user-x-line',
                'color' => 'orange',
                'url' => route('teacher.sessions.absent', ['subdomain' => $user->academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT',
                'confirm' => true
            ];
        }

        return response()->json([
            'success' => true,
            'actions' => $actions,
            'session_type' => $session->session_type,
            'current_status' => $session->status,
            'status_data' => $statusData
        ]);
    }

    /**
     * Add student feedback (for students)
     */
    public function addFeedback(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة أو غير مكتملة'], 404);
        }

        $request->validate([
            'student_feedback' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $session->update([
            'student_feedback' => $request->student_feedback,
            'student_rating' => $request->rating,
            'feedback_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تقييمك بنجاح'
        ]);
    }
}