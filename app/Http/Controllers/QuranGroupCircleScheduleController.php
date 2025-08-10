<?php

namespace App\Http\Controllers;

use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Services\QuranSessionSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class QuranGroupCircleScheduleController extends Controller
{
    private QuranSessionSchedulingService $schedulingService;

    public function __construct(QuranSessionSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
        $this->middleware('auth');
    }

    /**
     * Display teacher's group circles and their schedules
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $circles = QuranCircle::where('quran_teacher_id', $user->id)
            ->with(['schedule', 'academy'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.group-circles.index', compact('circles'));
    }

    /**
     * Show form to create/edit schedule for a group circle
     */
    public function create(QuranCircle $circle)
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بإدارة هذه الحلقة');
        }

        // Load existing schedule if any
        $circle->load('schedule');

        return view('teacher.group-circles.schedule-form', compact('circle'));
    }

    /**
     * Store or update schedule for a group circle
     */
    public function store(Request $request, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'weekly_schedule.*.time' => 'required|regex:/^\d{2}:\d{2}$/',
            'weekly_schedule.*.duration' => 'integer|min:15|max:240',
            'schedule_starts_at' => 'required|date|after_or_equal:today',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'session_title_template' => 'nullable|string|max:255',
            'session_description_template' => 'nullable|string',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'generate_ahead_days' => 'integer|min:7|max:90',
        ]);

        try {
            // Prepare schedule data
            $scheduleData = [
                'weekly_schedule' => $request->weekly_schedule,
                'schedule_starts_at' => Carbon::parse($request->schedule_starts_at),
                'schedule_ends_at' => $request->schedule_ends_at ? Carbon::parse($request->schedule_ends_at) : null,
                'session_title_template' => $request->session_title_template,
                'session_description_template' => $request->session_description_template,
                'meeting_link' => $request->meeting_link,
                'meeting_id' => $request->meeting_id,
                'meeting_password' => $request->meeting_password,
                'recording_enabled' => $request->boolean('recording_enabled'),
                'generate_ahead_days' => $request->generate_ahead_days ?? 30,
            ];

            // Determine default duration from schedule
            $defaultDuration = collect($request->weekly_schedule)->avg('duration') ?? 60;
            $scheduleData['duration'] = $defaultDuration;

            if ($circle->schedule) {
                // Update existing schedule
                $schedule = $this->schedulingService->updateGroupCircleSchedule(
                    $circle->schedule,
                    $scheduleData
                );
                $message = 'تم تحديث جدول الحلقة بنجاح';
            } else {
                // Create new schedule
                $schedule = $this->schedulingService->createGroupCircleSchedule(
                    $circle,
                    $request->weekly_schedule,
                    Carbon::parse($request->schedule_starts_at),
                    $request->schedule_ends_at ? Carbon::parse($request->schedule_ends_at) : null,
                    $scheduleData
                );
                $message = 'تم إنشاء جدول الحلقة بنجاح';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'schedule_id' => $schedule->id,
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حفظ الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate schedule for a group circle
     */
    public function activate(QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (!$circle->schedule) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إنشاء جدول زمني أولاً'
            ], 400);
        }

        try {
            $circle->schedule->activateSchedule();

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل جدول الحلقة بنجاح',
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تفعيل الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate schedule for a group circle
     */
    public function deactivate(QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (!$circle->schedule) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد جدول زمني لإلغاء تفعيله'
            ], 400);
        }

        try {
            $circle->schedule->deactivateSchedule();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء تفعيل جدول الحلقة بنجاح',
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إلغاء تفعيل الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview upcoming sessions for a schedule
     */
    public function previewSessions(Request $request, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'weekly_schedule.*.time' => 'required|regex:/^\d{2}:\d{2}$/',
            'schedule_starts_at' => 'required|date',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'preview_days' => 'integer|min:7|max:90',
        ]);

        try {
            // Create a temporary schedule object for preview
            $tempSchedule = new QuranCircleSchedule([
                'weekly_schedule' => $request->weekly_schedule,
                'schedule_starts_at' => $request->schedule_starts_at,
                'schedule_ends_at' => $request->schedule_ends_at,
                'default_duration_minutes' => 60,
            ]);

            $previewDays = $request->preview_days ?? 30;
            $startDate = Carbon::parse($request->schedule_starts_at);
            $endDate = $startDate->copy()->addDays($previewDays);

            if ($request->schedule_ends_at) {
                $endDate = $endDate->min(Carbon::parse($request->schedule_ends_at));
            }

            $upcomingSessions = $tempSchedule->getUpcomingSessionsForRange($startDate, $endDate);

            return response()->json([
                'success' => true,
                'preview_period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $startDate->diffInDays($endDate),
                ],
                'sessions_count' => count($upcomingSessions),
                'upcoming_sessions' => array_slice($upcomingSessions, 0, 10), // First 10 for preview
                'weekly_pattern' => $request->weekly_schedule,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في معاينة الجلسات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show circle schedule details and generated sessions
     */
    public function show(QuranCircle $circle)
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذه الحلقة');
        }

        $circle->load([
            'schedule',
            'sessions' => function ($query) {
                $query->where('is_generated', true)
                      ->orderBy('scheduled_at', 'desc')
                      ->limit(50); // Last 50 sessions
            },
            'students',
            'academy'
        ]);

        // Get upcoming sessions
        $upcomingSessions = $circle->sessions()
            ->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return view('teacher.group-circles.show', compact('circle', 'upcomingSessions'));
    }

    /**
     * Generate additional sessions manually
     */
    public function generateSessions(QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (!$circle->schedule || !$circle->schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الجدول الزمني غير نشط'
            ], 400);
        }

        try {
            $generatedCount = $circle->schedule->generateUpcomingSessions();

            return response()->json([
                'success' => true,
                'message' => $generatedCount > 0 
                    ? "تم إنشاء {$generatedCount} جلسة جديدة"
                    : 'لا توجد جلسات جديدة للإنشاء',
                'generated_count' => $generatedCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء الجلسات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule statistics
     */
    public function getStats(QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Check ownership
        if ($circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $stats = [
            'total_sessions_generated' => $circle->sessions()->where('is_generated', true)->count(),
            'completed_sessions' => $circle->sessions()->where('status', 'completed')->count(),
            'upcoming_sessions' => $circle->sessions()
                ->where('scheduled_at', '>=', now())
                ->where('status', 'scheduled')
                ->count(),
            'cancelled_sessions' => $circle->sessions()->where('status', 'cancelled')->count(),
            'enrolled_students' => $circle->enrolled_students,
            'attendance_rate' => $circle->sessions()->where('status', 'completed')->count() > 0
                ? ($circle->sessions()->where('attendance_status', 'attended')->count() / 
                   $circle->sessions()->where('status', 'completed')->count()) * 100
                : 0,
            'schedule_active' => $circle->schedule && $circle->schedule->is_active,
            'last_generated_at' => $circle->schedule?->last_generated_at?->format('Y-m-d H:i'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}