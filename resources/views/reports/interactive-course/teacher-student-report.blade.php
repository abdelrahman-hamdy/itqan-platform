@props([
    'course' => null,
    'student' => null,
    'enrollment' => null,
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
        'label' => auth()->user()->name,
        'url' => route('teacher.profile', ['subdomain' => $academySubdomain])
    ],
    [
        'label' => $course?->title,
        'url' => route('interactive-courses.show', ['subdomain' => $academySubdomain, 'courseId' => $course?->id])
    ],
    [
        'label' => __('teacher.interactive_reports.full_report'),
        'url' => route('teacher.interactive-courses.report', ['subdomain' => $academySubdomain, 'course' => $course?->id])
    ],
    ['label' => __('teacher.interactive_reports.report_for_student', ['student' => $student->name ?? __('teacher.interactive_reports.unknown_student')])]
];

// Header stats
$headerStats = [];
if (isset($enrollment)) {
    $headerStats[] = [
        'icon' => 'ri-calendar-line',
        'label' => __('teacher.interactive_reports.join_date'),
        'value' => $enrollment->created_at?->format('Y-m-d') ?? '-'
    ];
}

// Build stats grid data
$statsGridData = [
    [
        'label' => __('teacher.interactive_reports.attendance_rate'),
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
        'label' => __('teacher.interactive_reports.performance'),
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

// Add homework metrics if available
if (isset($progress['homework_completion_rate'])) {
    $statsGridData[] = [
        'label' => __('teacher.interactive_reports.homework_completion'),
        'value' => $progress['homework_completion_rate'] . '%',
        'color' => 'indigo',
        'icon' => 'ri-file-list-3-line'
    ];
}
@endphp

<x-reports.layouts.base-report
    :title="__('teacher.interactive_reports.student_report_title', ['course' => $course?->title]) . ' - ' . config('app.name', __('teacher.interactive_reports.platform_name'))"
    :description="__('teacher.interactive_reports.student_report_description')"
    layoutType="teacher">

<div>
    <!-- Report Header with Breadcrumbs -->
    <x-reports.report-header
        :title="__('teacher.interactive_reports.student_report_header')"
        :subtitle="($student->name ?? __('teacher.interactive_reports.unknown_student')) . ' - ' . $course?->title"
        :breadcrumbs="$breadcrumbs"
        :stats="$headerStats" />

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            :title="__('teacher.interactive_reports.attendance_stats')" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance"
            :title="__('teacher.interactive_reports.academic_performance')"
            type="interactive" />
    </div>
</div>

</x-reports.layouts.base-report>
