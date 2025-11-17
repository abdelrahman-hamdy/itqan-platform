<?php

namespace App\Http\Controllers;

use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Services\QuranSessionSchedulingService;
use App\Services\QuranProgressService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuranIndividualCircleController extends Controller
{
    private QuranSessionSchedulingService $schedulingService;
    private QuranProgressService $progressService;

    public function __construct(
        QuranSessionSchedulingService $schedulingService,
        QuranProgressService $progressService
    ) {
        $this->schedulingService = $schedulingService;
        $this->progressService = $progressService;
        $this->middleware('auth');
    }

    /**
     * Display individual circles for the teacher
     */
    public function index(Request $request, $subdomain = null)
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $circles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id)
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

        // Resolve academy from subdomain
        $tenantAcademy = \App\Models\Academy::where('subdomain', $subdomain)->first();
        if (! $tenantAcademy) {
            abort(404);
        }

        // Fetch circle without academy global scope then validate tenant academy
        $circleModel = QuranIndividualCircle::withoutGlobalScope('academy')->findOrFail($circle);

        if ((int) $circleModel->academy_id !== (int) $tenantAcademy->id) {
            abort(404);
        }

        // Determine user role and permissions
        $userRole = 'guest';
        $isTeacher = false;
        $isStudent = false;

        if ($user->user_type === 'quran_teacher' && (int) $circleModel->quran_teacher_id === (int) $user->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === 'student' && (int) $circleModel->student_id === (int) $user->id) {
            $userRole = 'student';
            $isStudent = true;
        } else {
            abort(403, 'غير مسموح لك بالوصول لهذه الحلقة');
        }

        $circleModel->load([
            'student',
            'subscription.package',
            'quranTeacher',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'scheduledSessions' => function ($query) {
                $query->whereIn('status', ['scheduled', 'in_progress'])->orderBy('scheduled_at');
            },
            'completedSessions' => function ($query) {
                $query->where('status', 'completed')->orderBy('ended_at', 'desc');
            },
        ]);

        $upcomingSessions = $circleModel->sessions()
            ->whereIn('status', ['scheduled', 'in_progress', 'unscheduled', 'ongoing', 'ready'])
            ->where(function ($query) {
                $query->where('scheduled_at', '>', now())
                    ->orWhereNull('scheduled_at') // Include unscheduled sessions
                    ->orWhereIn('status', ['ongoing', 'ready']); // Include ongoing and ready sessions regardless of time
            })
            ->orderByRaw('scheduled_at IS NULL') // Put scheduled sessions first
            ->orderBy('scheduled_at')
            ->orderBy('id') // Secondary sort for consistent ordering
            ->get();

        $pastSessions = $circleModel->sessions()
            ->whereIn('status', ['completed', 'cancelled', 'no_show', 'absent'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $circle = $circleModel;
        $individualCircle = $circleModel;

        $viewName = $userRole === 'teacher' ? 'teacher.individual-circles.show' : 'student.individual-circles.show';

        return view($viewName, compact('circle', 'individualCircle', 'userRole', 'isTeacher', 'isStudent', 'upcomingSessions', 'pastSessions'));
    }

    /**
     * Get available time slots for scheduling
     */
    public function getAvailableTimeSlots(Request $request, $circle): JsonResponse
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
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
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Update circle settings
     */
    public function updateSettings(Request $request, $circle): JsonResponse
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
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
                'message' => 'تم تحديث إعدادات الحلقة بنجاح',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الإعدادات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate comprehensive progress report for the circle
     */
    public function progressReport($subdomain, $circle)
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::where('academy_id', $user->academy_id)
            ->findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
        }

        // Load comprehensive data for enhanced progress tracking
        $circleModel->load([
            'student.studentProfile',
            'subscription.package',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'homework',
            'progress',
        ]);

        // Calculate progress statistics using the service
        $stats = $this->progressService->calculateProgressStats($circleModel);

        // Rename for view consistency
        $circle = $circleModel;

        return view('teacher.individual-circles.progress', compact('circle', 'stats'));
    }
}
