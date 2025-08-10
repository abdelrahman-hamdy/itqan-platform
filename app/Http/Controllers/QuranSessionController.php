<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'quranTeacher',
                'individualCircle.subscription.package',
                'homework',
                'progress',
                'quizzes'
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

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
            ->with([
                'student',
                'individualCircle.subscription.package',
                'homework',
                'progress',
                'quizzes'
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
    public function updateNotes(Request $request, $sessionId)
    {
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
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
    public function markCompleted(Request $request, $sessionId)
    {
        $user = Auth::user();
        
        $session = QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        if ($session->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'الجلسة مكتملة بالفعل'], 400);
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'attendance_status' => 'attended',
        ]);

        // Update individual circle progress
        if ($session->individualCircle) {
            $session->individualCircle->updateProgress();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء الجلسة بنجاح'
        ]);
    }

    /**
     * Add student feedback (for students)
     */
    public function addFeedback(Request $request, $sessionId)
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