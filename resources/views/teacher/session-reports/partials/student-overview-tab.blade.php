@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $typeOptions = [
        '' => __('teacher.reports.all_types'),
        'quran_individual' => __('teacher.reports.type_quran_individual'),
        'quran_group' => __('teacher.reports.type_quran_group'),
        'academic' => __('teacher.reports.type_academic_lesson'),
        'interactive' => __('teacher.reports.type_interactive_course'),
    ];

    $stats = [
        [
            'icon' => 'ri-group-line',
            'bgColor' => 'bg-indigo-100',
            'iconColor' => 'text-indigo-600',
            'value' => $totalStudents ?? 0,
            'label' => __('teacher.reports.total_students'),
        ],
        [
            'icon' => 'ri-pie-chart-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => ($avgAttendance ?? 0) . '%',
            'label' => __('teacher.reports.avg_attendance'),
        ],
        [
            'icon' => 'ri-book-open-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $totalEntities ?? 0,
            'label' => __('teacher.reports.total_entities'),
        ],
    ];

    $typeBadgeConfig = [
        'quran_individual' => ['class' => 'bg-green-100 text-green-700', 'text' => __('teacher.reports.type_quran_individual'), 'icon' => 'ri-book-open-line', 'iconBg' => 'bg-gradient-to-br from-green-500 to-green-600'],
        'quran_group' => ['class' => 'bg-emerald-100 text-emerald-700', 'text' => __('teacher.reports.type_quran_group'), 'icon' => 'ri-team-line', 'iconBg' => 'bg-gradient-to-br from-emerald-500 to-emerald-600'],
        'academic' => ['class' => 'bg-violet-100 text-violet-700', 'text' => __('teacher.reports.type_academic_lesson'), 'icon' => 'ri-graduation-cap-line', 'iconBg' => 'bg-gradient-to-br from-violet-500 to-violet-600'],
        'interactive' => ['class' => 'bg-purple-100 text-purple-700', 'text' => __('teacher.reports.type_interactive_course'), 'icon' => 'ri-live-line', 'iconBg' => 'bg-gradient-to-br from-purple-500 to-purple-600'],
    ];
@endphp

{{-- Filter Bar --}}
<div class="mb-4 md:mb-6" x-data="{
    selectedType: '{{ request('type', '') }}',
    entityOptions: {{ Js::from($entityOptions ?? []) }},
    get filteredEntities() {
        if (!this.selectedType || !this.entityOptions[this.selectedType]) return [];
        return this.entityOptions[this.selectedType];
    }
}">
    <form method="GET" action="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="students">

        {{-- Type Filter --}}
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.filter_type') }}</label>
            <select name="type" id="type" x-model="selectedType"
                    class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Entity Filter (cascading) --}}
        <div x-show="filteredEntities.length > 0" x-cloak>
            <label for="entity_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.filter_entity') }}</label>
            <select name="entity_id" id="entity_id"
                    class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                   class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
            <i class="ri-filter-line"></i>
            {{ __('teacher.reports.filter') }}
        </button>

        @if(request('type') || request('entity_id') || request('student_search'))
            <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'students']) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                <i class="ri-close-line"></i>
                {{ __('teacher.reports.clear_filters') }}
            </a>
        @endif
    </form>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6 mb-6 md:mb-8">
    @foreach($stats as $stat)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 {{ $stat['bgColor'] }} rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                    <i class="{{ $stat['icon'] }} {{ $stat['iconColor'] }} text-lg md:text-xl"></i>
                </div>
                <div>
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $stat['value'] }}</div>
                    <div class="text-xs md:text-sm text-gray-600">{{ $stat['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Student List --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
        <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.reports.tab_student_overview') }}</h2>
    </div>

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
                            'text' => __('teacher.reports.view_report'),
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
                @if(request('type') || request('entity_id') || request('student_search'))
                    {{ __('teacher.reports.empty_students_filter_description') }}
                @else
                    {{ __('teacher.reports.empty_students_description') }}
                @endif
            </p>
            @if(request('type') || request('entity_id') || request('student_search'))
                <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'students']) }}"
                   class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                    {{ __('teacher.reports.view_all') }}
                </a>
            @endif
        </div>
    @endif
</div>
