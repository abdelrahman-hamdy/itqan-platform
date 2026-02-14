@props([
    'route',
    'subjects' => [],
    'gradeLevels' => [],
    'levels' => [],
    'showSearch' => true,
    'showSubject' => true,
    'showGradeLevel' => true,
    'showDifficulty' => true,
    'color' => 'cyan'
])

@php
    $colorClasses = [
        'focus' => "focus:ring-{$color}-600 focus:border-{$color}-600",
        'button' => "bg-{$color}-600 hover:bg-{$color}-700",
        'buttonHover' => "hover:border-{$color}-600 hover:text-{$color}-700"
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-8" x-data="{ filtersOpen: false }">
    <form method="GET" action="{{ $route }}" class="space-y-4">
        <div class="flex items-center justify-between cursor-pointer md:cursor-default" @click="filtersOpen = !filtersOpen">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-filter-3-line"></i>
                {{ __('components.filters.title') }}
            </h3>
            <i class="ri-arrow-down-s-line text-xl text-gray-400 transition-transform duration-200 md:hidden" :class="{ 'rotate-180': filtersOpen }"></i>
        </div>

        <div :class="filtersOpen ? '' : 'hidden md:block'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            @if($showSearch)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-search-line"></i>
                    {{ __('components.filters.search') }}
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('components.filters.search_courses_placeholder') }}"
                       aria-label="{{ __('components.filters.search_courses_placeholder') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors">
            </div>
            @endif

            <!-- Subject Filter -->
            @if($showSubject && count($subjects) > 0)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-book-line"></i>
                    {{ __('components.filters.subject') }}
                </label>
                <div class="relative">
                    <select name="subject_id"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">{{ __('components.filters.all_subjects') }}</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-600">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Grade Level Filter -->
            @if($showGradeLevel && count($gradeLevels) > 0)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-medal-line"></i>
                    {{ __('components.filters.grade_level') }}
                </label>
                <div class="relative">
                    <select name="grade_level_id"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">{{ __('components.filters.all_grades') }}</option>
                        @foreach($gradeLevels as $gradeLevel)
                        <option value="{{ $gradeLevel->id }}" {{ request('grade_level_id') == $gradeLevel->id ? 'selected' : '' }}>
                            {{ $gradeLevel->getDisplayName() }}
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-600">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Difficulty Level Filter -->
            @if($showDifficulty && count($levels) > 0)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-bar-chart-line"></i>
                    {{ __('components.filters.difficulty_level') }}
                </label>
                <div class="relative">
                    <select name="level"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">{{ __('components.filters.all_levels') }}</option>
                        @foreach($levels as $level)
                        <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
                            @switch($level)
                                @case('easy') {{ __('components.filters.level_easy') }} @break
                                @case('medium') {{ __('components.filters.level_medium') }} @break
                                @case('hard') {{ __('components.filters.level_hard') }} @break
                                @default {{ $level }}
                            @endswitch
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-600">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Custom Filters Slot -->
            {{ $slot }}
        </div>

        <!-- Buttons Row -->
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="{{ $colorClasses['button'] }} text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-1">
                <i class="ri-search-line"></i>
                {{ __('components.filters.apply') }}
            </button>

            @if(request()->hasAny(['search', 'subject_id', 'grade_level_id', 'level']))
            <a href="{{ $route }}"
               class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors inline-flex items-center gap-1">
                <i class="ri-close-circle-line"></i>
                {{ __('components.filters.reset') }}
            </a>
            @endif
        </div>
        </div>
    </form>
</div>
