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
        'label' => 'لوحة التحكم',
        'url' => route('student.profile', ['subdomain' => $academySubdomain])
    ],
    [
        'label' => $course?->title,
        'url' => route('interactive-courses.show', ['subdomain' => $academySubdomain, 'courseId' => $course?->id])
    ],
    ['label' => 'تقريري']
];

// Header stats
$headerStats = [];
if (isset($progress['total_sessions'])) {
    $headerStats[] = [
        'icon' => 'ri-file-list-line',
        'label' => 'الجلسات المخططة',
        'value' => $progress['total_sessions'] ?? 0
    ];
}

// Build stats grid data
$statsGridData = [
    [
        'label' => 'نسبة حضوري',
        'value' => ($attendance['attendance_rate'] ?? 0) . '%',
        'color' => 'green',
        'icon' => 'ri-user-star-line'
    ],
    [
        'label' => 'الجلسات المكتملة',
        'value' => $progress['sessions_completed'] ?? 0,
        'color' => 'blue',
        'icon' => 'ri-checkbox-circle-line'
    ],
    [
        'label' => 'متوسط أدائي',
        'value' => number_format($performance['average_overall_performance'] ?? 0, 1) . '/10',
        'color' => 'purple',
        'icon' => 'ri-star-line'
    ],
    [
        'label' => 'نسبة التقدم',
        'value' => ($progress['completion_rate'] ?? 0) . '%',
        'color' => 'yellow',
        'icon' => 'ri-pie-chart-line'
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="'تقريري في الكورس - ' . $course?->title . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تقريري الشامل'"
    layoutType="student">

<div>
    <!-- Report Header with Breadcrumbs -->
    <x-reports.report-header
        title="تقريري في الكورس"
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
            title="إحصائيات حضوري" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance"
            title="أدائي الأكاديمي"
            type="interactive" />
    </div>
</div>

</x-reports.layouts.base-report>
