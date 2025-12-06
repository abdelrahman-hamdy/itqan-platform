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

// Convert DTOs to arrays for backward compatibility
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
    ['label' => 'تقرير الطالب']
];

// Build stats grid data
$statsGridData = [
    [
        'label' => 'نسبة الحضور',
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
        'label' => 'متوسط الأداء',
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
    :title="'تقرير الطالب - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الشامل'"
    layoutType="teacher">

<div>
    <!-- Report Header -->
    <x-reports.report-header
        title="تقرير الطالب"
        :subtitle="($student->name ?? 'الطالب') . ' - ' . ($subject->name ?? 'الدرس الأكاديمي')"
        :breadcrumbs="$breadcrumbs" />

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            title="إحصائيات الحضور" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance"
            title="الأداء الأكاديمي"
            type="academic" />
    </div>
</div>

</x-reports.layouts.base-report>
