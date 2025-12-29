<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Services\ParentDashboardService;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Parent Dashboard Controller
 *
 * Handles parent dashboard, child selection, and child detail views.
 * Uses child-switching pattern (stores active child ID in session).
 */
class ParentDashboardController extends Controller
{
    use ApiResponses;
    protected ParentDashboardService $dashboardService;
    protected ParentDataService $dataService;

    public function __construct(
        ParentDashboardService $dashboardService,
        ParentDataService $dataService
    ) {
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;

        // Enforce read-only access for parents
        $this->middleware('parent.readonly');
    }

    /**
     * Dashboard with all children cards + family stats
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewDashboard', \App\Models\ParentProfile::class);

        $user = Auth::user();
        $parent = $user->parentProfile;

        $dashboardData = $this->dashboardService->getDashboardData($parent);

        return view('parent.dashboard', [
            'parent' => $parent,
            'children' => $dashboardData['children'],
            'stats' => $dashboardData['stats'],
            'upcomingSessions' => $dashboardData['upcoming_sessions'],
            'recentActivity' => $dashboardData['recent_activity'],
        ]);
    }

    /**
     * Set active child in session, redirect to child detail
     *
     * @param Request $request
     * @param int $childId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function selectChild(Request $request, int $childId): RedirectResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Find child and authorize access
        $child = \App\Models\StudentProfile::findOrFail($childId);
        $this->authorize('viewChild', [$parent, $child]);

        // Store in session
        session(['active_child_id' => $childId]);

        return redirect()->route('parent.child.detail')
            ->with('success', 'تم اختيار الطالب: ' . $child->user->name);
    }

    /**
     * Show active child's full data
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function childDetail(Request $request): View
    {
        $this->authorize('viewDashboard', \App\Models\ParentProfile::class);

        $user = Auth::user();
        $parent = $user->parentProfile;
        $child = $this->getActiveChild();

        // Get child's full data
        $childData = $this->dataService->getChildData($parent, $child->id);
        $subscriptions = $this->dataService->getChildSubscriptions($parent, $child->id);
        $upcomingSessions = $this->dataService->getChildUpcomingSessions($parent, $child->id);
        $progressReport = $this->dataService->getChildProgressReport($parent, $child->id);

        return view('parent.child-detail', [
            'parent' => $parent,
            'child' => $child,
            'childData' => $childData,
            'subscriptions' => $subscriptions,
            'upcomingSessions' => $upcomingSessions,
            'progressReport' => $progressReport,
        ]);
    }

    /**
     * Select child via session (AJAX endpoint for top bar selector)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectChildSession(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard', \App\Models\ParentProfile::class);

        $user = Auth::user();
        $parent = $user->parentProfile;
        $childId = $request->input('child_id', 'all');

        // If selecting 'all', just store it
        if ($childId === 'all') {
            session(['parent_selected_child_id' => 'all']);
            return $this->customResponse([
                'message' => 'تم اختيار جميع الأبناء',
                'child_id' => 'all',
            ]);
        }

        // Find child and authorize access
        $child = \App\Models\StudentProfile::findOrFail($childId);
        $this->authorize('viewChild', [$parent, $child]);

        // Store in session
        session(['parent_selected_child_id' => $childId]);

        return $this->customResponse([
            'message' => 'تم اختيار: ' . ($child->user->name ?? $child->first_name),
            'child_id' => $childId,
            'child_name' => $child->user->name ?? $child->first_name,
        ]);
    }

    /**
     * Switch active child (AJAX endpoint)
     *
     * @param Request $request
     * @param int $childId
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchChild(Request $request, int $childId): JsonResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Find child and authorize access
        $child = \App\Models\StudentProfile::findOrFail($childId);
        $this->authorize('viewChild', [$parent, $child]);

        // Store in session
        session(['active_child_id' => $childId]);

        return $this->customResponse([
            'message' => 'تم التبديل إلى: ' . $child->user->name,
            'child_name' => $child->user->name,
        ]);
    }

    /**
     * Helper method: Get active child from session
     *
     * @return \App\Models\StudentProfile
     */
    protected function getActiveChild(): \App\Models\StudentProfile
    {
        $childId = session('active_child_id');

        if (!$childId) {
            abort(400, 'الرجاء اختيار طالب أولاً');
        }

        $user = Auth::user();
        $parent = $user->parentProfile;

        $child = $parent->students()
            ->where('student_profiles.id', $childId)
            ->forAcademy($parent->academy_id)
            ->first();

        if (!$child) {
            session()->forget('active_child_id');
            abort(400, 'الطالب غير موجود');
        }

        $this->authorize('viewChild', [$parent, $child]);

        return $child;
    }
}
