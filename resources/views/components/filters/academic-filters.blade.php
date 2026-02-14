@props([
    'route',
    'subjects' => [],
    'gradeLevels' => [],
    'showSearch' => true,
    'showSubjects' => true,
    'showGradeLevels' => true,
    'showExperience' => true,
    'showGender' => true,
    'showDays' => false,
    'color' => 'violet'
])

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
                       placeholder="{{ __('components.filters.search_teacher_placeholder') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
            </div>
            @endif

            <!-- Subject Filter -->
            @if($showSubjects && count($subjects) > 0)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-book-line"></i>
                    {{ __('components.filters.subject') }}
                </label>
                <div class="relative">
                    <select name="subject"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors bg-white">
                        <option value="">{{ __('components.filters.all_subjects') }}</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Grade Level Filter -->
            @if($showGradeLevels && count($gradeLevels) > 0)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-file-list-3-line"></i>
                    {{ __('components.filters.grade_level') }}
                </label>
                <div class="relative">
                    <select name="grade_level"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors bg-white">
                        <option value="">{{ __('components.filters.all_grades') }}</option>
                        @foreach($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}" {{ request('grade_level') == $gradeLevel->id ? 'selected' : '' }}>
                                {{ $gradeLevel->getDisplayName() }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Experience Years -->
            @if($showExperience)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-time-line"></i>
                    {{ __('components.filters.experience_years') }}
                </label>
                <div class="relative">
                    <select name="experience"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors bg-white">
                        <option value="">{{ __('components.filters.all') }}</option>
                        <option value="1-3" {{ request('experience') === '1-3' ? 'selected' : '' }}>{{ __('components.filters.years_1_3') }}</option>
                        <option value="3-5" {{ request('experience') === '3-5' ? 'selected' : '' }}>{{ __('components.filters.years_3_5') }}</option>
                        <option value="5-10" {{ request('experience') === '5-10' ? 'selected' : '' }}>{{ __('components.filters.years_5_10') }}</option>
                        <option value="10+" {{ request('experience') === '10+' ? 'selected' : '' }}>{{ __('components.filters.years_10_plus') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Gender Filter -->
            @if($showGender)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-line"></i>
                    {{ __('components.filters.gender') }}
                </label>
                <div class="relative">
                    <select name="gender"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors bg-white">
                        <option value="">{{ __('components.filters.all') }}</option>
                        <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>{{ __('components.filters.male_teacher') }}</option>
                        <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>{{ __('components.filters.female_teacher') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Schedule Days (Multi-select) -->
            @if($showDays)
            <div>
                <label class="flex items-center gap-1 text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-calendar-line"></i>
                    {{ __('components.filters.schedule_days') }}
                </label>
                <div class="relative" x-data="{ open: false, selected: {{ json_encode(request('schedule_days', [])) }} }">
                    <button type="button" @click="open = !open"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors bg-white text-start">
                        <span x-text="selected.length > 0 ? selected.length + ' {{ __('components.filters.days_selected', ['count' => '']) }}'.replace('{count}', '') : '{{ __('components.filters.all_days') }}'" class="text-gray-700"></span>
                    </button>
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                    <div x-show="open" @click.away="open = false"
                         x-cloak
                         class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
                        @foreach(\App\Enums\WeekDays::cases() as $weekDay)
                        <label class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="schedule_days[]" value="{{ $weekDay->value }}"
                                   x-model="selected"
                                   {{ in_array($weekDay->value, request('schedule_days', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700">{{ $weekDay->label() }}</span>
                        </label>
                        @endforeach
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
                    class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-1">
                <i class="ri-search-line"></i>
                {{ __('components.filters.apply') }}
            </button>

            @if(request()->hasAny(['subject', 'grade_level', 'search', 'gender', 'schedule_days']))
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
