<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Parent Session Controller
 *
 * Handles viewing of child sessions (upcoming, history, details).
 * Supports filtering by child using query parameters.
 */
class ParentSessionController extends Controller
{
    protected ParentDataService $dataService;

    public function __construct(ParentDataService $dataService)
    {
        $this->dataService = $dataService;

        // Enforce read-only access
        $this->middleware(function ($request, $next) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                abort(403, 'أولياء الأمور لديهم صلاحيات مشاهدة فقط');
            }
            return $next($request);
        });
    }

    /**
     * List upcoming sessions - redirects to calendar
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upcoming(Request $request)
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
    public function history(Request $request)
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
    public function show(Request $request, string $sessionType, string|int $sessionId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        if ($sessionType === 'quran') {
            $session = QuranSession::with(['quranTeacher', 'student', 'individualCircle', 'circle', 'attendances'])
                ->findOrFail($sessionId);
        } elseif ($sessionType === 'academic') {
            $session = AcademicSession::with(['academicTeacher.user', 'student', 'academicIndividualLesson', 'attendances'])
                ->findOrFail($sessionId);
        } else {
            abort(404, 'نوع الجلسة غير صحيح');
        }

        // Verify session belongs to one of parent's children
        $hasAccess = false;

        // Check individual sessions (student_id is set)
        if ($session->student_id && in_array($session->student_id, $childUserIds)) {
            $hasAccess = true;
        }

        // Check group circle sessions (circle_id is set)
        if (!$hasAccess && $sessionType === 'quran' && $session->circle_id) {
            $circleStudentIds = $session->circle->students()->pluck('quran_circle_students.student_id')->toArray();
            $hasAccess = !empty(array_intersect($childUserIds, $circleStudentIds));
        }

        if (!$hasAccess) {
            abort(403, 'لا يمكنك الوصول إلى هذه الجلسة');
        }

        // Get attendance for this session
        $attendance = $session->attendances->first();

        // Calculate stats for the child
        // For group sessions, get the first enrolled child from parent's children
        $studentId = $session->student_id;
        if (!$studentId && $sessionType === 'quran' && $session->circle_id) {
            // Get first child enrolled in this circle
            $studentId = $session->circle->students()
                ->whereIn('quran_circle_students.student_id', $childUserIds)
                ->first()
                ?->id;
        }

        // Initialize default values
        $totalSessions = 0;
        $completedSessions = 0;

        if ($sessionType === 'quran' && $studentId) {
            $totalSessions = QuranSession::where('student_id', $studentId)
                ->where('academy_id', $parent->academy_id)
                ->count();
            $completedSessions = QuranSession::where('student_id', $studentId)
                ->where('academy_id', $parent->academy_id)
                ->where('status', 'completed')
                ->count();
        } elseif ($sessionType === 'academic' && $studentId) {
            $totalSessions = AcademicSession::where('student_id', $studentId)
                ->where('academy_id', $parent->academy_id)
                ->count();
            $completedSessions = AcademicSession::where('student_id', $studentId)
                ->where('academy_id', $parent->academy_id)
                ->where('status', 'completed')
                ->count();
        }

        $attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

        $stats = [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'attendance_rate' => $attendanceRate,
        ];

        return view('parent.sessions.show', [
            'parent' => $parent,
            'children' => $children,
            'session' => $session,
            'sessionType' => $sessionType,
            'attendance' => $attendance,
            'stats' => $stats,
        ]);
    }

    /**
     * Helper: Get user IDs for children based on filter
     */
    protected function getChildUserIds($children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('user_id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->user_id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('user_id')->toArray();
    }
}
