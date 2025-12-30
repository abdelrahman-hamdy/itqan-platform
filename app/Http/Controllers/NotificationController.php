<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Enums\NotificationCategory;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\SessionStatus;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use ApiResponses;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * Display all notifications for the authenticated user
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Mark all as panel-opened when visiting the page
        $this->notificationService->markAllAsPanelOpened($user);

        // Build query
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('tenant_id', $user->academy_id);

        // Apply category filter
        $selectedCategory = $request->get('category');
        if ($selectedCategory) {
            $query->where('category', $selectedCategory);
        }

        // Apply unread filter
        $onlyUnread = $request->boolean('unread');
        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        // Get paginated notifications
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get all categories for filter
        $categories = NotificationCategory::cases();

        return view('notifications.index', compact(
            'notifications',
            'categories',
            'selectedCategory',
            'onlyUnread'
        ));
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markAsRead($id, auth()->user());

        return $this->success();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return $this->success([
            'count' => $count
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $this->notificationService->delete($id, auth()->user());

        if ($deleted) {
            return $this->success();
        }

        return $this->notFound('Notification not found');
    }
}
