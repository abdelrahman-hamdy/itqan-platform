<?php

namespace App\Http\Controllers;

use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Services\QuranSessionSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class QuranIndividualCircleController extends Controller
{
    private QuranSessionSchedulingService $schedulingService;

    public function __construct(QuranSessionSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
        $this->middleware('auth');
    }

    /**
     * Display individual circles for the teacher
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $circles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->with(['student', 'subscription.package'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.individual-circles.index', compact('circles'));
    }

    /**
     * Show individual circle details
     */
    public function show($subdomain, $circle)
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            abort(403, 'غير مسموح لك بالوصول لهذه الحلقة');
        }

        $circleModel->load([
            'student', 
            'subscription.package',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'scheduledSessions' => function ($query) {
                $query->whereIn('status', ['scheduled', 'in_progress'])->orderBy('scheduled_at');
            },
            'completedSessions' => function ($query) {
                $query->where('status', 'completed')->orderBy('ended_at', 'desc');
            }
        ]);

        // Rename for view consistency  
        $circle = $circleModel;
        return view('teacher.individual-circles.show', compact('circle'));
    }

    /**
     * Get unscheduled sessions for a circle (AJAX)
     */
    public function getTemplateSessions($subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        // Get unscheduled sessions instead of templates
        $unscheduledSessions = $circleModel->sessions()
            ->where('status', 'pending')
            ->orderBy('monthly_session_number')
            ->get(['id', 'title', 'monthly_session_number', 'duration_minutes']);

        return response()->json([
            'success' => true,
            'sessions' => $unscheduledSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title,
                    'sequence' => $session->monthly_session_number ?? 0,
                    'duration' => $session->duration_minutes,
                ];
            })
        ]);
    }

    /**
     * Schedule a template session
     */
    public function scheduleSession(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'template_session_id' => 'required|exists:quran_sessions,id',
            'scheduled_at' => 'required|date|after:now',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'lesson_objectives' => 'nullable|array',
        ]);

        try {
            $unscheduledSession = QuranSession::findOrFail($request->template_session_id);
            
            // Verify the session belongs to this circle and is unscheduled
            if ($unscheduledSession->individual_circle_id !== $circleModel->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة المحددة لا تنتمي لهذه الحلقة'
                ], 400);
            }

            if ($unscheduledSession->status !== 'unscheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الجلسة مجدولة بالفعل'
                ], 400);
            }

            // Update the unscheduled session to scheduled
            $unscheduledSession->update([
                'status' => 'scheduled',
                'is_scheduled' => true,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'title' => $request->title ?: $unscheduledSession->title,
                'description' => $request->description ?: $unscheduledSession->description,
                'lesson_objectives' => $request->lesson_objectives ?: $unscheduledSession->lesson_objectives,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم جدولة الجلسة بنجاح',
                'session' => [
                    'id' => $unscheduledSession->id,
                    'title' => $unscheduledSession->title,
                    'scheduled_at' => $unscheduledSession->scheduled_at->format('Y-m-d H:i'),
                    'sequence' => $unscheduledSession->monthly_session_number ?? 0,
                    'status' => $unscheduledSession->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جدولة الجلسة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk schedule multiple sessions
     */
    public function bulkSchedule(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'sessions' => 'required|array|min:1',
            'sessions.*.template_session_id' => 'required|exists:quran_sessions,id',
            'sessions.*.scheduled_at' => 'required|date|after:now',
            'sessions.*.title' => 'nullable|string|max:255',
            'sessions.*.description' => 'nullable|string',
        ]);

        try {
            $scheduledSessions = $this->schedulingService->bulkScheduleIndividualSessions(
                $circleModel,
                $request->sessions
            );

            return response()->json([
                'success' => true,
                'message' => "تم جدولة {$scheduledSessions->count()} جلسة بنجاح",
                'scheduled_count' => $scheduledSessions->count(),
                'sessions' => $scheduledSessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i'),
                        'sequence' => $session->monthly_session_number ?? 0,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جدولة الجلسات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for scheduling
     */
    public function getAvailableTimeSlots(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'integer|min:15|max:240',
        ]);

        $date = Carbon::parse($request->date);
        $duration = $request->duration ?? $circleModel->default_duration_minutes;

        $availableSlots = $this->schedulingService->getAvailableTimeSlots(
            $user->id,
            $date,
            $duration
        );

        return response()->json([
            'success' => true,
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots
        ]);
    }

    /**
     * Update circle settings
     */
    public function updateSettings(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'default_duration_minutes' => 'integer|min:15|max:240',
            'preferred_times' => 'array',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $circleModel->update($request->only([
                'default_duration_minutes',
                'preferred_times',
                'meeting_link',
                'meeting_id',
                'meeting_password',
                'recording_enabled',
                'notes',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث إعدادات الحلقة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الإعدادات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate progress report for the circle
     */
    public function progressReport($subdomain, $circle)
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if (!$user->quranTeacherProfile || $circleModel->quran_teacher_id !== $user->quranTeacherProfile->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
        }

        $circleModel->load([
            'student',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'homework',
            'progress'
        ]);

        // Calculate statistics
        $stats = [
            'total_sessions' => $circleModel->total_sessions,
            'completed_sessions' => $circleModel->sessions_completed,
            'scheduled_sessions' => $circleModel->sessions_scheduled,
            'remaining_sessions' => $circleModel->sessions_remaining,
            'progress_percentage' => $circleModel->progress_percentage,
            'attendance_rate' => $circleModel->sessions_completed > 0 
                ? ($circleModel->sessions()->where('attendance_status', 'attended')->count() / $circleModel->sessions_completed) * 100 
                : 0,
        ];

        // Rename for view consistency
        $circle = $circleModel;
        return view('teacher.individual-circles.progress', compact('circle', 'stats'));
    }
}