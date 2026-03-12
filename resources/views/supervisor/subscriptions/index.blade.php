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
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 md:gap-4 mb-6">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-time-line text-gray-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $totalExpired }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_expired') }}</p>
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
                    @php
                        $endDate = $sub['end_date'];
                        $daysLeft = $endDate ? (int) now()->diffInDays($endDate, false) : null;
                        $isExpired = $daysLeft !== null && $daysLeft < 0;
                        $isExpiringSoon = !$isExpired && $daysLeft !== null && $daysLeft <= 7;

                        $sessionsTotal = $sub['sessions_total'];
                        $sessionsCompleted = $sub['sessions_completed'];
                        $progressPct = $sessionsTotal > 0 ? min(100, round(($sessionsCompleted / $sessionsTotal) * 100)) : 0;

                        // Type label
                        $typeLabel = match($sub['sub_type']) {
                            'individual' => __('supervisor.subscriptions.type_quran_individual'),
                            'group' => __('supervisor.subscriptions.type_quran_group'),
                            default => __('supervisor.subscriptions.type_academic'),
                        };
                        $typeColor = $sub['type'] === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700';
                        $typeIcon = match($sub['sub_type']) {
                            'individual' => 'ri-user-line',
                            'group' => 'ri-group-line',
                            default => 'ri-graduation-cap-line',
                        };
                    @endphp

                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-start gap-3 md:gap-4">
                            <x-avatar :user="$sub['student_user']" size="md" user-type="student" />

                            <!-- Main content + right panel -->
                            <div class="flex-1 min-w-0 flex flex-col md:flex-row md:items-start md:gap-6">
                                <!-- Left: name, badges, metadata -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-base md:text-lg font-bold text-gray-900 truncate">{{ $sub['student_name'] }}</span>
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $typeColor }}">
                                            <i class="{{ $typeIcon }}"></i>
                                            {{ $typeLabel }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $sub['status']->badgeClasses() }}">
                                            {{ $sub['status']->label() }}
                                        </span>
                                        @if($sub['model']->isInGracePeriod())
                                            @php
                                                $graceMeta = $sub['model']->metadata ?? [];
                                                $graceEnd = \Carbon\Carbon::parse($graceMeta['grace_period_ends_at']);
                                                $graceDaysLeft = (int) now()->diffInDays($graceEnd, false);
                                            @endphp
                                            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-medium">
                                                <i class="ri-timer-line"></i>
                                                {{ __('supervisor.subscriptions.extended_for_days', ['days' => max(0, $graceDaysLeft)]) }}
                                            </span>
                                        @endif
                                        {{-- Expiry badge inline with name on desktop --}}
                                        @if($daysLeft !== null)
                                            @if($isExpired)
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">
                                                    <i class="ri-error-warning-line"></i>
                                                    {{ __('supervisor.subscriptions.ended_since', ['days' => abs($daysLeft)]) }}
                                                </span>
                                            @elseif($isExpiringSoon)
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">
                                                    <i class="ri-alarm-warning-line"></i>
                                                    {{ __('supervisor.subscriptions.ends_in', ['days' => $daysLeft]) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                                    <i class="ri-calendar-check-line"></i>
                                                    {{ __('supervisor.subscriptions.ends_in', ['days' => $daysLeft]) }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <i class="ri-user-star-line text-gray-400"></i>
                                            {{ $sub['teacher_name'] }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                            {{ $sub['start_date']?->format('Y-m-d') ?? '-' }}
                                            <span class="text-gray-400">{{ __('supervisor.subscriptions.to') }}</span>
                                            {{ $sub['end_date']?->format('Y-m-d') ?? '-' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right: sessions progress (desktop: side panel, mobile: below) -->
                                <div class="mt-2 md:mt-0 md:w-40 flex-shrink-0">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-500">{{ __('supervisor.subscriptions.col_sessions') }}</span>
                                        <span class="font-semibold text-gray-900">{{ $sessionsCompleted }}/{{ $sessionsTotal }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all {{ $progressPct >= 80 ? 'bg-red-500' : ($progressPct >= 50 ? 'bg-amber-500' : 'bg-blue-500') }}"
                                             style="width: {{ $progressPct }}%"></div>
                                    </div>
                                    @if($sub['sessions_remaining'] <= 3 && $sub['sessions_remaining'] > 0)
                                        <p class="text-xs text-amber-600 font-medium mt-0.5">{{ $sub['sessions_remaining'] }} {{ __('supervisor.subscriptions.remaining') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ms-0 md:ms-14 mt-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}"
                                   class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                    <i class="ri-eye-line"></i>
                                    {{ __('supervisor.subscriptions.action_view') }}
                                </a>
                                @if($isAdmin)
                                    @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::EXPIRED)
                                        <form id="activate-form-{{ $sub['id'] }}" method="POST"
                                              action="{{ route('manage.subscriptions.activate', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.subscriptions.action_activate')),
                                                message: @js(__('supervisor.subscriptions.confirm_activate')),
                                                confirmText: @js(__('supervisor.subscriptions.action_activate')),
                                                isDangerous: false,
                                                icon: 'ri-checkbox-circle-line',
                                                onConfirm: () => document.getElementById('activate-form-{{ $sub['id'] }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
                                            <i class="ri-checkbox-circle-line"></i>
                                            {{ __('supervisor.subscriptions.action_activate') }}
                                        </button>
                                    @endif

                                    @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::PAUSED)
                                        <form id="resume-form-{{ $sub['id'] }}" method="POST"
                                              action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.subscriptions.action_resume')),
                                                message: @js(__('supervisor.subscriptions.confirm_resume')),
                                                confirmText: @js(__('supervisor.subscriptions.action_resume')),
                                                isDangerous: false,
                                                icon: 'ri-play-circle-line',
                                                onConfirm: () => document.getElementById('resume-form-{{ $sub['id'] }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                            <i class="ri-play-circle-line"></i>
                                            {{ __('supervisor.subscriptions.action_resume') }}
                                        </button>
                                    @endif

                                    @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                                        <form id="pause-form-{{ $sub['id'] }}" method="POST"
                                              action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.subscriptions.action_pause')),
                                                message: @js(__('supervisor.subscriptions.confirm_pause')),
                                                confirmText: @js(__('supervisor.subscriptions.action_pause')),
                                                isDangerous: false,
                                                icon: 'ri-pause-circle-line',
                                                onConfirm: () => document.getElementById('pause-form-{{ $sub['id'] }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                                            <i class="ri-pause-circle-line"></i>
                                            {{ __('supervisor.subscriptions.action_pause') }}
                                        </button>
                                    @endif

                                    {{-- Extend button --}}
                                    <button type="button"
                                        onclick="document.getElementById('extend-modal-{{ $sub['id'] }}').classList.remove('hidden')"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
                                        <i class="ri-calendar-check-line"></i>
                                        {{ __('supervisor.subscriptions.action_extend') }}
                                    </button>

                                    {{-- Cancel Extension button (only when in grace period) --}}
                                    @if($sub['model']->isInGracePeriod())
                                        <form id="cancel-extension-form-{{ $sub['id'] }}" method="POST"
                                              action="{{ route('manage.subscriptions.cancel-extension', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.subscriptions.action_cancel_extension')),
                                                message: @js(__('supervisor.subscriptions.confirm_cancel_extension')),
                                                confirmText: @js(__('supervisor.subscriptions.action_cancel_extension')),
                                                isDangerous: true,
                                                icon: 'ri-calendar-close-line',
                                                onConfirm: () => document.getElementById('cancel-extension-form-{{ $sub['id'] }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition-colors">
                                            <i class="ri-calendar-close-line"></i>
                                            {{ __('supervisor.subscriptions.action_cancel_extension') }}
                                        </button>
                                    @endif

                                    @if($sub['status']->canCancel())
                                        <form id="cancel-form-{{ $sub['id'] }}" method="POST"
                                              action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.subscriptions.action_cancel')),
                                                message: @js(__('supervisor.subscriptions.confirm_cancel')),
                                                confirmText: @js(__('supervisor.subscriptions.action_cancel')),
                                                isDangerous: true,
                                                icon: 'ri-close-circle-line',
                                                onConfirm: () => document.getElementById('cancel-form-{{ $sub['id'] }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                                            <i class="ri-close-circle-line"></i>
                                            {{ __('supervisor.subscriptions.action_cancel') }}
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Extend Modal --}}
                    @if($isAdmin)
                        <div id="extend-modal-{{ $sub['id'] }}" class="hidden fixed inset-0 z-[9999] overflow-y-auto" x-data>
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
                            <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
                                <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                                    <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                                    <div class="p-6 pb-4 pt-8 md:pt-6">
                                        <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-green-100 mb-4">
                                            <i class="ri-calendar-check-line text-3xl md:text-2xl text-green-600"></i>
                                        </div>
                                        <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.extend_title') }}</h3>
                                        <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.extend_message', ['name' => $sub['student_name']]) }}</p>
                                        <form method="POST" action="{{ route('manage.subscriptions.extend', ['subdomain' => $subdomain, 'type' => $sub['type'], 'subscription' => $sub['id']]) }}" id="extend-form-{{ $sub['id'] }}">
                                            @csrf
                                            <label for="extend_days_{{ $sub['id'] }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.extend_days') }} ({{ __('supervisor.subscriptions.extend_max_days', ['max' => 30]) }})</label>
                                            <input type="number" name="extend_days" id="extend_days_{{ $sub['id'] }}" min="1" max="30" value="3" required
                                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                            @if($sub['end_date'])
                                                <p class="text-xs text-gray-500 mt-1">{{ __('supervisor.subscriptions.current_end_date') }}: {{ $sub['end_date']->format('Y-m-d') }}</p>
                                            @endif
                                        </form>
                                    </div>
                                    <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                                        <button type="button" onclick="this.closest('[id^=extend-modal]').classList.add('hidden')"
                                            class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                                            {{ __('common.cancel') }}
                                        </button>
                                        <button type="button" onclick="document.getElementById('extend-form-{{ $sub['id'] }}').submit()"
                                            class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md">
                                            {{ __('supervisor.subscriptions.action_extend') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
