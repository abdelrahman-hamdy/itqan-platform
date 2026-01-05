@props([
    'subscription' => null,
    'student' => null,
    'subject' => null,
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
    ['label' => __('components.reports.common.student_report')]
];

// Build stats grid data
$statsGridData = [
    [
        'label' => __('components.reports.academic.attendance_rate'),
        'value' => ($attendance['attendance_rate'] ?? 0) . '%',
        'color' => 'green',
        'icon' => 'ri-user-star-line'
    ],
    [
        'label' => __('components.reports.academic.completed_sessions'),
        'value' => $progress['sessions_completed'] ?? 0,
        'color' => 'blue',
        'icon' => 'ri-checkbox-circle-line'
    ],
    [
        'label' => __('components.reports.academic.average_performance'),
        'value' => number_format($performance['average_overall_performance'] ?? 0, 1) . '/10',
        'color' => 'purple',
        'icon' => 'ri-star-line'
    ],
    [
        'label' => __('components.reports.academic.progress_rate'),
        'value' => ($progress['completion_rate'] ?? 0) . '%',
        'color' => 'yellow',
        'icon' => 'ri-pie-chart-line'
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="__('components.reports.common.student_report') . ' - ' . config('app.name', __('components.reports.common.app_name'))"
    :description="__('components.reports.common.comprehensive_report')"
    layoutType="teacher">

<div>
    <!-- Report Header -->
    <x-reports.report-header
        :title="__('components.reports.common.student_report')"
        :subtitle="($student->name ?? __('components.reports.academic.student')) . ' - ' . ($subject->name ?? __('components.reports.academic.academic_lesson'))"
        :breadcrumbs="$breadcrumbs" />

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            :title="__('components.reports.academic.attendance_stats')" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance"
            :title="__('components.reports.academic.academic_performance')"
            type="academic" />
    </div>
</div>

</x-reports.layouts.base-report>
