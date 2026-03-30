<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Http\Requests\CancelQuranSessionRequest;
use App\Http\Requests\MarkQuranSessionAbsentRequest;
use App\Http\Requests\Session\AddQuranSessionFeedbackRequest;
use App\Http\Requests\UpdateQuranSessionNotesRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QuranSessionController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show session details for student
     */
    public function showForStudent(Request $request, $subdomain, $sessionId): View
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Query for sessions - handle individual, trial, and group sessions
        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where(function ($query) use ($user) {
                // Individual sessions: direct student_id match
                $query->where('student_id', $user->id)
                    // OR trial sessions: student owns the trial request
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('session_type', 'trial')
                            ->whereHas('trialRequest', function ($trialQuery) use ($user) {
                                $trialQuery->where('student_id', $user->id);
                            });
                    })
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

        // Authorize user can view this session
        $this->authorize('view', $session);

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('student.session-detail', compact('session', 'academy'));
    }

    /**
     * Show session details for teacher
     */
    public function showForTeacher(Request $request, $subdomain, $sessionId): View
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Build query based on user type
        $query = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id);

        // For regular teachers, only show their own sessions
        // For admins and super_admins, show any session in their academy
        if ($user->user_type === UserType::QURAN_TEACHER->value) {
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

        // Authorize user can view this session
        $this->authorize('view', $session);

        // Automatic meeting creation fallback for ready/ongoing sessions
        $session->ensureMeetingExists();

        return view('teacher.session-detail', compact('session', 'academy'));
    }

    /**
     * Update session notes (for teachers)
     */
    public function updateNotes(UpdateQuranSessionNotesRequest $request, $subdomain, $sessionId): JsonResponse
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
            return $this->notFound('الجلسة غير موجودة');
        }

        // Authorize user can update this session
        $this->authorize('update', $session);

        $session->update([
            'lesson_content' => $request->lesson_content,
            'teacher_notes' => $request->teacher_notes,
            'student_progress' => $request->student_progress,
            'homework_assigned' => $request->homework_assigned,
        ]);

        return $this->success(null, 'تم حفظ الملاحظات بنجاح');
    }

    /**
     * Mark session as completed
     */
    public function markCompleted(Request $request, $subdomain, $sessionId): JsonResponse
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
            return $this->notFound('الجلسة غير موجودة');
        }

        // Authorize user can update this session
        $this->authorize('update', $session);

        if ($session->status === SessionStatus::COMPLETED) {
            return $this->error('الجلسة مكتملة بالفعل', 400);
        }

        $result = $session->markAsCompleted();

        if (! $result) {
            return $this->error('لا يمكن إكمال هذه الجلسة في حالتها الحالية', 400);
        }

        return $this->success(null, 'تم إنهاء الجلسة بنجاح');
    }

    /**
     * Mark session as cancelled
     */
    public function markCancelled(CancelQuranSessionRequest $request, $subdomain, $sessionId): JsonResponse
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
            return $this->notFound('الجلسة غير موجودة');
        }

        // Authorize user can cancel this session
        $this->authorize('cancel', $session);

        $result = $session->markAsCancelled(
            $request->reason ?? 'ألغيت بواسطة المعلم',
            $user,
            'teacher'
        );

        if (! $result) {
            return $this->error('لا يمكن إلغاء هذه الجلسة في حالتها الحالية', 400);
        }

        return $this->success(null, 'تم إلغاء الجلسة بنجاح');
    }

    /**
     * Mark session as absent (individual circles only)
     */
    public function markAbsent(MarkQuranSessionAbsentRequest $request, $subdomain, $sessionId): JsonResponse
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

            return $this->notFound('الجلسة غير موجودة في قاعدة البيانات');
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

            return $this->notFound('الجلسة غير موجودة أو ليست جلسة فردية');
        }

        // Authorize user can update this session
        $this->authorize('update', $session);

        $result = $session->markAsAbsent($request->reason);

        if (! $result) {
            return $this->error('لا يمكن تسجيل غياب لهذه الجلسة في حالتها الحالية', 400);
        }

        return $this->success(null, 'تم تسجيل غياب الطالب بنجاح');
    }

    /**
     * Get available status actions for a session
     */
    public function getStatusActions(Request $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            return $this->notFound('Academy not found');
        }

        // Check if user has permission to access teacher sessions
        if (! in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ADMIN->value, UserType::SUPER_ADMIN->value])) {
            return $this->forbidden('غير مسموح لك بالوصول');
        }

        // Build query based on user type
        $query = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id);

        // For regular teachers, only show their own sessions
        // For admins and super_admins, show any session in their academy
        if ($user->user_type === UserType::QURAN_TEACHER->value) {
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
            return $this->notFound('الجلسة غير موجودة');
        }

        // Authorize user can view this session
        $this->authorize('view', $session);

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

        return $this->success([
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
    public function addFeedback(AddQuranSessionFeedbackRequest $request, $subdomain, $sessionId): JsonResponse
    {
        $user = Auth::user();

        // Get academy from container (set by middleware) or from user
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            return $this->notFound('Academy not found');
        }

        // Query for sessions - handle individual, trial, and group sessions
        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->where(function ($query) use ($user) {
                // Individual sessions: direct student_id match
                $query->where('student_id', $user->id)
                    // OR trial sessions: student owns the trial request
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('session_type', 'trial')
                            ->whereHas('trialRequest', function ($trialQuery) use ($user) {
                                $trialQuery->where('student_id', $user->id);
                            });
                    })
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
            return $this->notFound('الجلسة غير موجودة أو غير مكتملة');
        }

        // Authorize user can view this session (students can only add feedback to their own sessions)
        $this->authorize('view', $session);

        $session->update([
            'student_feedback' => $request->student_feedback,
            'student_rating' => $request->rating,
            'feedback_at' => now(),
        ]);

        return $this->success(null, 'تم إرسال تقييمك بنجاح');
    }

    /**
     * Show form to create a new Quran session
     */
    public function create(Request $request, $subdomain, $sessionId = null): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        // Get teacher's individual circles
        $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->with('student')
            ->get();

        // Get teacher's group circles
        $groupCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', true)
            ->get();

        $session = null;
        $isEdit = false;
        $selectedCircleId = $request->query('circle_id');
        $selectedCircleType = $request->query('circle_type', 'individual');

        return view('teacher.sessions.quran-form', compact(
            'session', 'isEdit', 'academy', 'individualCircles', 'groupCircles',
            'selectedCircleId', 'selectedCircleType'
        ));
    }

    /**
     * Store a new Quran session
     */
    public function storeSession(Request $request, $subdomain): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $validated = $request->validate([
            'circle_type' => 'required|string|in:individual,group',
            'individual_circle_id' => 'required_if:circle_type,individual|nullable|exists:quran_individual_circles,id',
            'circle_id' => 'required_if:circle_type,group|nullable|exists:quran_circles,id',
            'scheduled_at' => 'required|date|after:now',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lesson_content' => 'nullable|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:15|max:180',
        ]);

        // Determine circle ownership and session type
        $sessionData = [
            'academy_id' => $academy->id,
            'quran_teacher_id' => $user->id,
            'status' => SessionStatus::SCHEDULED,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'lesson_content' => $validated['lesson_content'] ?? null,
            'meeting_auto_generated' => true,
        ];

        // Convert scheduled_at from academy timezone to UTC
        $scheduledAt = Carbon::parse($validated['scheduled_at'], AcademyContextService::getTimezone());
        $sessionData['scheduled_at'] = AcademyContextService::toUtcForStorage($scheduledAt);

        if ($validated['circle_type'] === 'individual') {
            $circle = QuranIndividualCircle::where('id', $validated['individual_circle_id'])
                ->where('quran_teacher_id', $user->id)
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            $sessionData['session_type'] = 'individual';
            $sessionData['individual_circle_id'] = $circle->id;
            $sessionData['student_id'] = $circle->student_id;
            $sessionData['duration_minutes'] = $validated['duration_minutes'] ?? $circle->default_duration_minutes ?? 60;

            // Link to subscription if available
            if ($circle->subscription_id) {
                $sessionData['quran_subscription_id'] = $circle->subscription_id;
            }
        } else {
            $circle = QuranCircle::where('id', $validated['circle_id'])
                ->where('quran_teacher_id', $user->id)
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            $sessionData['session_type'] = 'group';
            $sessionData['circle_id'] = $circle->id;
            $sessionData['duration_minutes'] = $validated['duration_minutes'] ?? 60;
        }

        $session = QuranSession::create($sessionData);

        return redirect()
            ->route('teacher.sessions.show', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id])
            ->with('success', __('teacher.session_form.created_success'));
    }

    /**
     * Show form to edit a Quran session (only SCHEDULED sessions)
     */
    public function edit(Request $request, $subdomain, $sessionId): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id);
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId);
                }
            })
            ->firstOrFail();

        // Only SCHEDULED sessions can be edited
        if ($session->status !== SessionStatus::SCHEDULED) {
            return redirect()
                ->route('teacher.sessions.show', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id])
                ->with('error', __('teacher.session_form.only_scheduled_editable'));
        }

        $this->authorize('update', $session);

        $isEdit = true;

        // Get teacher's circles for the form
        $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->with('student')
            ->get();

        $groupCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', true)
            ->get();

        $selectedCircleId = $session->session_type === 'individual'
            ? $session->individual_circle_id
            : $session->circle_id;
        $selectedCircleType = $session->session_type === 'group' ? 'group' : 'individual';

        return view('teacher.sessions.quran-form', compact(
            'session', 'isEdit', 'academy', 'individualCircles', 'groupCircles',
            'selectedCircleId', 'selectedCircleType'
        ));
    }

    /**
     * Update an existing Quran session (only SCHEDULED sessions)
     */
    public function updateSession(Request $request, $subdomain, $sessionId): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $teacherProfileId = $user->quranTeacherProfile?->id;

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where(function ($query) use ($user, $teacherProfileId) {
                $query->where('quran_teacher_id', $user->id);
                if ($teacherProfileId) {
                    $query->orWhere('quran_teacher_id', $teacherProfileId);
                }
            })
            ->firstOrFail();

        // Only SCHEDULED sessions can be edited
        if ($session->status !== SessionStatus::SCHEDULED) {
            return redirect()
                ->route('teacher.sessions.show', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id])
                ->with('error', __('teacher.session_form.only_scheduled_editable'));
        }

        $this->authorize('update', $session);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lesson_content' => 'nullable|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:15|max:180',
        ]);

        // Convert scheduled_at from academy timezone to UTC
        $scheduledAt = Carbon::parse($validated['scheduled_at'], AcademyContextService::getTimezone());

        $session->update([
            'scheduled_at' => AcademyContextService::toUtcForStorage($scheduledAt),
            'title' => $validated['title'] ?? $session->title,
            'description' => $validated['description'] ?? $session->description,
            'lesson_content' => $validated['lesson_content'] ?? $session->lesson_content,
            'duration_minutes' => $validated['duration_minutes'] ?? $session->duration_minutes,
        ]);

        return redirect()
            ->route('teacher.sessions.show', ['subdomain' => $academy->subdomain, 'sessionId' => $session->id])
            ->with('success', __('teacher.session_form.updated_success'));
    }
}
