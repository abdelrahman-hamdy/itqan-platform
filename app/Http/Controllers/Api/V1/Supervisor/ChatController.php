<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ChatGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Supervisor-specific chat endpoints
 */
class ChatController extends Controller
{
    use ApiResponses;

    /**
     * Get supervised chat groups for the supervisor
     */
    public function getSupervisedGroups(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSupervisor()) {
            return $this->error(
                __('Access denied. Supervisor account required.'),
                403,
                'FORBIDDEN'
            );
        }

        $groups = ChatGroup::where('supervisor_id', $user->supervisorProfile?->id)
            ->with([
                'conversation.lastMessage',
                'members.user',
            ])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'groups' => collect($groups->items())->map(function ($group) use ($user) {
                // Get teacher and student from members
                $members = $group->members;
                $teacher = $members->firstWhere('role', 'admin')?->user;
                $student = $members->firstWhere('role', 'member')?->user;

                // Get unread count from conversation
                $unreadCount = $group->conversation?->unreadMessagesCount($user) ?? 0;

                return [
                    'id' => $group->id,
                    'type' => $group->type,
                    'name' => $group->name,
                    'teacher' => $teacher ? [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ] : null,
                    'student' => $student ? [
                        'id' => $student->id,
                        'name' => $student->name,
                    ] : null,
                    'last_message' => $group->conversation?->lastMessage ? [
                        'id' => $group->conversation->lastMessage->id,
                        'body' => $group->conversation->lastMessage->body,
                        'sender_name' => $group->conversation->lastMessage->sendable?->name,
                        'created_at' => $group->conversation->lastMessage->created_at->toISOString(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'metadata' => $group->metadata ?? [],
                    'created_at' => $group->created_at->toISOString(),
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($groups),
        ], __('Supervised groups retrieved successfully'));
    }

    /**
     * Get members of a supervised group
     */
    public function getGroupMembers(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSupervisor()) {
            return $this->error(
                __('Access denied. Supervisor account required.'),
                403,
                'FORBIDDEN'
            );
        }

        $group = ChatGroup::where('id', $id)
            ->where('supervisor_id', $user->supervisorProfile?->id)
            ->with(['members.user'])
            ->first();

        if (! $group) {
            return $this->notFound(__('Group not found or access denied.'));
        }

        return $this->success([
            'group' => [
                'id' => $group->id,
                'type' => $group->type,
                'name' => $group->name,
            ],
            'members' => $group->members->map(fn ($member) => [
                'id' => $member->user_id,
                'name' => $member->user?->name,
                'role' => $member->role,
                'can_send_messages' => $member->can_send_messages,
                'joined_at' => $member->joined_at?->toISOString(),
            ])->toArray(),
        ], __('Group members retrieved successfully'));
    }
}
