@props([
    'layoutType' => 'student', // student, teacher
    'circle' => null,
    'circleType' => 'individual', // individual, group
    'student' => null,
    'attendance' => null,
    'performance' => null,
    'progress' => null,
    'trends' => null,
    'enrollment' => null,
    'overall' => null,
    'filterPeriod' => 'all',
    'customStartDate' => '',
    'customEndDate' => '',
])

@php
$isStudent = $layoutType === 'student';
$isTeacher = $layoutType === 'teacher';
$isIndividual = $circleType === 'individual';
$isGroup = $circleType === 'group';

// Determine page title and subtitle
$pageTitle = $isStudent ? __('components.reports.quran.my_circle_report') : __('components.reports.common.student_report');
$pageSubtitle = $isIndividual
    ? ($isStudent ? __('components.reports.quran.individual_circle') : $student?->name . ' - ' . __('components.reports.quran.individual_circle'))
    : ($isStudent ? $circle?->name : $student?->name . ' - ' . $circle?->name);

// Build breadcrumbs
$academySubdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
$breadcrumbs = [];

if ($isStudent) {
    $breadcrumbs[] = [
        'label' => __('components.reports.common.dashboard'),
        'url' => route('student.profile', ['subdomain' => $academySubdomain])
    ];

    if ($isIndividual) {
        $breadcrumbs[] = [
            'label' => __('components.reports.quran.my_individual_circle'),
            'url' => route('individual-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    } else {
        $breadcrumbs[] = [
            'label' => $circle?->name,
            'url' => route('quran-circles.show', ['subdomain' => $academySubdomain, 'circleId' => $circle?->id])
        ];
    }

    $breadcrumbs[] = ['label' => __('components.reports.common.my_report')];
} else {
    $breadcrumbs[] = [
        'label' => auth()->user()->name,
        'url' => route('teacher.profile', ['subdomain' => $academySubdomain])
    ];

    if ($isIndividual) {
        $breadcrumbs[] = [
            'label' => __('components.reports.quran.individual_circles'),
            'url' => route('teacher.individual-circles.index', ['subdomain' => $academySubdomain])
        ];
        $breadcrumbs[] = [
            'label' => $student?->name,
            'url' => route('individual-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    } else {
        $breadcrumbs[] = [
            'label' => __('components.reports.quran.group_circles'),
            'url' => route('teacher.group-circles.index', ['subdomain' => $academySubdomain])
        ];
        $breadcrumbs[] = [
            'label' => $circle?->name,
            'url' => route('teacher.group-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    }

    $breadcrumbs[] = ['label' => __('components.reports.common.student_report') . ' ' . $student?->name];
}

// Build header stats
$headerStats = [];
if ($isIndividual && isset($overall)) {
    $headerStats = [
        [
            'icon' => 'ri-calendar-line',
            'label' => __('components.reports.quran.start_date'),
            'value' => $overall['started_at'] ? $overall['started_at']->format('Y-m-d') : __('components.reports.common.not_started')
        ],
        [
            'icon' => 'ri-file-list-line',
            'label' => __('components.reports.quran.planned_sessions'),
            'value' => $overall['total_sessions_planned'] ?? 0
        ],
        [
            'icon' => 'ri-time-line',
            'label' => __('components.reports.quran.remaining_sessions'),
            'value' => $overall['sessions_remaining'] ?? 0
        ],
    ];
} elseif ($isGroup && isset($enrollment)) {
    $headerStats = [
        [
            'icon' => 'ri-calendar-line',
            'label' => __('components.reports.quran.join_date'),
            'value' => $enrollment['enrolled_at'] ? $enrollment['enrolled_at']->format('Y-m-d') : '-'
        ],
    ];
}

// Convert DTO objects to arrays for array-style access in Blade
if (is_object($attendance) && method_exists($attendance, 'toArray')) {
    $attendance = $attendance->toArray();
}
if (is_object($performance) && method_exists($performance, 'toArray')) {
    $performance = $performance->toArray();
}
if (is_object($progress) && method_exists($progress, 'toArray')) {
    $progress = $progress->toArray();
}

// Build stats grid data
$statsGridData = [
    [
        'label' => $isStudent ? __('components.reports.quran.my_attendance_rate') : __('components.reports.quran.attendance_rate'),
        'value' => ($attendance['attendance_rate'] ?? 0) . '%',
        'color' => 'green',
        'icon' => 'ri-user-star-line'
    ],
    [
        'label' => __('components.reports.quran.pages_memorized'),
        'value' => number_format($progress['pages_memorized'] ?? 0, 1),
        'color' => 'purple',
        'icon' => 'ri-book-open-line'
    ],
    [
        'label' => __('components.reports.quran.pages_reviewed'),
        'value' => $progress['pages_reviewed'] ?? 0,
        'color' => 'blue',
        'icon' => 'ri-refresh-line'
    ],
    [
        'label' => $isStudent ? __('components.reports.quran.my_overall_assessment') : __('components.reports.quran.overall_assessment'),
        'value' => ($progress['overall_assessment'] ?? 0) . '/10',
        'color' => 'yellow',
        'icon' => 'ri-star-line'
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="$pageTitle . ' - ' . config('app.name', __('components.reports.common.app_name'))"
    :description="__('components.reports.common.comprehensive_report')"
    :layoutType="$layoutType">

<div>
    <!-- Report Header with Breadcrumbs -->
    <x-reports.report-header
        :title="$pageTitle"
        :subtitle="$pageSubtitle"
        :breadcrumbs="$breadcrumbs"
        :stats="$headerStats" />

    <!-- Date Range Filter -->
    <x-reports.date-range-filter
        :filterPeriod="$filterPeriod"
        :customStartDate="$customStartDate"
        :customEndDate="$customEndDate" />

    <!-- Performance Trend Chart -->
    @if(isset($trends))
        <x-reports.trend-chart
            :data="$trends"
            :title="$isStudent ? __('components.reports.quran.my_performance_progress') : __('components.reports.quran.performance_progress')" />
    @endif

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            :title="$isStudent ? __('components.reports.quran.my_attendance_stats') : __('components.reports.quran.attendance_stats')" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance ?? $progress"
            :title="$isStudent ? __('components.reports.quran.my_memorization_performance') : __('components.reports.quran.overall_assessment')"
            type="quran" />
    </div>
</div>

</x-reports.layouts.base-report>
