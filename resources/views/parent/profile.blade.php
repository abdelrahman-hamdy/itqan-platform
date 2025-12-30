@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.profile.title')">
    <div class="space-y-6">

        <!-- Welcome Section -->
        <div class="mb-4 md:mb-8">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
                    {{ __('parent.profile.welcome', ['name' => $parent->first_name ?? $user->name]) }}
                </h1>
                <p class="text-sm md:text-base text-gray-600">
                    @if($selectedChild ?? false)
                        {{ __('parent.profile.tracking_child', ['child' => $selectedChild->user->name ?? $selectedChild->first_name]) }}
                    @else
                        {{ __('parent.profile.tracking_all') }}
                    @endif
                </p>
            </div>
        </div>

        <!-- Quick Stats -->
        @include('components.stats.parent-quick-stats', ['stats' => $stats, 'selectedChild' => $selectedChild ?? null])

        <!-- Children Overview Cards -->
        @if($children->count() > 0)
            <div class="mb-4 md:mb-8">
                <div class="flex items-center justify-between mb-4 md:mb-6">
                    <h2 class="text-lg md:text-2xl font-bold text-gray-900">
                        <i class="ri-team-line text-purple-600 ms-1.5 md:ms-2"></i>
                        {{ __('parent.profile.registered_children_title') }}
                    </h2>
                    <span class="text-xs md:text-sm text-gray-500">{{ $children->count() }} {{ $children->count() > 1 ? __('parent.profile.child_count_plural') : __('parent.profile.child_count_singular') }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($children as $child)
                        <x-parent.child-overview-card :child="$child" />
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Upcoming Sessions Section -->
        <div class="mb-4 md:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4 mb-4 md:mb-6">
                <h2 class="text-lg md:text-2xl font-bold text-gray-900">
                    <i class="ri-calendar-event-line text-blue-600 ms-1.5 md:ms-2"></i>
                    {{ __('parent.dashboard.upcoming_sessions_title') }}
                </h2>
                <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}"
                   class="min-h-[44px] inline-flex items-center text-blue-600 hover:text-blue-700 text-sm font-medium transition-colors">
                    {{ __('parent.dashboard.view_full_calendar') }}
                    <i class="ri-arrow-left-s-line me-1"></i>
                </a>
            </div>
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if(!empty($upcomingSessions) && count($upcomingSessions) > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach(collect($upcomingSessions)->take(5) as $session)
                            @php
                                // Determine status badge
                                $statusText = match($session['status'] ?? 'scheduled') {
                                    'ready' => __('parent.dashboard.session_status.ready'),
                                    'live' => __('parent.dashboard.session_status.live'),
                                    'scheduled' => __('parent.dashboard.session_status.scheduled'),
                                    'pending' => __('parent.dashboard.session_status.pending'),
                                    default => __('parent.dashboard.session_status.scheduled')
                                };

                                $statusColor = match($session['status'] ?? 'scheduled') {
                                    'ready' => 'bg-green-100 text-green-700',
                                    'live' => 'bg-red-100 text-red-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-3 md:p-4">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="flex-shrink-0 w-10 h-10 md:w-12 md:h-12 rounded-lg md:rounded-xl {{ $session['type'] === 'quran' ? 'bg-green-100' : 'bg-violet-100' }} flex items-center justify-center">
                                        <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-green-600' : 'ri-book-line text-violet-600' }} text-lg md:text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5 md:gap-2 mb-0.5 md:mb-1">
                                            <p class="font-medium text-gray-900 text-sm md:text-base truncate">{{ $session['title'] }}</p>
                                            <span class="px-1.5 md:px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium {{ $statusColor }}">{{ $statusText }}</span>
                                        </div>
                                        <p class="text-xs md:text-sm text-gray-500 truncate">
                                            <i class="ri-user-line ms-1"></i>{{ $session['teacher_name'] }}
                                            <span class="hidden sm:inline">•</span>
                                            <span class="block sm:inline"><i class="ri-team-line ms-1"></i>{{ $session['child_name'] }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0 pe-12 sm:pe-0">
                                    <p class="text-xs md:text-sm font-medium text-gray-600">{{ $session['scheduled_at']->format('d/m/Y') }}</p>
                                    <p class="text-[10px] md:text-xs text-gray-500">
                                        <i class="ri-time-line ms-1"></i>{{ $session['scheduled_at']->format('h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 md:p-8 text-center">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                            <i class="ri-calendar-line text-gray-400 text-xl md:text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1 text-sm md:text-base">{{ __('parent.dashboard.no_upcoming_sessions') }}</h3>
                        <p class="text-xs md:text-sm text-gray-500">{{ __('parent.dashboard.no_sessions_description') }}</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-layouts.parent-layout>
