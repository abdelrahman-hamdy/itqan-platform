<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $filterRoute = route('manage.group-circles.index', ['subdomain' => $subdomain]);

    $hasActiveFilters = request('status')
        || request('search')
        || request('date_from')
        || request('date_to')
        || request('teacher_id');

    $filterCount = (request('status') ? 1 : 0)
        + (request('search') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0)
        + (request('teacher_id') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb :items="[['label' => __('supervisor.group_circles.page_title')]]" view-type="supervisor" />

    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.group_circles.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.group_circles.page_subtitle') }}</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('manage.group-circles.create', ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="ri-add-line"></i>
                {{ __('supervisor.group_circles.create_circle') }}
            </a>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-group-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.group_circles.total_circles') }}</p>
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
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.group_circles.active_circles') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-add-line text-lg md:text-xl text-orange-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['full'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.group_circles.full_capacity') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-team-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['totalStudents'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.group_circles.total_students') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- List Header --}}
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('supervisor.group_circles.page_title') }} ({{ $circles->total() }})</h2>
        </div>

        {{-- Collapsible Filters --}}
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-green-500"></i>
                    {{ __('teacher.quizzes.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-green-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ $filterRoute }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_by_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('supervisor.group_circles.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">{{ __('teacher.circles_list.group.all_circles') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('enums.circle_active_status.active') }}</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('enums.circle_active_status.inactive') }}</option>
                                <option value="full" {{ request('status') === 'full' ? 'selected' : '' }}>{{ __('teacher.circles_list.group.full_filter') }}</option>
                                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ __('teacher.circles_list.group.active_filter') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mt-3">
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
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
        @if($circles->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($circles as $circle)
                    @php
                        $statusConfig = match(true) {
                            !$circle->status => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('enums.circle_active_status.inactive')],
                            $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.circles_list.group.status_full')],
                            default => ['class' => 'bg-green-100 text-green-800', 'text' => __('enums.circle_active_status.active')],
                        };

                        $metadata = [
                            ['icon' => 'ri-user-3-line', 'text' => __('teacher.circles_list.group.students_per_max', ['enrolled' => $circle->enrolled_students ?? 0, 'max' => $circle->max_students ?? 15])],
                            ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $circle->quranTeacher?->name ?? ''])],
                            ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')],
                        ];

                        if ($circle->schedule && is_array($circle->schedule->days_of_week ?? null)) {
                            $daysText = implode('، ', array_map(fn($day) => match($day) {
                                'sunday' => __('teacher.circles_list.days.sunday'),
                                'monday' => __('teacher.circles_list.days.monday'),
                                'tuesday' => __('teacher.circles_list.days.tuesday'),
                                'wednesday' => __('teacher.circles_list.days.wednesday'),
                                'thursday' => __('teacher.circles_list.days.thursday'),
                                'friday' => __('teacher.circles_list.days.friday'),
                                'saturday' => __('teacher.circles_list.days.saturday'),
                                default => $day
                            }, $circle->schedule->days_of_week));
                            $metadata[] = ['icon' => 'ri-time-line', 'text' => $daysText, 'class' => 'hidden sm:flex'];
                        }

                        $actions = [
                            [
                                'href' => route('manage.group-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]),
                                'icon' => 'ri-eye-line',
                                'label' => __('supervisor.common.view_details'),
                                'shortLabel' => __('supervisor.common.view'),
                                'class' => 'bg-green-600 hover:bg-green-700 text-white',
                            ],
                        ];
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$circle->name ?? __('teacher.circles.group.title')"
                        :status-badge="$statusConfig['text']"
                        :status-class="$statusConfig['class']"
                        :metadata="$metadata"
                        :actions="$actions"
                        :description="$circle->description"
                        icon="ri-group-line"
                        icon-bg-class="bg-gradient-to-br from-green-500 to-teal-600"
                    />
                @endforeach
            </div>

            @if($circles->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $circles->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-group-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.common.no_data') }}</h3>
                <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.group_circles.page_subtitle') }}</p>
                @if($hasActiveFilters)
                    <a href="{{ $filterRoute }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.common.back_to_list') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
