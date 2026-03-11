<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('type')
        || request('teacher_id')
        || request('date_from')
        || request('date_to');

    $filterCount = (request('type') ? 1 : 0)
        + (request('teacher_id') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.homework.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.homework.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.homework.page_subtitle') }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        {{-- Total Assigned --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="ri-book-2-line text-xl text-blue-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.homework.total_assigned') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $totalAssigned }}</div>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-yellow-50 rounded-lg">
                    <i class="ri-time-line text-xl text-yellow-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.homework.pending_submissions') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $pendingCount }}</div>
        </div>

        {{-- Graded --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-green-50 rounded-lg">
                    <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.homework.graded') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $gradedCount }}</div>
        </div>

        {{-- Overdue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-red-50 rounded-lg">
                    <i class="ri-error-warning-line text-xl text-red-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.homework.overdue') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $overdueCount }}</div>
        </div>
    </div>

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- List Header -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.homework.list_title') }} ({{ $homework->total() }})
            </h2>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-orange-500"></i>
                    {{ __('supervisor.homework.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.filter_type') }}</label>
                            <select name="type" id="type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">{{ __('supervisor.homework.all_types') }}</option>
                                <option value="quran" {{ request('type') === 'quran' ? 'selected' : '' }}>{{ __('supervisor.homework.type_quran') }}</option>
                                <option value="academic" {{ request('type') === 'academic' ? 'selected' : '' }}>{{ __('supervisor.homework.type_academic') }}</option>
                                <option value="interactive" {{ request('type') === 'interactive' ? 'selected' : '' }}>{{ __('supervisor.homework.type_interactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.filter_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">{{ __('supervisor.homework.all_teachers') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher['id'] }}" {{ request('teacher_id') == $teacher['id'] ? 'selected' : '' }}>
                                        {{ $teacher['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.homework.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.homework.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        @if($homework->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.type') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.session_info') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.teacher') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.assigned_date') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.due_date') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.status') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.submissions') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($homework as $item)
                            @php
                                $typeBadges = [
                                    'quran' => 'bg-green-100 text-green-700',
                                    'academic' => 'bg-violet-100 text-violet-700',
                                    'interactive' => 'bg-blue-100 text-blue-700',
                                ];
                                $statusBadges = [
                                    'assigned' => 'bg-blue-100 text-blue-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'graded' => 'bg-green-100 text-green-700',
                                    'overdue' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 md:px-6 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $typeBadges[$item['type']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $item['type_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    <div class="font-medium text-gray-900 max-w-[200px] truncate">{{ $item['session_info'] }}</div>
                                    <div class="text-xs text-gray-500 md:hidden">{{ $item['teacher_name'] }}</div>
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">
                                    {{ $item['teacher_name'] }}
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                    {{ $item['assigned_date']?->format('Y-m-d') ?? '-' }}
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                    @if($item['due_date'])
                                        <span class="{{ $item['due_date']->isPast() ? 'text-red-600 font-medium' : '' }}">
                                            {{ $item['due_date']->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $statusBadges[$item['status']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">
                                    {{ $item['submissions_count'] }}
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    @if($item['has_submissions'] || $item['type'] === 'quran')
                                        <a href="{{ route('manage.homework.submissions', ['subdomain' => $subdomain, 'type' => $item['type'], 'id' => $item['id']]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                                            <i class="ri-eye-line"></i>
                                            {{ __('supervisor.homework.view_submissions') }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($homework->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $homework->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-book-2-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.homework.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.homework.no_results_description') }}</p>
                    <a href="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.homework.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.homework.no_homework') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.homework.no_homework_description') }}</p>
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

</x-layouts.supervisor>
