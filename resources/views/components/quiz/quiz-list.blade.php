@props([
    'quizzes',
    'totalQuizzes',
    'activeQuizzes',
    'totalAssignments',
    'totalAttempts',
    'filterRoute',
    'subdomain',
    'showRoute',
    'teachers' => null,
    'selectedTeacherId' => null,
    'showEditButton' => false,
    'editRoute' => null,
    'accentColor' => 'blue',
])

@php
    $hasActiveFilters = request('is_active') !== null && request('is_active') !== ''
        || request('search')
        || request('date_from')
        || request('date_to')
        || ($teachers && request('teacher_id'));

    $filterCount = (request('is_active') !== null && request('is_active') !== '' ? 1 : 0)
        + (request('search') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0)
        + ($teachers && request('teacher_id') ? 1 : 0);

    $colorMap = [
        'blue' => [
            'stat1_bg' => 'bg-blue-100', 'stat1_text' => 'text-blue-600',
            'filter_icon' => 'text-blue-500', 'filter_badge' => 'bg-blue-500',
            'btn_primary' => 'bg-blue-600 hover:bg-blue-700',
            'icon_gradient' => 'bg-gradient-to-br from-blue-500 to-blue-600',
            'empty_bg' => 'bg-gray-100', 'empty_icon' => 'text-gray-400',
        ],
        'indigo' => [
            'stat1_bg' => 'bg-indigo-100', 'stat1_text' => 'text-indigo-600',
            'filter_icon' => 'text-indigo-500', 'filter_badge' => 'bg-indigo-500',
            'btn_primary' => 'bg-indigo-600 hover:bg-indigo-700',
            'icon_gradient' => 'bg-gradient-to-br from-indigo-500 to-indigo-600',
            'empty_bg' => 'bg-indigo-100', 'empty_icon' => 'text-indigo-400',
        ],
    ];
    $c = $colorMap[$accentColor] ?? $colorMap['blue'];
@endphp

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 {{ $c['stat1_bg'] }} rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-questionnaire-line text-lg md:text-xl {{ $c['stat1_text'] }}"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalQuizzes ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.quizzes.total_quizzes') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-checkbox-circle-line text-lg md:text-xl text-green-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeQuizzes ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.quizzes.active_quizzes') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-links-line text-lg md:text-xl text-purple-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalAssignments ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.quizzes.total_assignments') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-file-list-3-line text-lg md:text-xl text-amber-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalAttempts ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.quizzes.total_attempts') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- List Card --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    {{-- List Header --}}
    <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
        <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.quizzes.list_title') }} ({{ $quizzes->total() }})</h2>
    </div>

    {{-- Collapsible Filters --}}
    <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <span class="flex items-center gap-2">
                <i class="ri-filter-3-line {{ $c['filter_icon'] }}"></i>
                {{ __('teacher.quizzes.filter') }}
                @if($hasActiveFilters)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white {{ $c['filter_badge'] }} rounded-full">{{ $filterCount }}</span>
                @endif
            </span>
            <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        <div x-show="open" x-collapse>
            <form method="GET" action="{{ $filterRoute }}" class="px-4 md:px-6 pb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                    @if($teachers)
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_by_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                                <option value="">{{ __('supervisor.common.all_teachers') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher['id'] }}" {{ $selectedTeacherId == $teacher['id'] ? 'selected' : '' }}>
                                        {{ $teacher['name'] }}
                                        @if(isset($teacher['type_label'])) ({{ $teacher['type_label'] }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.filter_search') }}</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('teacher.quizzes.search_placeholder') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                    </div>
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.filter_status') }}</label>
                        <select name="is_active" id="is_active" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                            <option value="">{{ __('teacher.quizzes.all_quizzes') }}</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('teacher.quizzes.active_only') }}</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('teacher.quizzes.inactive_only') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_from') }}</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                    </div>
                    @if(!$teachers)
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                        </div>
                    @endif
                </div>
                @if($teachers)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mt-3">
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">
                        </div>
                    </div>
                @endif
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 {{ $c['btn_primary'] }} text-white rounded-lg transition-colors text-sm font-medium">
                        <i class="ri-filter-line"></i>
                        {{ __('teacher.quizzes.filter') }}
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ $filterRoute }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                            <i class="ri-close-line"></i>
                            {{ __('teacher.quizzes.clear_filters') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Items --}}
    @if($quizzes->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($quizzes as $quiz)
                @php
                    $metadata = [
                        ['icon' => 'ri-question-line', 'iconColor' => 'text-blue-500', 'text' => $quiz->questions_count . ' ' . __('teacher.quizzes.questions')],
                        ['icon' => 'ri-links-line', 'iconColor' => 'text-purple-500', 'text' => ($quiz->assignments_count ?? $quiz->assignments->count()) . ' ' . __('teacher.quizzes.assignments')],
                        ['icon' => 'ri-percent-line', 'iconColor' => 'text-green-500', 'text' => __('teacher.quizzes.passing_score') . ': ' . $quiz->passing_score . '%'],
                    ];
                    if ($quiz->duration_minutes) {
                        $metadata[] = ['icon' => 'ri-time-line', 'iconColor' => 'text-amber-500', 'text' => $quiz->duration_minutes . ' ' . __('teacher.quizzes.minutes')];
                    }
                    if ($teachers && $quiz->relationLoaded('creator') && $quiz->creator) {
                        $metadata[] = ['icon' => 'ri-user-line', 'iconColor' => 'text-gray-400', 'text' => __('supervisor.quizzes.created_by', ['name' => $quiz->creator->name])];
                    }

                    $actions = [
                        [
                            'href' => route($showRoute, ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                            'icon' => 'ri-eye-line',
                            'label' => __('common.view'),
                            'shortLabel' => __('common.view'),
                            'class' => $c['btn_primary'] . ' text-white',
                        ],
                    ];
                    if ($showEditButton && $editRoute) {
                        $actions[] = [
                            'href' => route($editRoute, ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                            'icon' => 'ri-edit-line',
                            'label' => __('common.edit'),
                            'shortLabel' => __('common.edit'),
                            'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                        ];
                    }
                @endphp

                <x-teacher.entity-list-item
                    :title="$quiz->title"
                    :status-badge="$quiz->is_active ? __('teacher.quizzes.active') : __('teacher.quizzes.inactive')"
                    :status-class="$quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                    :metadata="$metadata"
                    :description="$quiz->description"
                    :actions="$actions"
                    icon="ri-questionnaire-line"
                    :icon-bg-class="$c['icon_gradient']"
                />
            @endforeach
        </div>

        @if($quizzes->hasPages())
            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                {{ $quizzes->withQueryString()->links() }}
            </div>
        @endif
    @else
        <div class="px-4 md:px-6 py-8 md:py-12 text-center">
            <div class="w-14 h-14 md:w-16 md:h-16 {{ $c['empty_bg'] }} rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-questionnaire-line text-xl md:text-2xl {{ $c['empty_icon'] }}"></i>
            </div>
            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('teacher.quizzes.empty_title') }}</h3>
            <p class="text-sm md:text-base text-gray-600">
                @if($hasActiveFilters)
                    {{ __('teacher.quizzes.empty_filter_description') }}
                @else
                    {{ __('teacher.quizzes.empty_description') }}
                @endif
            </p>
            @if($hasActiveFilters)
                <a href="{{ $filterRoute }}"
                   class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 {{ $c['btn_primary'] }} text-white text-sm font-medium rounded-lg transition-colors mt-4">
                    {{ __('teacher.quizzes.view_all') }}
                </a>
            @endif
        </div>
    @endif
</div>
