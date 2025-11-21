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
         class="absolute right-auto left-0 z-[100] mt-4 w-96 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
         style="transform-origin: top left;">

        {{-- Header --}}
        <div class="bg-blue-50 px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('الإشعارات') }}</h3>
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <button wire:click="markAllAsRead"
                            type="button"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        {{ __('notifications.actions.mark_all_as_read') }}
                    </button>
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
        <div class="max-h-96 overflow-y-auto"
             x-data="{ loading: false }"
             @scroll.debounce.150ms="
                if (($el.scrollHeight - $el.scrollTop - $el.clientHeight) < 100 && !loading && {{ $hasMore ? 'true' : 'false' }}) {
                    loading = true;
                    $wire.loadMore().then(() => { loading = false; });
                }
             ">
            @forelse($notifications as $notification)
                @php
                    $data = json_decode($notification->data, true);
                    $metadata = json_decode($notification->metadata, true) ?? [];
                    $isUnclicked = !$notification->read_at; // Not clicked yet (highlighted)

                    // Map category to colors
                    $categoryColors = [
                        'session' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                        'attendance' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
                        'homework' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
                        'payment' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
                        'meeting' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                        'progress' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
                        'chat' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
                        'system' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
                    ];
                    $colors = $categoryColors[$notification->category] ?? $categoryColors['system'];
                @endphp

                <div class="{{ $isUnclicked ? 'bg-blue-50' : 'bg-white' }}">
                    {{-- Clickable notification item --}}
                    @if($notification->action_url)
                        <a href="{{ $notification->action_url }}"
                           wire:click="markAsRead('{{ $notification->id }}')"
                           class="block px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100">
                    @else
                        <div class="px-4 py-3 border-b border-gray-100">
                    @endif
                        <div class="flex items-start space-x-3 rtl:space-x-reverse">
                            {{-- Icon with fixed colors --}}
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $colors['bg'] }} {{ $colors['text'] }}">
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
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $data['title'] ?? '' }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                    {{ $data['message'] ?? '' }}
                                </p>
                                <p class="mt-1.5 text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @if($notification->action_url)
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">{{ __('notifications.empty.message') }}</p>
                </div>
            @endforelse

            {{-- Loading indicator for infinite scroll --}}
            <div x-show="loading || ({{ $hasMore ? 'true' : 'false' }} && {{ count($notifications) }} > 0)"
                 x-cloak
                 class="px-4 py-3 text-center border-t border-gray-100">
                <div class="inline-flex items-center text-xs text-gray-500">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('جاري التحميل...') }}</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            <a href="{{ route('notifications.index', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
               class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors py-1 hover:bg-blue-50 rounded">
                {{ __('عرض كل الإشعارات') }}
            </a>
        </div>
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

    /* Scrollbar styles for notifications list */
    .max-h-96::-webkit-scrollbar {
        width: 6px;
    }
    .max-h-96::-webkit-scrollbar-track {
        background: #f9fafb;
    }
    .max-h-96::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }
    .max-h-96::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
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