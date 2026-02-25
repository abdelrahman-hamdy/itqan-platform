<?php

namespace App\Livewire;

use App\Enums\NotificationCategory;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property Collection $notifications
 * @property array $categories
 */
class NotificationCenter extends Component
{
    public $selectedCategory = null;

    public $unreadCount = 0;

    public $perPage = 15;

    public $hasMore = true;

    public function mount()
    {
        $this->loadUnreadCount();
    }

    public function loadUnreadCount()
    {
        if (! auth()->check()) {
            $this->unreadCount = 0;

            return;
        }

        $this->unreadCount = app(NotificationService::class)->getUnreadCount(auth()->user());
    }

    public function toggleNotificationPanel()
    {
        if (! auth()->check()) {
            return;
        }

        // Mark all notifications as panel-opened (seen but not clicked)
        app(NotificationService::class)->markAllAsPanelOpened(auth()->user());

        // Panel state is managed in Alpine.js now
        $this->dispatch('notification-panel-opened');
        $this->loadUnreadCount();
    }

    public function filterByCategory($category = null)
    {
        if ($category !== null) {
            $validValues = array_column(NotificationCategory::cases(), 'value');
            if (! in_array($category, $validValues, true)) {
                return;
            }
        }
        $this->selectedCategory = $category;
        $this->perPage = 15;
    }

    public function loadMore()
    {
        if ($this->hasMore) {
            $this->perPage += 15;
        }
    }

    public function markAsRead($notificationId)
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationService::class)->markAsRead($notificationId, auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('notification-read', ['id' => $notificationId]);
    }

    public function markAllAsRead()
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationService::class)->markAllAsRead(auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('all-notifications-read');
    }

    public function deleteNotification($notificationId)
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationService::class)->delete($notificationId, auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('notification-deleted', ['id' => $notificationId]);
    }

    #[On('notification.sent')]
    public function handleNotificationEvent($event = null)
    {
        $this->loadUnreadCount();

        // If event data is provided, show browser notification
        if ($event && isset($event['notification'])) {
            $this->dispatch('new-notification-received', $event['notification']);
            $this->dispatch('show-browser-notification', $event['notification']);
        }
    }

    public function handleNewNotification($event)
    {
        $this->handleNotificationEvent($event);
    }

    public function refreshNotifications()
    {
        $this->loadUnreadCount();
        $this->render();
    }

    public function getNotificationsProperty()
    {
        if (! auth()->check()) {
            $this->hasMore = false;

            return collect();
        }

        $query = DB::table('notifications')
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', get_class(auth()->user()))
            ->where('tenant_id', auth()->user()->academy_id);

        // Validate selectedCategory against allowed enum values before using in query
        $validCategory = null;
        if ($this->selectedCategory !== null) {
            $validValues = array_column(NotificationCategory::cases(), 'value');
            $validCategory = in_array($this->selectedCategory, $validValues, true) ? $this->selectedCategory : null;
        }

        if ($validCategory) {
            $query->where('category', $validCategory);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($this->perPage)
            ->get();

        // Check if there are more notifications
        $totalCount = DB::table('notifications')
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', get_class(auth()->user()))
            ->where('tenant_id', auth()->user()->academy_id)
            ->when($validCategory, fn ($q) => $q->where('category', $validCategory))
            ->count();

        $this->hasMore = $notifications->count() < $totalCount;

        return $notifications;
    }

    public function getCategoriesProperty()
    {
        return NotificationCategory::cases();
    }

    public function render()
    {
        return view('livewire.notification-center', [
            'notifications' => $this->notifications,
            'categories' => $this->categories,
        ]);
    }
}
