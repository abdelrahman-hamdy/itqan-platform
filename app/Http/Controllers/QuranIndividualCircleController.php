<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Http\Requests\GetAvailableTimeSlotsRequest;
use App\Http\Requests\UpdateIndividualCircleSettingsRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranIndividualCircle;
use App\Services\QuranCircleReportService;
use App\Services\QuranSessionSchedulingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuranIndividualCircleController extends Controller
{
    use ApiResponses;

    private QuranSessionSchedulingService $schedulingService;

    private QuranCircleReportService $reportService;

    public function __construct(
        QuranSessionSchedulingService $schedulingService,
        QuranCircleReportService $reportService
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
    public function show($subdomain, $circle): View
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

        // Authorize viewing the circle
        $this->authorize('view', $circleModel);

        if ($user->user_type === 'quran_teacher' && (int) $circleModel->quran_teacher_id === (int) $user->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === 'student' && (int) $circleModel->student_id === (int) $user->id) {
            $userRole = 'student';
            $isStudent = true;
        }

        $circleModel->load([
            'student',
            'subscription.package',
            'quranTeacher',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'scheduledSessions' => function ($query) {
                $query->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value])->orderBy('scheduled_at');
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
            ->whereIn('status', [
                SessionStatus::COMPLETED->value,
                SessionStatus::CANCELLED->value,
                SessionStatus::ABSENT->value,
            ])
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
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
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
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return $this->forbidden('غير مسموح');
        }

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

            return $this->success(null, 'تم تحديث إعدادات الحلقة بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ في تحديث الإعدادات: '.$e->getMessage());
        }
    }

    /**
     * Generate comprehensive progress report for the circle
     */
    public function progressReport($subdomain, $circle): View
    {
        $user = Auth::user();

        // Find the circle
        $circleModel = QuranIndividualCircle::where('academy_id', $user->academy_id)
            ->findOrFail($circle);

        // Authorize viewing the circle (includes teacher ownership check)
        $this->authorize('view', $circleModel);

        // Load comprehensive data for enhanced progress tracking
        $circleModel->load([
            'student.studentProfile',
            'subscription.package',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'homework',
        ]);

        // Get comprehensive report data using the QuranCircleReportService
        $reportData = $this->reportService->getIndividualCircleReport($circleModel);

        // Extract stats for view compatibility
        $stats = $reportData['progress'];
        $stats['attendance'] = $reportData['attendance'];
        $stats['trends'] = $reportData['trends'];

        // Rename for view consistency
        $circle = $circleModel;

        return view('teacher.individual-circles.progress', compact('circle', 'stats'));
    }
}
