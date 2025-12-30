@props([
    'route', // Form action route
    'filters' => ['search', 'enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day'], // Available filters
    'teachers' => collect(), // Available teachers for dropdown
    'showHeader' => true,
    'title' => null
])

@php
    $hasActiveFilters = request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search', 'specialization']);
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
    $displayTitle = $title ?? __('components.filters.title');
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ $route }}" class="space-y-4">
        @if($showHeader)
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-filter-3-line ms-2 rtl:ms-2 ltr:me-2"></i>
                {{ $displayTitle }}
            </h3>
            @if($hasActiveFilters)
            <a href="{{ $route }}"
               class="text-sm text-gray-600 hover:text-primary transition-colors">
                <i class="ri-close-circle-line ms-1 rtl:ms-1 ltr:me-1"></i>
                {{ __('components.filters.reset') }}
            </a>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            @if(in_array('search', $filters))
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-search-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.search') }}
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('components.filters.search_placeholder') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
            </div>
            @endif

            <!-- Enrollment Status (Group circles) -->
            @if(in_array('enrollment_status', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-follow-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.enrollment_status') }}
                </label>
                <select name="enrollment_status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">{{ __('components.filters.all') }}</option>
                    <option value="enrolled" {{ request('enrollment_status') === 'enrolled' ? 'selected' : '' }}>{{ __('components.filters.my_circles') }}</option>
                    <option value="available" {{ request('enrollment_status') === 'available' ? 'selected' : '' }}>{{ __('components.filters.available_for_enrollment') }}</option>
                    <option value="open" {{ request('enrollment_status') === 'open' ? 'selected' : '' }}>{{ __('components.filters.open') }}</option>
                    <option value="full" {{ request('enrollment_status') === 'full' ? 'selected' : '' }}>{{ __('components.filters.full') }}</option>
                </select>
            </div>
            @endif

            <!-- Memorization Level (Quran circles) -->
            @if(in_array('memorization_level', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-bar-chart-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.memorization_level') }}
                </label>
                <select name="memorization_level"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">{{ __('components.filters.all_levels') }}</option>
                    <option value="beginner" {{ request('memorization_level') === 'beginner' ? 'selected' : '' }}>{{ __('components.filters.beginner') }}</option>
                    <option value="intermediate" {{ request('memorization_level') === 'intermediate' ? 'selected' : '' }}>{{ __('components.filters.intermediate') }}</option>
                    <option value="advanced" {{ request('memorization_level') === 'advanced' ? 'selected' : '' }}>{{ __('components.filters.advanced') }}</option>
                </select>
            </div>
            @endif

            <!-- Teacher -->
            @if(in_array('teacher_id', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-star-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.teacher') }}
                </label>
                <select name="teacher_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">{{ __('components.filters.all_teachers') }}</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->user_id ?? $teacher->id }}" {{ request('teacher_id') == ($teacher->user_id ?? $teacher->id) ? 'selected' : '' }}>
                        {{ $teacher->user->full_name ?? $teacher->full_name ?? $teacher->name ?? __('components.filters.teacher') }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Schedule Day (Group circles) -->
            @if(in_array('schedule_day', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-calendar-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.schedule_days') }}
                </label>
                <select name="schedule_day"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">{{ __('components.filters.all_days') }}</option>
                    <option value="{{ __('common.days.saturday') }}" {{ request('schedule_day') === __('common.days.saturday') ? 'selected' : '' }}>{{ __('common.days.saturday') }}</option>
                    <option value="{{ __('common.days.sunday') }}" {{ request('schedule_day') === __('common.days.sunday') ? 'selected' : '' }}>{{ __('common.days.sunday') }}</option>
                    <option value="{{ __('common.days.monday') }}" {{ request('schedule_day') === __('common.days.monday') ? 'selected' : '' }}>{{ __('common.days.monday') }}</option>
                    <option value="{{ __('common.days.tuesday') }}" {{ request('schedule_day') === __('common.days.tuesday') ? 'selected' : '' }}>{{ __('common.days.tuesday') }}</option>
                    <option value="{{ __('common.days.wednesday') }}" {{ request('schedule_day') === __('common.days.wednesday') ? 'selected' : '' }}>{{ __('common.days.wednesday') }}</option>
                    <option value="{{ __('common.days.thursday') }}" {{ request('schedule_day') === __('common.days.thursday') ? 'selected' : '' }}>{{ __('common.days.thursday') }}</option>
                    <option value="{{ __('common.days.friday') }}" {{ request('schedule_day') === __('common.days.friday') ? 'selected' : '' }}>{{ __('common.days.friday') }}</option>
                </select>
            </div>
            @endif

            <!-- Specialization (Individual circles) -->
            @if(in_array('specialization', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-book-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.specialization') }}
                </label>
                <select name="specialization"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">{{ __('components.filters.all_specializations') }}</option>
                    <option value="quran" {{ request('specialization') === 'quran' ? 'selected' : '' }}>{{ __('components.filters.quran') }}</option>
                    <option value="academic" {{ request('specialization') === 'academic' ? 'selected' : '' }}>{{ __('components.filters.academic_lessons') }}</option>
                </select>
            </div>
            @endif

            <!-- Apply Button -->
            <div class="lg:col-span-2 flex items-end">
                <button type="submit"
                        class="w-full bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                    <i class="ri-search-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ __('components.filters.apply') }}
                </button>
            </div>
        </div>
    </form>
</div>
