<x-layouts.teacher :title="__('teacher.reports.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $filterOptions = [
        '' => __('teacher.reports.all_statuses'),
        \App\Enums\AttendanceStatus::ATTENDED->value => __('teacher.reports.status_attended'),
        \App\Enums\AttendanceStatus::LATE->value => __('teacher.reports.status_late'),
        \App\Enums\AttendanceStatus::ABSENT->value => __('teacher.reports.status_absent'),
        \App\Enums\AttendanceStatus::LEFT->value => __('teacher.reports.status_left'),
    ];

    $stats = [
        [
            'icon' => 'ri-file-chart-line',
            'bgColor' => 'bg-indigo-100',
            'iconColor' => 'text-indigo-600',
            'value' => $totalReports ?? 0,
            'label' => __('teacher.reports.total_reports'),
        ],
        [
            'icon' => 'ri-checkbox-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $presentCount ?? 0,
            'label' => __('teacher.reports.present_count'),
        ],
        [
            'icon' => 'ri-time-line',
            'bgColor' => 'bg-amber-100',
            'iconColor' => 'text-amber-600',
            'value' => $lateCount ?? 0,
            'label' => __('teacher.reports.late_count'),
        ],
        [
            'icon' => 'ri-close-circle-line',
            'bgColor' => 'bg-red-100',
            'iconColor' => 'text-red-600',
            'value' => $absentCount ?? 0,
            'label' => __('teacher.reports.absent_count'),
        ],
    ];
@endphp

    {{-- Date Filter Form --}}
    <div class="mb-4 md:mb-6">
        <form method="GET" action="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.date_from') }}</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                       class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.reports.date_to') }}</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                       class="min-h-[44px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            @if(request('attendance_status'))
                <input type="hidden" name="attendance_status" value="{{ request('attendance_status') }}">
            @endif
            <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                <i class="ri-filter-line"></i>
                {{ __('teacher.reports.filter') }}
            </button>
            @if(request('date_from') || request('date_to'))
                <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain]) }}"
                   class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    <i class="ri-close-line"></i>
                    {{ __('teacher.reports.clear_filters') }}
                </a>
            @endif
        </form>
    </div>

    <x-teacher.entity-list-page
        :title="__('teacher.reports.page_title')"
        :subtitle="__('teacher.reports.page_description')"
        :items="$paginatedReports"
        :stats="$stats"
        :filter-options="$filterOptions"
        filter-param="attendance_status"
        :breadcrumbs="[['label' => __('teacher.reports.breadcrumb')]]"
        theme-color="indigo"
        :list-title="__('teacher.reports.list_title')"
        empty-icon="ri-file-chart-line"
        :empty-title="__('teacher.reports.empty_title')"
        :empty-description="__('teacher.reports.empty_description')"
        :empty-filter-description="__('teacher.reports.empty_filter_description')"
        :clear-filter-route="route('teacher.session-reports.index', ['subdomain' => $subdomain])"
        :clear-filter-text="__('teacher.reports.view_all')"
    >
        @foreach($paginatedReports as $report)
            @php
                $attendanceConfig = match($report->attendance_status) {
                    \App\Enums\AttendanceStatus::ATTENDED => ['class' => 'bg-green-100 text-green-700', 'text' => __('teacher.reports.status_attended')],
                    \App\Enums\AttendanceStatus::LATE => ['class' => 'bg-amber-100 text-amber-700', 'text' => __('teacher.reports.status_late')],
                    \App\Enums\AttendanceStatus::LEFT => ['class' => 'bg-orange-100 text-orange-700', 'text' => __('teacher.reports.status_left')],
                    \App\Enums\AttendanceStatus::ABSENT => ['class' => 'bg-red-100 text-red-700', 'text' => __('teacher.reports.status_absent')],
                    default => ['class' => 'bg-gray-100 text-gray-500', 'text' => __('teacher.reports.status_unknown')],
                };

                // Determine report type label and icon
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
    </x-teacher.entity-list-page>
</x-layouts.teacher>
