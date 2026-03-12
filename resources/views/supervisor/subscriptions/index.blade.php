<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('search') || request('type') || request('status');

    $filterCount = (request('search') ? 1 : 0)
        + (request('type') ? 1 : 0)
        + (request('status') ? 1 : 0);

    $currentSort = request('sort', 'newest');
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.subscriptions.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.subscriptions.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.subscriptions.page_subtitle') }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $totalActive }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_active') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-alarm-warning-line text-amber-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $expiringThisWeek }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_expiring') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-time-line text-yellow-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $totalPending }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_pending') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-pause-circle-line text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $totalPaused }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_paused') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- List Header with Sort -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.subscriptions.list_title') }} ({{ $subscriptions->total() }})
            </h2>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-sort-desc"></i>
                    <span>
                        @switch($currentSort)
                            @case('oldest') {{ __('supervisor.subscriptions.sort_oldest') }} @break
                            @case('expiring_soon') {{ __('supervisor.subscriptions.sort_expiring_soon') }} @break
                            @case('sessions_remaining') {{ __('supervisor.subscriptions.sort_sessions_remaining') }} @break
                            @default {{ __('supervisor.subscriptions.sort_newest') }}
                        @endswitch
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute start-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                    @foreach(['newest', 'oldest', 'expiring_soon', 'sessions_remaining'] as $sortOption)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sortOption, 'page' => 1]) }}"
                           class="block px-4 py-2 text-sm cursor-pointer {{ $currentSort === $sortOption ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ __('supervisor.subscriptions.sort_' . $sortOption) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-blue-500"></i>
                    {{ __('supervisor.subscriptions.filter_apply') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="{{ __('supervisor.subscriptions.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.filter_type') }}</label>
                            <select name="type" id="type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('supervisor.subscriptions.all_types') }}</option>
                                <option value="quran" @selected(request('type') === 'quran')>{{ __('supervisor.subscriptions.type_quran') }}</option>
                                <option value="academic" @selected(request('type') === 'academic')>{{ __('supervisor.subscriptions.type_academic') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('supervisor.subscriptions.all_statuses') }}</option>
                                @foreach(\App\Enums\SessionSubscriptionStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.subscriptions.filter_apply') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.subscriptions.filter_clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Subscription Items -->
        @if($subscriptions->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($subscriptions as $sub)
                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <!-- Top: Avatar + Info + Badges -->
                        <div class="flex items-start gap-3 md:gap-4 mb-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-sm font-bold text-gray-600 flex-shrink-0">
                                {{ mb_substr($sub['student_name'], 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-base md:text-lg font-bold text-gray-900 truncate">{{ $sub['student_name'] }}</span>
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $sub['type'] === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                                        <i class="{{ $sub['type'] === 'quran' ? 'ri-book-read-line' : 'ri-graduation-cap-line' }}"></i>
                                        {{ $sub['type'] === 'quran' ? __('supervisor.subscriptions.type_quran') : __('supervisor.subscriptions.type_academic') }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $sub['status']->badgeClasses() }}">
                                        {{ $sub['status']->label() }}
                                    </span>
                                </div>
                                <!-- Metadata row -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <i class="ri-user-star-line text-gray-400"></i>
                                        {{ $sub['teacher_name'] }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-book-open-line text-gray-400"></i>
                                        {{ $sub['sessions_completed'] }}/{{ $sub['sessions_total'] }} {{ __('supervisor.subscriptions.col_sessions') }}
                                        @if($sub['sessions_remaining'] <= 3 && $sub['sessions_remaining'] > 0)
                                            <span class="text-amber-600 font-medium">({{ $sub['sessions_remaining'] }} {{ __('supervisor.subscriptions.remaining') }})</span>
                                        @endif
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-calendar-line text-gray-400"></i>
                                        {{ $sub['start_date']?->format('Y-m-d') ?? '-' }}
                                        <span class="text-gray-400">{{ __('supervisor.subscriptions.to') }}</span>
                                        {{ $sub['end_date']?->format('Y-m-d') ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ms-0 md:ms-14">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}"
                                   class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                                    <i class="ri-eye-line"></i>
                                    {{ __('supervisor.subscriptions.action_view') }}
                                </a>
                                @if($isAdmin)
                                    <a href="{{ route('manage.subscriptions.edit', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-gray-50 text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="ri-edit-line"></i>
                                        {{ __('supervisor.subscriptions.action_edit') }}
                                    </a>

                                    @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::PAUSED)
                                        <form method="POST" action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                                                <i class="ri-play-circle-line"></i>
                                                {{ __('supervisor.subscriptions.action_resume') }}
                                            </button>
                                        </form>
                                    @endif

                                    @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                                        <form method="POST" action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                                                <i class="ri-pause-circle-line"></i>
                                                {{ __('supervisor.subscriptions.action_pause') }}
                                            </button>
                                        </form>
                                    @endif

                                    @if($sub['status']->canCancel())
                                        <form method="POST" action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                                <i class="ri-close-circle-line"></i>
                                                {{ __('supervisor.subscriptions.action_cancel') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($subscriptions->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-file-list-3-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.subscriptions.no_subscriptions') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.subscriptions.no_subscriptions_description') }}</p>
                    <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.subscriptions.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.subscriptions.no_subscriptions') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.subscriptions.no_subscriptions_description') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-checkbox-circle-line"></i>
        {{ session('success') }}
        <button @click="show = false" class="cursor-pointer ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif
@if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-error-warning-line"></i>
        {{ session('error') }}
        <button @click="show = false" class="cursor-pointer ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif

</x-layouts.supervisor>
