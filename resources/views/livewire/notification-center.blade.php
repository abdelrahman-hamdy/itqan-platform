<div class="relative" x-data="{ open: false }">
    {{-- Notification Bell Icon --}}
    <button @click="open = !open"
            wire:click="toggleNotificationPanel"
            class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200"
            aria-label="الإشعارات">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>

        {{-- Unread Count Badge --}}
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Notification Panel --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="open = false"
         class="absolute right-auto left-0 z-[100] mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
         style="transform-origin: top left;">

        {{-- Header --}}
        <div class="bg-blue-50 px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('الإشعارات') }}</h3>
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    @if($unreadCount > 0)
                        <button wire:click="markAllAsRead"
                                type="button"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                            {{ __('notifications.actions.mark_all_as_read') }}
                        </button>
                    @endif
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Category Filter --}}
        <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                <button wire:click.stop="filterByCategory(null)"
                        type="button"
                        class="px-3 py-1 text-sm rounded-full whitespace-nowrap transition-colors {{ !$selectedCategory ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                    {{ __('الكل') }}
                </button>
                @foreach($categories as $category)
                    <button wire:click.stop='filterByCategory("{{ $category->value }}")'
                            type="button"
                            class="px-3 py-1 text-sm rounded-full whitespace-nowrap transition-colors {{ $selectedCategory === $category->value ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                        {{ $category->getLabel() }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                @php
                    $data = json_decode($notification->data, true);
                    $metadata = json_decode($notification->metadata, true) ?? [];
                    $isUnread = !$notification->read_at;
                @endphp

                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 {{ $isUnread ? 'bg-blue-50' : '' }}">
                    <div class="flex items-start space-x-3 rtl:space-x-reverse">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $notification->icon_color ? 'bg-'.$notification->icon_color.'-100 text-'.$notification->icon_color.'-600' : 'bg-gray-100 text-gray-600' }}">
                                @if($notification->icon)
                                    <x-dynamic-component :component="$notification->icon" class="w-5 h-5" />
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $data['title'] ?? '' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $data['message'] ?? '' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                    </p>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center space-x-2 rtl:space-x-reverse ml-2">
                                    @if($notification->action_url)
                                        <a href="{{ $notification->action_url }}"
                                           class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    @endif

                                    @if($isUnread)
                                        <button wire:click="markAsRead('{{ $notification->id }}')"
                                                type="button"
                                                class="text-green-600 hover:text-green-800 p-1"
                                                title="{{ __('notifications.actions.mark_as_read') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                    @endif

                                    <button wire:click="deleteNotification('{{ $notification->id }}')"
                                            type="button"
                                            class="text-red-600 hover:text-red-800 p-1"
                                            title="{{ __('notifications.actions.delete') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">{{ __('notifications.empty.message') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Footer with Pagination --}}
        @if($notifications->hasPages())
            <div class="px-4 py-2 border-t border-gray-200 bg-gray-50">
                {{ $notifications->links('pagination::simple-tailwind') }}
            </div>
        @endif
    </div>

</div>

@push('styles')
<style>
    /* Scrollbar styles for notification center category filter */
    .scrollbar-thin::-webkit-scrollbar {
        height: 4px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 2px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Listen for browser notification event
        if (typeof Livewire !== 'undefined') {
            Livewire.on('show-browser-notification', (notification) => {
                if ('Notification' in window && Notification.permission === 'granted') {
                    const data = notification.data || {};
                    const notif = new Notification(data.title || 'New Notification', {
                        body: data.message || '',
                        icon: '/logo.png',
                        badge: '/badge.png',
                        tag: 'notification-' + Date.now(),
                        requireInteraction: notification.is_important || false,
                    });

                    // Handle notification click
                    notif.onclick = function() {
                        if (notification.action_url) {
                            window.open(notification.action_url, '_blank');
                        }
                        notif.close();
                    };
                }
            });
        }

        // Subscribe to Echo channel for real-time notifications
        @if(auth()->check())
            if (typeof Echo !== 'undefined') {
                Echo.private('user.{{ auth()->id() }}')
                    .listen('.notification.sent', (e) => {
                        // Dispatch Livewire event to refresh notifications
                        Livewire.dispatch('notification.sent', e);
                    });
            }
        @endif
    });
</script>
@endpush