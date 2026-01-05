@props([
    'course' => null,
    'student' => null,
    'attendance' => null,
    'performance' => null,
    'progress' => null,
])

@php
$academySubdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

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

// Build breadcrumbs
$breadcrumbs = [
    [
        'label' => __('teacher.interactive_reports.dashboard'),
        'url' => route('student.profile', ['subdomain' => $academySubdomain])
    ],
    [
        'label' => $course?->title,
        'url' => route('interactive-courses.show', ['subdomain' => $academySubdomain, 'courseId' => $course?->id])
    ],
    ['label' => __('teacher.interactive_reports.my_report')]
];

// Header stats
$headerStats = [];
if (isset($progress['total_sessions'])) {
    $headerStats[] = [
        'icon' => 'ri-file-list-line',
        'label' => __('teacher.interactive_reports.planned_sessions'),
        'value' => $progress['total_sessions'] ?? 0
    ];
}

// Build stats grid data
$statsGridData = [
    [
        'label' => __('teacher.interactive_reports.my_attendance'),
        'value' => ($attendance['attendance_rate'] ?? 0) . '%',
        'color' => 'green',
        'icon' => 'ri-user-star-line'
    ],
    [
        'label' => __('teacher.interactive_reports.completed_sessions'),
        'value' => $progress['sessions_completed'] ?? 0,
        'color' => 'blue',
        'icon' => 'ri-checkbox-circle-line'
    ],
    [
        'label' => __('teacher.interactive_reports.my_performance'),
        'value' => number_format($performance['average_overall_performance'] ?? 0, 1) . '/10',
        'color' => 'purple',
        'icon' => 'ri-star-line'
    ],
    [
        'label' => __('teacher.interactive_reports.progress_rate'),
        'value' => ($progress['completion_rate'] ?? 0) . '%',
        'color' => 'yellow',
        'icon' => 'ri-pie-chart-line'
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="__('teacher.interactive_reports.my_report_title', ['course' => $course?->title]) . ' - ' . config('app.name', __('teacher.interactive_reports.platform_name'))"
    :description="__('teacher.interactive_reports.my_report_description')"
    layoutType="student">

<div>
    <!-- Report Header with Breadcrumbs -->
    <x-reports.report-header
        :title="__('teacher.interactive_reports.my_report_header')"
        :subtitle="$course?->title"
        :breadcrumbs="$breadcrumbs"
        :stats="$headerStats" />

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            :title="__('teacher.interactive_reports.my_attendance_stats')" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance"
            :title="__('teacher.interactive_reports.my_academic_performance')"
            type="interactive" />
    </div>
</div>

</x-reports.layouts.base-report>
