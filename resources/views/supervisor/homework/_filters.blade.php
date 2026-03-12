@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $hasActiveFilters = request('teacher_id') || request('date_from') || request('date_to');
    $filterCount = (request('teacher_id') ? 1 : 0) + (request('date_from') ? 1 : 0) + (request('date_to') ? 1 : 0);
@endphp

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
        <form method="GET" action="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}#{{ $tabType }}" class="px-4 md:px-6 pb-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label for="teacher_id_{{ $tabType }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.filter_teacher') }}</label>
                    <select name="teacher_id" id="teacher_id_{{ $tabType }}" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('supervisor.homework.all_teachers') }}</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher['id'] }}" {{ request('teacher_id') == $teacher['id'] ? 'selected' : '' }}>
                                {{ $teacher['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from_{{ $tabType }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from_{{ $tabType }}" value="{{ request('date_from') }}"
                           class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="date_to_{{ $tabType }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.homework.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to_{{ $tabType }}" value="{{ request('date_to') }}"
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
                    <a href="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}#{{ $tabType }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                        <i class="ri-close-line"></i>
                        {{ __('supervisor.homework.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
