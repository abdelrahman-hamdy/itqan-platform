<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle child selection for parent users.
 *
 * This middleware:
 * - Stores child selection in session (persists across navigations)
 * - Validates that selected child belongs to the parent
 * - Shares child data with all parent views
 */
class ChildSelectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only apply to authenticated parent users
        if (! $user || ! $user->parentProfile) {
            return $next($request);
        }

        $parent = $user->parentProfile;
        $children = $parent->students()->with(['user'])->get();

        // Check if child_id is being changed via request (query param or POST)
        if ($request->has('child_id')) {
            $newChildId = $request->input('child_id');
            session(['parent_selected_child_id' => $newChildId]);
        }

        // Get current selection from session (default to 'all')
        $selectedChildId = session('parent_selected_child_id', 'all');

        // Validate that selected child belongs to this parent
        if ($selectedChildId !== 'all') {
            $childExists = $children->contains('id', $selectedChildId);
            if (! $childExists) {
                $selectedChildId = 'all';
                session(['parent_selected_child_id' => 'all']);
            }
        }

        // Get the selected child model (null if 'all')
        $selectedChild = null;
        if ($selectedChildId !== 'all') {
            $selectedChild = $children->firstWhere('id', $selectedChildId);
        }

        // Share data with all views
        view()->share('parentChildren', $children);
        view()->share('selectedChildId', $selectedChildId);
        view()->share('selectedChild', $selectedChild);

        // Also make available in request for controllers
        $request->merge([
            '_parent_children' => $children,
            '_selected_child_id' => $selectedChildId,
            '_selected_child' => $selectedChild,
        ]);

        return $next($request);
    }

    /**
     * Get child IDs based on current selection.
     *
     * Returns StudentProfile IDs.
     * Helper method that can be used in controllers.
     *
     * @return array
     */
    public static function getChildIds(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->parentProfile) {
            return [];
        }

        $parent = $user->parentProfile;
        $children = $parent->students;
        $selectedChildId = session('parent_selected_child_id', 'all');

        if ($selectedChildId === 'all') {
            return $children->pluck('id')->toArray();
        }

        // Validate the child belongs to parent
        if ($children->contains('id', $selectedChildId)) {
            return [$selectedChildId];
        }

        return $children->pluck('id')->toArray();
    }

    /**
     * Get child User IDs based on current selection.
     *
     * Returns User IDs (not StudentProfile IDs).
     * Use this for models that reference users directly (like Certificate.student_id).
     *
     * @return array
     */
    public static function getChildUserIds(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->parentProfile) {
            return [];
        }

        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        $selectedChildId = session('parent_selected_child_id', 'all');

        if ($selectedChildId === 'all') {
            return $children->pluck('user_id')->toArray();
        }

        // Validate the child belongs to parent and get user_id
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->user_id];
        }

        return $children->pluck('user_id')->toArray();
    }
}
