@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $attendanceFilterOptions = [
        '' => __('teacher.reports.all_statuses'),
        \App\Enums\AttendanceStatus::ATTENDED->value => __('teacher.reports.status_attended'),
        \App\Enums\AttendanceStatus::LATE->value => __('teacher.reports.status_late'),
        \App\Enums\AttendanceStatus::ABSENT->value => __('teacher.reports.status_absent'),
        \App\Enums\AttendanceStatus::LEFT->value => __('teacher.reports.status_left'),
    ];

    $typeFilterOptions = [
        '' => __('teacher.reports.all_types'),
        'quran' => __('teacher.reports.type_quran'),
        'academic' => __('teacher.reports.type_academic'),
        'interactive' => __('teacher.reports.type_interactive'),
    ];

    $hasActiveFilters = request('report_type') || request('entity_id') || request('student_search') || request('date_from') || request('date_to') || request('attendance_status');
@endphp

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-file-chart-line text-lg md:text-xl text-indigo-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $totalReports ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.total_reports') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-checkbox-circle-line text-lg md:text-xl text-green-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $presentCount ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.present_count') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-time-line text-lg md:text-xl text-amber-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $lateCount ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.late_count') }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-close-circle-line text-lg md:text-xl text-red-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $absentCount ?? 0 }}</p>
                <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.reports.absent_count') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Reports List Card --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    {{-- List Header --}}
    <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
        <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.reports.tab_session_reports') }} ({{ $paginatedReports->total() }})</h2>
    </div>

    {{-- Collapsible Filters --}}
    <div x-data="{
        open: {{ $hasActiveFilters ? 'true' : 'false' }},
        selectedType: '{{ request('report_type', '') }}',
        entityOptions: {{ Js::from($entityOptions ?? []) }},
        get filteredEntities() {
            const typeMap = { 'quran': ['quran_individual', 'quran_group'], 'academic': ['academic'], 'interactive': ['interactive'] };
            if (!this.selectedType) return [];
            const keys = typeMap[this.selectedType] || [];
            let result = [];
            keys.forEach(k => { if (this.entityOptions[k]) result = result.concat(this.entityOptions[k]); });
            return result;
        }
    }" class="border-b border-gray-200">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <span class="flex items-center gap-2">
                <i class="ri-filter-3-line text-indigo-500"></i>
                {{ __('teacher.reports.filter') }}
                @if($hasActiveFilters)
                    @php
                        $filterCount = (request('report_type') ? 1 : 0) + (request('entity_id') ? 1 : 0) + (request('student_search') ? 1 : 0)
                            + (request('date_from') ? 1 : 0) + (request('date_to') ? 1 : 0) + (request('attendance_status') ? 1 : 0);
                    @endphp
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-indigo-500 rounded-full">{{ $filterCount }}</span>
                @endif
            </span>
            <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        <div x-show="open" x-collapse>
            <form method="GET" action="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                <input type="hidden" name="tab" value="sessions">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                    {{-- Report Type Filter --}}
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.report_type') }}</label>
                        <select name="report_type" id="report_type" x-model="selectedType"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($typeFilterOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('report_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Entity Filter (cascading) --}}
                    <div>
                        <label for="session_entity_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.filter_entity') }}</label>
                        <select name="entity_id" id="session_entity_id"
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
                        <label for="session_student_search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.search_student') }}</label>
                        <input type="text" name="student_search" id="session_student_search" value="{{ request('student_search') }}"
                               placeholder="{{ __('teacher.reports.search_student_placeholder') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Date From --}}
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.date_from') }}</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.date_to') }}</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Attendance Status --}}
                    <div>
                        <label for="attendance_status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.all_statuses') }}</label>
                        <select name="attendance_status" id="attendance_status"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($attendanceFilterOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('attendance_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                        <i class="ri-filter-line"></i>
                        {{ __('teacher.reports.filter') }}
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'sessions']) }}"
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
    @if($paginatedReports->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($paginatedReports as $report)
                @php
                    $attendanceConfig = match($report->attendance_status) {
                        \App\Enums\AttendanceStatus::ATTENDED => ['class' => 'bg-green-100 text-green-700', 'text' => __('teacher.reports.status_attended')],
                        \App\Enums\AttendanceStatus::LATE => ['class' => 'bg-amber-100 text-amber-700', 'text' => __('teacher.reports.status_late')],
                        \App\Enums\AttendanceStatus::LEFT => ['class' => 'bg-orange-100 text-orange-700', 'text' => __('teacher.reports.status_left')],
                        \App\Enums\AttendanceStatus::ABSENT => ['class' => 'bg-red-100 text-red-700', 'text' => __('teacher.reports.status_absent')],
                        default => ['class' => 'bg-gray-100 text-gray-500', 'text' => __('teacher.reports.status_unknown')],
                    };

                    if ($report instanceof \App\Models\StudentSessionReport) {
                        $typeLabel = __('teacher.reports.type_quran');
                        $typeIcon = 'ri-book-open-line';
                        $iconBg = 'bg-gradient-to-br from-green-500 to-green-600';
                        $sessionTitle = $report->session?->title ?? __('teacher.reports.quran_session');
                    } elseif ($report instanceof \App\Models\AcademicSessionReport) {
                        $typeLabel = __('teacher.reports.type_academic');
                        $typeIcon = 'ri-graduation-cap-line';
                        $iconBg = 'bg-gradient-to-br from-violet-500 to-violet-600';
                        $sessionTitle = $report->session?->title ?? __('teacher.reports.academic_session');
                    } else {
                        $typeLabel = __('teacher.reports.type_interactive');
                        $typeIcon = 'ri-live-line';
                        $iconBg = 'bg-gradient-to-br from-purple-500 to-purple-600';
                        $sessionTitle = $report->session?->course?->title ?? $report->session?->title ?? __('teacher.reports.interactive_session');
                    }

                    $metadata = [
                        ['icon' => 'ri-user-line', 'text' => $report->student?->name ?? __('teacher.reports.unknown_student')],
                        ['icon' => $typeIcon, 'text' => $typeLabel],
                    ];

                    if ($report->created_at) {
                        $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $report->created_at->format('Y/m/d')];
                    }

                    if ($report->overall_performance !== null) {
                        $metadata[] = ['icon' => 'ri-bar-chart-line', 'text' => __('teacher.reports.performance') . ': ' . $report->overall_performance . '/10'];
                    }

                    if ($report->actual_attendance_minutes) {
                        $metadata[] = ['icon' => 'ri-timer-line', 'text' => $report->actual_attendance_minutes . ' ' . __('teacher.reports.minutes')];
                    }
                @endphp

                <x-teacher.entity-list-item
                    :title="$sessionTitle"
                    :status-badge="$attendanceConfig['text']"
                    :status-class="$attendanceConfig['class']"
                    :metadata="$metadata"
                    :description="$report->notes"
                    icon="{{ $typeIcon }}"
                    icon-bg-class="{{ $iconBg }}"
                />
            @endforeach
        </div>

        @if($paginatedReports->hasPages())
            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                {{ $paginatedReports->links() }}
            </div>
        @endif
    @else
        <div class="px-4 md:px-6 py-8 md:py-12 text-center">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-file-chart-line text-xl md:text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('teacher.reports.empty_title') }}</h3>
            <p class="text-sm md:text-base text-gray-600">
                @if($hasActiveFilters)
                    {{ __('teacher.reports.empty_filter_description') }}
                @else
                    {{ __('teacher.reports.empty_description') }}
                @endif
            </p>
            @if($hasActiveFilters)
                <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'sessions']) }}"
                   class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                    {{ __('teacher.reports.view_all') }}
                </a>
            @endif
        </div>
    @endif
</div>
