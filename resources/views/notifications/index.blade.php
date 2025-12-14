@php
    // Determine layout based on user role
    $isParent = auth()->user()->role === 'parent' || auth()->user()->user_type === 'parent';
    $layoutComponent = $isParent ? 'layouts.parent-layout' : 'layouts.student';
    $pageTitle = 'الإشعارات - ' . config('app.name', 'منصة إتقان');
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$pageTitle">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            @php
                $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
                $dashboardRoute = $isParent
                    ? route('parent.profile', ['subdomain' => $subdomain])
                    : route('student.profile', ['subdomain' => $subdomain]);
            @endphp
            <li><a href="{{ $dashboardRoute }}" class="hover:text-primary">الرئيسية</a></li>
            <li>/</li>
            <li class="text-gray-900">الإشعارات</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">الإشعارات</h1>
        <p class="mt-1 text-sm text-gray-600">تتبع جميع إشعاراتك وتحديثاتك</p>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('notifications.index', ['subdomain' => request()->route('subdomain')]) }}">
            <div class="space-y-4">
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">التصنيف</label>
                    <div class="flex items-center gap-2 overflow-x-auto pb-2">
                        <a href="{{ route('notifications.index', ['subdomain' => request()->route('subdomain'), 'unread' => request()->get('unread')]) }}"
                           class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all {{ !$selectedCategory ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            الكل
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ route('notifications.index', ['subdomain' => request()->route('subdomain'), 'category' => $category->value, 'unread' => request()->get('unread')]) }}"
                               class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all {{ $selectedCategory === $category->value ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                {{ $category->getLabel() }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Actions Row -->
                <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                    <a href="{{ route('notifications.index', ['subdomain' => request()->route('subdomain'), 'category' => $selectedCategory, 'unread' => !$onlyUnread ? '1' : '0']) }}"
                       class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all {{ $onlyUnread ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        غير المقروءة فقط
                    </a>

                    <button type="button"
                            onclick="markAllAsRead()"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        تعليم الكل كمقروء
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $notification)
            @php
                $data = json_decode($notification->data, true);
                $metadata = json_decode($notification->metadata, true) ?? [];
                $isUnclicked = !$notification->read_at;

                // Map category to colors
                $categoryColors = [
                    'session' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-200'],
                    'attendance' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-200'],
                    'homework' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'border' => 'border-amber-200'],
                    'payment' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200'],
                    'meeting' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-200'],
                    'progress' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200'],
                    'system' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-200'],
                ];
                $colors = $categoryColors[$notification->category] ?? $categoryColors['system'];
            @endphp

            <div class="relative group bg-white rounded-xl shadow-sm border {{ $isUnclicked ? 'border-blue-300 bg-blue-50/30' : 'border-gray-200' }} hover:shadow-md transition-all">
                {{-- Clickable notification item --}}
                @if($notification->action_url)
                    <a href="{{ $notification->action_url }}"
                       onclick="markAsRead('{{ $notification->id }}')"
                       class="block p-6">
                @else
                    <div class="p-6">
                @endif
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $colors['bg'] }} {{ $colors['text'] }} border {{ $colors['border'] }}">
                                @if($notification->icon)
                                    <x-dynamic-component :component="$notification->icon" class="w-6 h-6" />
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4 mb-2">
                                <h3 class="text-base font-bold text-gray-900">
                                    {{ $data['title'] ?? '' }}
                                </h3>

                                {{-- Unread indicator --}}
                                @if($isUnclicked)
                                    <div class="flex-shrink-0">
                                        <div class="w-2.5 h-2.5 bg-blue-600 rounded-full"></div>
                                    </div>
                                @endif
                            </div>

                            <p class="text-sm text-gray-600 leading-relaxed mb-3">
                                {{ $data['message'] ?? '' }}
                            </p>

                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                </span>
                                @if($notification->category)
                                    <span class="px-2 py-1 {{ $colors['bg'] }} {{ $colors['text'] }} rounded-md font-medium">
                                        {{ \App\Enums\NotificationCategory::tryFrom($notification->category)?->getLabel() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @if($notification->action_url)
                    </a>
                @else
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد إشعارات</h3>
                <p class="text-sm text-gray-600">لم يتم العثور على إشعارات تطابق المرشحات المحددة.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="mt-8">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/api/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    }).catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    fetch('/api/notifications/mark-all-as-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endpush

</x-dynamic-component>
