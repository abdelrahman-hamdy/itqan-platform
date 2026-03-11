<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('search') || request('type') || request('status');
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.subscriptions.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.subscriptions.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.subscriptions.page_subtitle') }}</p>
        </div>
        @if($isAdmin)
            <a href="{{ route('manage.subscriptions.create', ['subdomain' => $subdomain]) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap cursor-pointer">
                <i class="ri-add-line"></i>
                {{ __('supervisor.subscriptions.add_subscription') }}
            </a>
        @endif
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div>
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
                <div>
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
                <div>
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
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $totalPaused }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.subscriptions.stat_paused') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.filter_search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('supervisor.subscriptions.search_placeholder') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.filter_type') }}</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('supervisor.subscriptions.all_types') }}</option>
                    <option value="quran" @selected(request('type') === 'quran')>{{ __('supervisor.subscriptions.type_quran') }}</option>
                    <option value="academic" @selected(request('type') === 'academic')>{{ __('supervisor.subscriptions.type_academic') }}</option>
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.filter_status') }}</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('supervisor.subscriptions.all_statuses') }}</option>
                    @foreach(\App\Enums\SessionSubscriptionStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="min-h-[38px] px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                    <i class="ri-search-line me-1"></i>{{ __('supervisor.subscriptions.filter_apply') }}
                </button>
                @if($hasActiveFilters)
                    <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
                       class="min-h-[38px] px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors inline-flex items-center">
                        <i class="ri-close-line me-1"></i>{{ __('supervisor.subscriptions.filter_clear') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_student') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_teacher') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_type') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_status') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_sessions') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_dates') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $sub)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $sub['student_name'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sub['teacher_name'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full {{ $sub['type'] === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                                    {{ $sub['type'] === 'quran' ? __('supervisor.subscriptions.type_quran') : __('supervisor.subscriptions.type_academic') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full {{ $sub['status']->badgeClasses() }}">
                                    {{ $sub['status']->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                {{ $sub['sessions_completed'] }}/{{ $sub['sessions_total'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div>{{ $sub['start_date']?->format('Y-m-d') ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ $sub['end_date']?->format('Y-m-d') ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="relative inline-block" x-data="{ open: false }">
                                    <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition
                                         class="absolute left-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 z-10 py-1">
                                        <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $sub['type'], 'id' => $sub['id']]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <i class="ri-eye-line me-1.5"></i>{{ __('supervisor.subscriptions.action_view') }}
                                        </a>
                                        @if($isAdmin)
                                            <a href="{{ route('manage.subscriptions.edit', ['subdomain' => $subdomain, 'type' => $sub['type'], 'id' => $sub['id']]) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                <i class="ri-edit-line me-1.5"></i>{{ __('supervisor.subscriptions.action_edit') }}
                                            </a>
                                            @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::PAUSED)
                                                <form method="POST" action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $sub['type'], 'id' => $sub['id']]) }}">
                                                    @csrf
                                                    <button type="submit" class="block w-full text-start px-4 py-2 text-sm text-blue-600 hover:bg-blue-50">
                                                        <i class="ri-play-circle-line me-1.5"></i>{{ __('supervisor.subscriptions.action_resume') }}
                                                    </button>
                                                </form>
                                            @endif
                                            @if($sub['status'] === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                                                <form method="POST" action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $sub['type'], 'id' => $sub['id']]) }}">
                                                    @csrf
                                                    <button type="submit" class="block w-full text-start px-4 py-2 text-sm text-amber-600 hover:bg-amber-50">
                                                        <i class="ri-pause-circle-line me-1.5"></i>{{ __('supervisor.subscriptions.action_pause') }}
                                                    </button>
                                                </form>
                                            @endif
                                            @if($sub['status']->canCancel())
                                                <form method="POST" action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $sub['type'], 'id' => $sub['id']]) }}">
                                                    @csrf
                                                    <button type="submit" class="block w-full text-start px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                        <i class="ri-close-circle-line me-1.5"></i>{{ __('supervisor.subscriptions.action_cancel') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="ri-file-list-3-line text-3xl text-gray-300 mb-2"></i>
                                    {{ __('supervisor.subscriptions.no_subscriptions') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscriptions->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
