@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $typeOptions = [
        '' => __('teacher.reports.all_types'),
        'quran_individual' => __('teacher.reports.type_quran_individual'),
        'quran_group' => __('teacher.reports.type_quran_group'),
        'academic' => __('teacher.reports.type_academic_lesson'),
        'interactive' => __('teacher.reports.type_interactive_course'),
    ];

    $hasActiveFilters = request('type') || request('entity_id') || request('student_search');

    $typeBadgeConfig = [
        'quran_individual' => ['class' => 'bg-green-100 text-green-700', 'text' => __('teacher.reports.type_quran_individual'), 'icon' => 'ri-book-open-line', 'iconBg' => 'bg-gradient-to-br from-green-500 to-green-600'],
        'quran_group' => ['class' => 'bg-emerald-100 text-emerald-700', 'text' => __('teacher.reports.type_quran_group'), 'icon' => 'ri-team-line', 'iconBg' => 'bg-gradient-to-br from-emerald-500 to-emerald-600'],
        'academic' => ['class' => 'bg-violet-100 text-violet-700', 'text' => __('teacher.reports.type_academic_lesson'), 'icon' => 'ri-graduation-cap-line', 'iconBg' => 'bg-gradient-to-br from-violet-500 to-violet-600'],
        'interactive' => ['class' => 'bg-purple-100 text-purple-700', 'text' => __('teacher.reports.type_interactive_course'), 'icon' => 'ri-live-line', 'iconBg' => 'bg-gradient-to-br from-purple-500 to-purple-600'],
    ];
@endphp

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-group-line text-lg md:text-xl text-indigo-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalStudents ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.total_students') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-pie-chart-line text-lg md:text-xl text-green-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ ($avgAttendance ?? 0) . '%' }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.avg_attendance') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-book-open-line text-lg md:text-xl text-violet-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalEntities ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.total_entities') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Student List Card --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    {{-- List Header --}}
    <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
        <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.reports.tab_student_overview') }} ({{ $paginatedRows->total() }})</h2>
    </div>

    {{-- Collapsible Filters --}}
    <div x-data="{
        open: {{ $hasActiveFilters ? 'true' : 'false' }},
        selectedType: '{{ request('type', '') }}',
        entityOptions: {{ Js::from($entityOptions ?? []) }},
        get filteredEntities() {
            if (!this.selectedType || !this.entityOptions[this.selectedType]) return [];
            return this.entityOptions[this.selectedType];
        }
    }" class="border-b border-gray-200">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <span class="flex items-center gap-2">
                <i class="ri-filter-3-line text-indigo-500"></i>
                {{ __('teacher.reports.filter') }}
                @if($hasActiveFilters)
                    @php
                        $filterCount = (request('type') ? 1 : 0) + (request('entity_id') ? 1 : 0) + (request('student_search') ? 1 : 0);
                    @endphp
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-indigo-500 rounded-full">{{ $filterCount }}</span>
                @endif
            </span>
            <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        <div x-show="open" x-collapse>
            <form method="GET" action="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                <input type="hidden" name="tab" value="students">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                    {{-- Type Filter --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.filter_type') }}</label>
                        <select name="type" id="type" x-model="selectedType"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Entity Filter (cascading) --}}
                    <div>
                        <label for="entity_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.filter_entity') }}</label>
                        <select name="entity_id" id="entity_id"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                :disabled="filteredEntities.length === 0">
                            <option value="">{{ __('teacher.reports.all_entities') }}</option>
                            <template x-for="entity in filteredEntities" :key="entity.id">
                                <option :value="entity.id" x-text="entity.name" :selected="entity.id == {{ request('entity_id', 0) }}"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Student Search --}}
                    <div>
                        <label for="student_search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.search_student') }}</label>
                        <input type="text" name="student_search" id="student_search" value="{{ request('student_search') }}"
                               placeholder="{{ __('teacher.reports.search_student_placeholder') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                        <i class="ri-filter-line"></i>
                        {{ __('teacher.reports.filter') }}
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'students']) }}"
                           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                            <i class="ri-close-line"></i>
                            {{ __('teacher.reports.clear_filters') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Items --}}
    @if($paginatedRows->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($paginatedRows as $row)
                @php
                    $badge = $typeBadgeConfig[$row->entity_type] ?? ['class' => 'bg-gray-100 text-gray-700', 'text' => $row->entity_type, 'icon' => 'ri-file-line', 'iconBg' => 'bg-gradient-to-br from-gray-500 to-gray-600'];

                    $attendanceColor = $row->attendance_rate >= 80 ? 'text-green-600' : ($row->attendance_rate >= 50 ? 'text-amber-600' : 'text-red-600');

                    $metadata = [
                        ['icon' => $badge['icon'], 'text' => $row->entity_name],
                        ['icon' => 'ri-flag-line', 'text' => $badge['text']],
                        ['icon' => 'ri-pie-chart-line', 'text' => __('teacher.reports.attendance_rate') . ': ' . $row->attendance_rate . '%', 'class' => $attendanceColor],
                        ['icon' => 'ri-calendar-check-line', 'text' => __('teacher.reports.sessions_completed') . ': ' . $row->sessions_completed],
                    ];

                    if ($row->avg_performance !== null) {
                        $metadata[] = ['icon' => 'ri-bar-chart-line', 'text' => __('teacher.reports.avg_performance_label') . ': ' . $row->avg_performance . '/10'];
                    }

                    $actions = [];
                    if ($row->report_route) {
                        $actions[] = [
                            'href' => route($row->report_route, array_merge($row->report_params, ['subdomain' => $subdomain])),
                            'label' => __('teacher.reports.view_report'),
                            'icon' => 'ri-eye-line',
                        ];
                    }
                @endphp

                <x-teacher.entity-list-item
                    :title="$row->student_name"
                    :status-badge="$badge['text']"
                    :status-class="$badge['class']"
                    :metadata="$metadata"
                    :actions="$actions"
                    icon="{{ $badge['icon'] }}"
                    icon-bg-class="{{ $badge['iconBg'] }}"
                />
            @endforeach
        </div>

        @if($paginatedRows->hasPages())
            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                {{ $paginatedRows->links() }}
            </div>
        @endif
    @else
        <div class="px-4 md:px-6 py-8 md:py-12 text-center">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-group-line text-xl md:text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('teacher.reports.empty_students_title') }}</h3>
            <p class="text-sm md:text-base text-gray-600">
                @if($hasActiveFilters)
                    {{ __('teacher.reports.empty_students_filter_description') }}
                @else
                    {{ __('teacher.reports.empty_students_description') }}
                @endif
            </p>
            @if($hasActiveFilters)
                <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'students']) }}"
                   class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                    {{ __('teacher.reports.view_all') }}
                </a>
            @endif
        </div>
    @endif
</div>
