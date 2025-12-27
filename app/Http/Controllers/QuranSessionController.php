<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

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

        // Get academy from container (set by middleware) or from user
        $academy = app('current_academy') ?? $user->academy;

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Check if user is a student
        if ($user->user_type !== 'student') {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        // Query for sessions - handle individual, trial, and group sessions
        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where(function ($query) use ($user) {
                // Individual/trial sessions: direct student_id match
                $query->where('student_id', $user->id)
                    // OR group sessions: student enrolled in the circle
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('session_type', 'group')
                            ->whereHas('circle.students', function ($circleQuery) use ($user) {
                                $circleQuery->where('student_id', $user->id);
                            });
                    });
            })
            ->with([
                'quranTeacher',
                'individualCircle.subscription.package',
                'circle.students',
                'sessionHomework',
                'studentReports',
                'trialRequest', // For trial sessions
            ])
            ->first();

        if (! $session) {
            abort(404, 'الجلسة غير موجودة أو غير مصرح لك بالوصول إليها');
        }

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('student.session-detail', compact('session', 'academy'));
    }

    /**
     * Show session details for teacher
     */
    public function showForTeacher(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = app('current_academy') ?? $user->academy;

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Check if user has permission to access teacher sessions
        if (! in_array($user->user_type, ['quran_teacher', 'admin', 'super_admin'])) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        // Get teacher profile (only required for quran_teacher users)
        if ($user->user_type === 'quran_teacher') {
            $teacherProfile = $user->quranTeacherProfile;
            if (! $teacherProfile) {
                abort(403, 'ملف المعلم غير موجود');
            }
        }

        // Build query based on user type
        $query = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id);

        // For regular teachers, only show their own sessions
        // For admins and super_admins, show any session in their academy
        if ($user->user_type === 'quran_teacher') {
            // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
            $teacherProfileId = $user->quranTeacherProfile?->id;

            $query->where(function ($subQuery) use ($user, $teacherProfileId) {
                $subQuery->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $subQuery->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            });
        }

        $session = $query->with([
            'student',
            'individualCircle.subscription.package',
            'circle.students',
            'quranTeacher',
            'sessionHomework',
            'studentReports',
        ])
            ->first();

        if (! $session) {
            abort(404, 'الجلسة غير موجودة أو غير مصرح لك بالوصول إليها');
        }

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('teacher.session-detail', compact('session', 'academy'));
    }

    /**
     * Update session notes (for teachers)
     */
    public function updateNotes(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();

        // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            })
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $request->validate([
            'lesson_content' => 'nullable|string|max:5000',
            'teacher_notes' => 'nullable|string|max:2000',
            'student_progress' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
        ]);

        $session->update([
            'lesson_content' => $request->lesson_content,
            'teacher_notes' => $request->teacher_notes,
            'student_progress' => $request->student_progress,
            'homework_assigned' => $request->homework_assigned,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الملاحظات بنجاح',
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
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();

        // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            })
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        if ($session->status === SessionStatus::COMPLETED) {
            return response()->json(['success' => false, 'message' => 'الجلسة مكتملة بالفعل'], 400);
        }

        $result = $session->markAsCompleted();

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إكمال هذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء الجلسة بنجاح',
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
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();

        // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            })
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $session->markAsCancelled(
            $request->reason,
            $user->name
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إلغاء هذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الجلسة بنجاح',
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
            'user_id' => Auth::id(),
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
        if (! $sessionExists) {
            Log::error('Session not found in database', ['session_id' => $sessionId]);

            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة في قاعدة البيانات'], 404);
        }

        Log::info('Session found', [
            'session' => $sessionExists->only(['id', 'session_type', 'quran_teacher_id', 'student_id', 'status']),
        ]);

        // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where('session_type', 'individual') // Only for individual sessions
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            })
            ->first();

        if (! $session) {
            // Let's debug why the session wasn't found
            $allUserSessions = QuranSession::where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id);
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId);
                }
            })->get(['id', 'session_type', 'status']);

            $individualSessions = QuranSession::where('session_type', 'individual')->where('id', $sessionId)->get(['id', 'quran_teacher_id', 'session_type']);

            Log::error('Session access failed', [
                'searched_session_id' => $sessionId,
                'user_id' => $user->id,
                'teacher_profile_id' => $teacherProfileId,
                'user_sessions_count' => $allUserSessions->count(),
                'user_sessions' => $allUserSessions->toArray(),
                'individual_session_check' => $individualSessions->toArray(),
            ]);

            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة أو ليست جلسة فردية'], 404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $session->markAsAbsent($request->reason);

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تسجيل غياب لهذه الجلسة في حالتها الحالية'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل غياب الطالب بنجاح',
        ]);
    }

    /**
     * Get available status actions for a session
     */
    public function getStatusActions(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = app('current_academy') ?? $user->academy;

        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'Academy not found'], 404);
        }

        // Check if user has permission to access teacher sessions
        if (! in_array($user->user_type, ['quran_teacher', 'admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'غير مسموح لك بالوصول'], 403);
        }

        // Build query based on user type
        $query = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id);

        // For regular teachers, only show their own sessions
        // For admins and super_admins, show any session in their academy
        if ($user->user_type === 'quran_teacher') {
            // Handle both individual sessions (user_id) and group sessions (teacher_profile_id)
            $teacherProfileId = $user->quranTeacherProfile?->id;

            $query->where(function ($subQuery) use ($user, $teacherProfileId) {
                $subQuery->where('quran_teacher_id', $user->id); // Individual sessions
                if ($teacherProfileId) {
                    $subQuery->orWhere('quran_teacher_id', $teacherProfileId); // Group sessions
                }
            });
        }

        $session = $query->first();

        if (! $session) {
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
                'url' => route('teacher.sessions.complete', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT',
            ];
        }

        // Cancel action
        if ($statusData['can_cancel']) {
            $actions[] = [
                'action' => 'cancel',
                'label' => 'إلغاء الجلسة',
                'icon' => 'ri-close-line',
                'color' => 'red',
                'url' => route('teacher.sessions.cancel', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT',
                'confirm' => true,
            ];
        }

        // Absent action (individual circles only)
        if ($session->session_type === 'individual' && $statusData['can_complete']) {
            $actions[] = [
                'action' => 'absent',
                'label' => 'تسجيل غياب الطالب',
                'icon' => 'ri-user-x-line',
                'color' => 'orange',
                'url' => route('teacher.sessions.absent', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id]),
                'method' => 'PUT',
                'confirm' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'actions' => $actions,
            'session_type' => $session->session_type,
            'current_status' => $session->status,
            'status_data' => $statusData,
        ]);
    }

    /**
     * Add student feedback (for students)
     */
    public function addFeedback(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = app('current_academy') ?? $user->academy;

        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'Academy not found'], 404);
        }

        // Query for sessions - handle both individual and group sessions
        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->where(function ($query) use ($user) {
                // Individual sessions: direct student_id match
                $query->where('student_id', $user->id)
                    // OR group sessions: student enrolled in the circle
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('session_type', 'group')
                            ->whereHas('circle.students', function ($circleQuery) use ($user) {
                                $circleQuery->where('student_id', $user->id);
                            });
                    });
            })
            ->first();

        if (! $session) {
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
            'message' => 'تم إرسال تقييمك بنجاح',
        ]);
    }
}
