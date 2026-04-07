<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $filterRoute = route('manage.interactive-courses.index', ['subdomain' => $subdomain]);

    $hasActiveFilters = request('status')
        || request('search')
        || request('teacher_id')
        || request('subject_id')
        || request('grade_level_id');

    $filterCount = (request('status') ? 1 : 0)
        + (request('search') ? 1 : 0)
        + (request('teacher_id') ? 1 : 0)
        + (request('subject_id') ? 1 : 0)
        + (request('grade_level_id') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb :items="[['label' => __('supervisor.interactive_courses.page_title')]]" view-type="supervisor" />

    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.interactive_courses.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.interactive_courses.page_subtitle') }}</p>
        </div>
        @if($canCreate)
            <a href="{{ route('manage.interactive-courses.create', ['subdomain' => $subdomain]) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                <i class="ri-add-line"></i>
                {{ __('supervisor.interactive_courses.create_course') }}
            </a>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-book-open-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.interactive_courses.total_courses') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-play-circle-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.interactive_courses.active_courses') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-lg md:text-xl text-purple-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.interactive_courses.completed_courses') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['totalEnrolled'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.interactive_courses.total_enrolled') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- List Header --}}
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('supervisor.interactive_courses.page_title') }} ({{ $courses->total() }})</h2>
        </div>

        {{-- Collapsible Filters --}}
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-blue-500"></i>
                    {{ __('teacher.quizzes.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ $filterRoute }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_by_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('supervisor.common.all_teachers') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher['id'] }}" {{ request('teacher_id') == $teacher['id'] ? 'selected' : '' }}>
                                        {{ $teacher['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('supervisor.interactive_courses.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('teacher.courses_list.all_courses') }}</option>
                                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>{{ __('teacher.courses_list.status_published') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('teacher.courses_list.status_active') }}</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('teacher.courses_list.status_completed') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.filter_by_subject') }}</label>
                            <select name="subject_id" id="subject_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('supervisor.interactive_courses.all_subjects') }}</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mt-3">
                        <div>
                            <label for="grade_level_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.filter_by_grade') }}</label>
                            <select name="grade_level_id" id="grade_level_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('supervisor.interactive_courses.all_grades') }}</option>
                                @foreach($gradeLevels as $level)
                                    <option value="{{ $level->id }}" {{ request('grade_level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
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
        @if($courses->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($courses as $course)
                    @php
                        $statusValue = is_object($course->status) ? $course->status->value : $course->status;
                        $statusConfig = match($statusValue) {
                            'draft' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.courses_list.status_draft')],
                            'published' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.courses_list.status_published')],
                            'active' => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.courses_list.status_active')],
                            'completed' => ['class' => 'bg-purple-100 text-purple-800', 'text' => __('teacher.courses_list.status_completed')],
                            'cancelled' => ['class' => 'bg-red-100 text-red-800', 'text' => __('teacher.courses_list.status_cancelled')],
                            default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? '']
                        };

                        $metadata = [
                            ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $course->assignedTeacher?->user?->name ?? ''])],
                        ];
                        if ($course->subject) { $metadata[] = ['icon' => 'ri-book-line', 'text' => $course->subject->name]; }
                        if ($course->gradeLevel) { $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $course->gradeLevel->getDisplayName()]; }
                        $metadata[] = ['icon' => 'ri-user-line', 'text' => __('teacher.courses_list.students_enrolled_count', ['count' => $course->enrollments->count()])];

                        $actions = [
                            [
                                'href' => route('manage.interactive-courses.show', ['subdomain' => $subdomain, 'course' => $course->id]),
                                'icon' => 'ri-eye-line',
                                'label' => __('supervisor.common.view_details'),
                                'shortLabel' => __('supervisor.common.view'),
                                'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                            ],
                        ];
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$course->title"
                        :status-badge="$statusConfig['text']"
                        :status-class="$statusConfig['class']"
                        :metadata="$metadata"
                        :actions="$actions"
                        :description="$course->description"
                        icon="ri-book-open-line"
                        icon-bg-class="bg-gradient-to-br from-blue-500 to-indigo-600"
                    />
                @endforeach
            </div>

            @if($courses->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $courses->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-book-open-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.common.no_data') }}</h3>
                <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.interactive_courses.page_subtitle') }}</p>
                @if($hasActiveFilters)
                    <a href="{{ $filterRoute }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.common.back_to_list') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
