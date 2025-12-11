<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponses;

    /**
     * Get all notifications for the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $user->notifications();

        if ($unreadOnly) {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'notifications' => collect($notifications->items())->map(fn($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'data' => $notification->data,
                'read' => $notification->read_at !== null,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'action_url' => $notification->data['action_url'] ?? null,
            ])->toArray(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'total_pages' => $notifications->lastPage(),
                'has_more' => $notifications->hasMorePages(),
            ],
        ], __('Notifications retrieved successfully'));
    }

    /**
     * Get unread notification count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'unread_count' => $user->unreadNotifications()->count(),
        ], __('Unread count retrieved'));
    }

    /**
     * Mark a notification as read.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return $this->notFound(__('Notification not found.'));
        }

        $notification->markAsRead();

        return $this->success([
            'marked' => true,
        ], __('Notification marked as read'));
    }

    /**
     * Mark all notifications as read.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return $this->success([
            'marked' => true,
        ], __('All notifications marked as read'));
    }

    /**
     * Delete a notification.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return $this->notFound(__('Notification not found.'));
        }

        $notification->delete();

        return $this->success([
            'deleted' => true,
        ], __('Notification deleted'));
    }

    /**
     * Clear all notifications.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()->delete();

        return $this->success([
            'cleared' => true,
        ], __('All notifications cleared'));
    }
}
