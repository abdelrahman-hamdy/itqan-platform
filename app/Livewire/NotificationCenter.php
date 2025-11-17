<?php

namespace App\Livewire;

use App\Enums\NotificationCategory;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    public $selectedCategory = null;
    public $unreadCount = 0;

    protected $listeners = [
        'notification.sent' => 'handleNotificationEvent',
    ];

    public function mount()
    {
        $this->loadUnreadCount();
    }

    public function loadUnreadCount()
    {
        $this->unreadCount = app(NotificationService::class)->getUnreadCount(auth()->user());
    }

    public function toggleNotificationPanel()
    {
        // Panel state is managed in Alpine.js now
        $this->dispatch('notification-panel-opened');
        $this->loadUnreadCount();
    }

    public function filterByCategory($category = null)
    {
        $this->selectedCategory = $category;
        $this->resetPage();
    }

    public function markAsRead($notificationId)
    {
        app(NotificationService::class)->markAsRead($notificationId, auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('notification-read', ['id' => $notificationId]);
    }

    public function markAllAsRead()
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('all-notifications-read');
    }

    public function deleteNotification($notificationId)
    {
        app(NotificationService::class)->delete($notificationId, auth()->user());
        $this->loadUnreadCount();
        $this->dispatch('notification-deleted', ['id' => $notificationId]);
    }

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
        $query = DB::table('notifications')
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', get_class(auth()->user()));

        if ($this->selectedCategory) {
            $query->where('category', $this->selectedCategory);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(15);
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