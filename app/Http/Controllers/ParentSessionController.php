<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasParentChildren;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\ParentDataService;
use App\Services\ParentChildVerificationService;
use App\Services\Unified\UnifiedStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Parent Session Controller
 *
 * Handles viewing of child sessions (upcoming, history, details).
 * Supports filtering by child using query parameters.
 *
 * Uses UnifiedStatisticsService for attendance calculations.
 */
class ParentSessionController extends Controller
{
    use HasParentChildren;
    public function __construct(
        protected ParentDataService $dataService,
        protected ParentChildVerificationService $verificationService,
        protected UnifiedStatisticsService $statsService
    ) {
        // Enforce read-only access
        $this->middleware('parent.readonly');
    }

    /**
     * List upcoming sessions - redirects to calendar
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upcoming(Request $request): RedirectResponse
    {
        $subdomain = $request->route('subdomain') ?? Auth::user()->academy?->subdomain ?? 'itqan-academy';

        return redirect()->route('parent.calendar.index', ['subdomain' => $subdomain]);
    }

    /**
     * List past sessions - redirects to calendar
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function history(Request $request): RedirectResponse
    {
        $subdomain = $request->route('subdomain') ?? Auth::user()->academy?->subdomain ?? 'itqan-academy';

        return redirect()->route('parent.calendar.index', ['subdomain' => $subdomain]);
    }

    /**
     * Show session details
     *
     * @param Request $request
     * @param string $sessionType
     * @param string|int $sessionId
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $sessionType, string|int $sessionId): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $this->verificationService->getChildrenWithUsers($parent);
        $childUserIds = $this->verificationService->getChildUserIds($parent);

        if ($sessionType === 'quran') {
            $session = QuranSession::with(['quranTeacher', 'student', 'individualCircle', 'circle', 'attendances'])
                ->findOrFail($sessionId);
        } elseif ($sessionType === 'academic') {
            $session = AcademicSession::with(['academicTeacher.user', 'student', 'academicIndividualLesson', 'attendances'])
                ->findOrFail($sessionId);
        } else {
            abort(404, 'نوع الجلسة غير صحيح');
        }

        $this->authorize('view', $session);

        // Verify session belongs to one of parent's children
        $this->verificationService->verifySessionBelongsToChild($parent, $session);

        // Get attendance for this session
        $attendance = $session->attendances->first();

        // Calculate stats for the child using unified service
        // For group sessions, get the first enrolled child from parent's children
        $studentId = $session->student_id;
        if (!$studentId && $sessionType === 'quran' && $session->circle_id) {
            // Get first child enrolled in this circle
            $studentId = $session->circle->students()
                ->whereIn('quran_circle_students.student_id', $childUserIds)
                ->first()
                ?->id;
        }

        // Use unified statistics service for consistent calculations
        if ($studentId) {
            $studentStats = $this->statsService->getStudentStatistics($studentId, $parent->academy_id);
            $sessionStats = $studentStats['sessions']['totals'] ?? [];

            $stats = [
                'total_sessions' => $sessionStats['total'] ?? 0,
                'completed_sessions' => $sessionStats['completed'] ?? 0,
                'attendance_rate' => $studentStats['attendance']['overall_rate'] ?? 0,
            ];
        } else {
            $stats = [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'attendance_rate' => 0,
            ];
        }

        return view('parent.sessions.show', [
            'parent' => $parent,
            'children' => $children,
            'session' => $session,
            'sessionType' => $sessionType,
            'attendance' => $attendance,
            'stats' => $stats,
        ]);
    }
}
