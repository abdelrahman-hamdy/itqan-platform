<?php

namespace App\Http\Controllers;

use App\Contracts\QuranReportServiceInterface;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Http\Requests\GetAvailableTimeSlotsRequest;
use App\Http\Requests\UpdateIndividualCircleSettingsRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\QuranIndividualCircle;
use App\Services\QuranSessionSchedulingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuranIndividualCircleController extends Controller
{
    use ApiResponses;

    private QuranSessionSchedulingService $schedulingService;

    private QuranReportServiceInterface $reportService;

    public function __construct(
        QuranSessionSchedulingService $schedulingService,
        QuranReportServiceInterface $reportService
    ) {
        $this->schedulingService = $schedulingService;
        $this->reportService = $reportService;
        $this->middleware('auth');
    }

    /**
     * Display individual circles for the teacher
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();

        // Authorize viewing circles
        $this->authorize('viewAny', QuranIndividualCircle::class);

        $baseQuery = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->effectivelyActive()->count(),
            'paused' => (clone $baseQuery)->effectivelyPaused()->count(),
            'completed' => (clone $baseQuery)->whereNotNull('completed_at')->count(),
        ];

        $query = clone $baseQuery;
        $query->with(['student', 'subscription.package', 'linkedSubscriptions'])->withCount('sessions');

        if ($request->status) {
            match ($request->status) {
                'active' => $query->effectivelyActive(),
                'paused' => $query->effectivelyPaused(),
                'completed' => $query->whereNotNull('completed_at'),
                default => null,
            };
        }
        if ($request->filled('search')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        return view('teacher.individual-circles.index', compact('circles', 'stats'));
    }

    /**
     * Show individual circle details
     */
    public function show($subdomain, $circle): View
    {
        $user = Auth::user();

        // Resolve academy from subdomain
        $tenantAcademy = Academy::where('subdomain', $subdomain)->first();
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

        // Authorize viewing the circle
        $this->authorize('view', $circleModel);

        if ($user->user_type === UserType::QURAN_TEACHER->value && (int) $circleModel->quran_teacher_id === (int) $user->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === UserType::STUDENT->value && (int) $circleModel->student_id === (int) $user->id) {
            $userRole = 'student';
            $isStudent = true;
        }

        $circleModel->load([
            'student',
            'subscription.package',
            'linkedSubscriptions',
            'quranTeacher',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'scheduledSessions' => function ($query) {
                $query->active()->orderBy('scheduled_at');
            },
            'completedSessions' => function ($query) {
                $query->where('status', SessionStatus::COMPLETED->value)->orderBy('ended_at', 'desc');
            },
        ]);

        $upcomingSessions = $circleModel->sessions()
            ->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::ONGOING->value,
                SessionStatus::UNSCHEDULED->value,
                SessionStatus::READY->value,
            ])
            ->where(function ($query) {
                $query->where('scheduled_at', '>', now())
                    ->orWhereNull('scheduled_at') // Include unscheduled sessions
                    ->orWhereIn('status', [SessionStatus::ONGOING->value, SessionStatus::READY->value]); // Include ongoing and ready sessions regardless of time
            })
            ->orderByRaw('scheduled_at IS NULL') // Put scheduled sessions first
            ->orderBy('scheduled_at')
            ->orderBy('id') // Secondary sort for consistent ordering
            ->get();

        $pastSessions = $circleModel->sessions()
            ->final()
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
    public function getAvailableTimeSlots(GetAvailableTimeSlotsRequest $request, $circle): JsonResponse
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== UserType::QURAN_TEACHER->value || $circleModel->quran_teacher_id !== $user->id) {
            return $this->forbidden('غير مسموح');
        }

        $date = Carbon::parse($request->date);
        $duration = $request->duration ?? $circleModel->default_duration_minutes;

        $availableSlots = $this->schedulingService->getAvailableTimeSlots(
            $user->id,
            $date,
            $duration
        );

        return $this->success([
            'success' => true,
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Update circle settings
     */
    public function updateSettings(UpdateIndividualCircleSettingsRequest $request, $circle): JsonResponse
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== UserType::QURAN_TEACHER->value || $circleModel->quran_teacher_id !== $user->id) {
            return $this->forbidden('غير مسموح');
        }

        try {
            $circleModel->update($request->only([
                'default_duration_minutes',
                'preferred_times',
                'meeting_link',
                'meeting_id',
                'recording_enabled',
                'notes',
            ]));

            return $this->success(null, 'تم تحديث إعدادات الحلقة بنجاح');

        } catch (Exception $e) {
            return $this->serverError('حدث خطأ في تحديث الإعدادات: '.$e->getMessage());
        }
    }
}
