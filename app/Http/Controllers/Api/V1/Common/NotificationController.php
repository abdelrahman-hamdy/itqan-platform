<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ApiResponses;

    /**
     * Get all notifications for the user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadOnly = $request->boolean('unread_only', false);
        $category = $request->get('category');
        $isRead = $request->get('is_read');

        $query = $user->notifications();

        if ($unreadOnly) {
            $query = $user->unreadNotifications();
        }

        // Filter by read status if specified
        if ($isRead !== null) {
            if ($isRead === 'true' || $isRead === true) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Filter by category if specified
        if ($category) {
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                    ->orWhereJsonContains('data->category', $category);
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'notifications' => collect($notifications->items())->map(function ($notification) {
                $metadata = $notification->metadata ?? $notification->data['metadata'] ?? null;

                // Ensure metadata is an object/array, not a JSON string
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true);
                }

                // Convert empty array to null, or ensure it's an associative array (object in JSON)
                if (is_array($metadata) && empty($metadata)) {
                    $metadata = null;
                } elseif (is_array($metadata) && array_values($metadata) === $metadata) {
                    // It's a sequential array, convert to empty object or null
                    $metadata = null;
                }

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'notification_type' => $notification->data['notification_type'] ?? $notification->notification_type ?? null,
                    'category' => $notification->data['category'] ?? $notification->category ?? 'system',
                    'title' => $notification->data['title'] ?? null,
                    'message' => $notification->data['message'] ?? null,
                    'data' => $notification->data,
                    'read' => $notification->read_at !== null,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at->toISOString(),
                    'action_url' => $notification->data['action_url'] ?? $notification->action_url ?? null,
                    'is_important' => (bool) ($notification->is_important ?? $notification->data['is_important'] ?? false),
                    'metadata' => $metadata,
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($notifications),
        ], __('Notifications retrieved successfully'));
    }

    /**
     * Get unread notification count.
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
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (! $notification) {
            return $this->notFound(__('Notification not found.'));
        }

        $notification->markAsRead();

        return $this->success([
            'marked' => true,
        ], __('Notification marked as read'));
    }

    /**
     * Mark all notifications as read.
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
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (! $notification) {
            return $this->notFound(__('Notification not found.'));
        }

        $notification->delete();

        return $this->success([
            'deleted' => true,
        ], __('Notification deleted'));
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()->delete();

        return $this->success([
            'cleared' => true,
        ], __('All notifications cleared'));
    }

    /**
     * Register or update a device token for push notifications.
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|max:500',
            'platform' => 'required|string|in:android,ios',
            'device_name' => 'sometimes|string|max:255',
        ]);

        $user = $request->user();

        try {
            $deviceToken = DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $request->input('token'),
                ],
                [
                    'platform' => $request->input('platform'),
                    'device_name' => $request->input('device_name'),
                    'last_used_at' => now(),
                ]
            );

            Log::info('Device token registered', [
                'user_id' => $user->id,
                'platform' => $request->input('platform'),
                'token_id' => $deviceToken->id,
            ]);

            return $this->success([
                'registered' => true,
                'token_id' => $deviceToken->id,
            ], __('Device token registered successfully'));

        } catch (\Throwable $e) {
            Log::error('Failed to register device token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('Failed to register device token.'));
        }
    }

    /**
     * Remove a device token (typically on logout).
     */
    public function removeDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|max:500',
        ]);

        $user = $request->user();

        $deleted = DeviceToken::where('user_id', $user->id)
            ->where('token', $request->input('token'))
            ->delete();

        return $this->success([
            'removed' => $deleted > 0,
        ], $deleted > 0
            ? __('Device token removed successfully')
            : __('Device token not found')
        );
    }
}
