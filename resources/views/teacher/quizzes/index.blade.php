<x-layouts.teacher :title="__('teacher.quizzes.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('is_active') !== null && request('is_active') !== '' || request('search') || request('date_from') || request('date_to');
@endphp

    {{-- Page Header --}}
    <div>
        <x-ui.breadcrumb :items="[['label' => __('teacher.quizzes.breadcrumb')]]" view-type="teacher" />

        <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.quizzes.page_title') }}</h1>
                <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.quizzes.page_description') }}</p>
            </div>
            <a href="{{ route('teacher.quizzes.create', ['subdomain' => $subdomain]) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                <i class="ri-add-line"></i>
                {{ __('teacher.quizzes.create_quiz') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start">
                <i class="ri-checkbox-circle-line text-green-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                <p class="font-medium text-green-900 text-sm md:text-base">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-red-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                <p class="font-medium text-red-900 text-sm md:text-base">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-questionnaire-line text-lg md:text-xl text-blue-600"></i>
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
                    <i class="ri-filter-3-line text-blue-500"></i>
                    {{ __('teacher.quizzes.filter') }}
                    @if($hasActiveFilters)
                        @php
                            $filterCount = (request('is_active') !== null && request('is_active') !== '' ? 1 : 0)
                                + (request('search') ? 1 : 0)
                                + (request('date_from') ? 1 : 0)
                                + (request('date_to') ? 1 : 0);
                        @endphp
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('teacher.quizzes.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('teacher.quizzes.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.filter_status') }}</label>
                            <select name="is_active" id="is_active" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('teacher.quizzes.all_quizzes') }}</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('teacher.quizzes.active_only') }}</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('teacher.quizzes.inactive_only') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('teacher.quizzes.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('teacher.quizzes.index', ['subdomain' => $subdomain]) }}"
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

                        $actions = [
                            [
                                'href' => route('teacher.quizzes.show', ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                                'icon' => 'ri-eye-line',
                                'label' => __('common.view'),
                                'shortLabel' => __('common.view'),
                                'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                            ],
                            [
                                'href' => route('teacher.quizzes.edit', ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                                'icon' => 'ri-edit-line',
                                'label' => __('common.edit'),
                                'shortLabel' => __('common.edit'),
                                'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                            ],
                        ];
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$quiz->title"
                        :status-badge="$quiz->is_active ? __('teacher.quizzes.active') : __('teacher.quizzes.inactive')"
                        :status-class="$quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                        :metadata="$metadata"
                        :description="$quiz->description"
                        :actions="$actions"
                        icon="ri-questionnaire-line"
                        icon-bg-class="bg-gradient-to-br from-blue-500 to-blue-600"
                    />
                @endforeach
            </div>

            @if($quizzes->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $quizzes->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-questionnaire-line text-xl md:text-2xl text-gray-400"></i>
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
                    <a href="{{ route('teacher.quizzes.index', ['subdomain' => $subdomain]) }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('teacher.quizzes.view_all') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-layouts.teacher>
