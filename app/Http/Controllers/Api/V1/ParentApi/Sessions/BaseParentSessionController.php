<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\JsonResponse;

/**
 * Base controller for parent session operations.
 *
 * Provides shared functionality for accessing and formatting
 * sessions for parent's linked children.
 */
abstract class BaseParentSessionController extends Controller
{
    use ApiResponses;

    /**
     * Get all linked children's user IDs for a parent.
     */
    protected function getChildUserIds(int $parentProfileId): array
    {
        return ParentStudentRelationship::where('parent_id', $parentProfileId)
            ->with('student.user')
            ->get()
            ->map(fn ($r) => $r->student->user?->id ?? $r->student->id)
            ->filter()
            ->toArray();
    }

    /**
     * Get all linked children for a parent.
     *
     * @param  int|null  $childId  Optional filter for specific child
     * @return \Illuminate\Support\Collection
     */
    protected function getChildren(int $parentProfileId, ?int $childId = null)
    {
        $query = ParentStudentRelationship::where('parent_id', $parentProfileId)
            ->with('student.user');

        if ($childId) {
            $query->where('student_id', $childId);
        }

        return $query->get();
    }

    /**
     * Get student user ID from student model.
     *
     * @param  mixed  $student
     */
    protected function getStudentUserId($student): ?int
    {
        return $student->user?->id ?? $student->id;
    }

    /**
     * Format base session data.
     *
     * @param  mixed  $session
     * @param  mixed  $student
     */
    protected function formatBaseSession(string $type, $session, $student): array
    {
        return [
            'id' => $session->id,
            'type' => $type,
            'child_id' => $student->id,
            'child_name' => $student->full_name,
            'status' => is_object($session->status) ? $session->status->value : $session->status,
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes ?? 60,
        ];
    }

    /**
     * Validate parent access to child.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse|null Returns error response if validation fails, null if passes
     */
    protected function validateParentAccess($request, ?int $childId = null): ?JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        if ($childId) {
            $hasAccess = ParentStudentRelationship::where('parent_id', $parentProfile->id)
                ->where('student_id', $childId)
                ->exists();

            if (! $hasAccess) {
                return $this->error(__('Child not found or access denied.'), 403, ['code' => 'CHILD_ACCESS_DENIED']);
            }
        }

        return null;
    }

    /**
     * Sort sessions array by scheduled time.
     */
    protected function sortSessions(array $sessions, bool $ascending = false): array
    {
        usort($sessions, function ($a, $b) use ($ascending) {
            $timeA = strtotime($a['scheduled_at'] ?? 0);
            $timeB = strtotime($b['scheduled_at'] ?? 0);

            return $ascending
                ? $timeA <=> $timeB
                : $timeB <=> $timeA;
        });

        return $sessions;
    }

    /**
     * Manually paginate array of sessions.
     */
    protected function paginateSessions(array $sessions, int $page = 1, int $perPage = 15): array
    {
        $total = count($sessions);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($sessions, $offset, $perPage);

        return [
            'sessions' => $items,
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ];
    }
}
