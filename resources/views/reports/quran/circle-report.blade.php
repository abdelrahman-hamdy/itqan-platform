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
$pageTitle = $isStudent ? 'تقريري في الحلقة' : 'تقرير الطالب';
$pageSubtitle = $isIndividual
    ? ($isStudent ? 'حلقة فردية' : $student?->name . ' - حلقة فردية')
    : ($isStudent ? $circle?->name : $student?->name . ' - ' . $circle?->name);

// Build breadcrumbs
$academySubdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
$breadcrumbs = [];

if ($isStudent) {
    $breadcrumbs[] = [
        'label' => 'لوحة التحكم',
        'url' => route('student.profile', ['subdomain' => $academySubdomain])
    ];

    if ($isIndividual) {
        $breadcrumbs[] = [
            'label' => 'حلقتي الفردية',
            'url' => route('individual-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    } else {
        $breadcrumbs[] = [
            'label' => $circle?->name,
            'url' => route('quran-circles.show', ['subdomain' => $academySubdomain, 'circleId' => $circle?->id])
        ];
    }

    $breadcrumbs[] = ['label' => 'تقريري'];
} else {
    $breadcrumbs[] = [
        'label' => auth()->user()->name,
        'url' => route('teacher.profile', ['subdomain' => $academySubdomain])
    ];

    if ($isIndividual) {
        $breadcrumbs[] = [
            'label' => 'الحلقات الفردية',
            'url' => route('teacher.individual-circles.index', ['subdomain' => $academySubdomain])
        ];
        $breadcrumbs[] = [
            'label' => $student?->name,
            'url' => route('individual-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    } else {
        $breadcrumbs[] = [
            'label' => 'الحلقات الجماعية',
            'url' => route('teacher.group-circles.index', ['subdomain' => $academySubdomain])
        ];
        $breadcrumbs[] = [
            'label' => $circle?->name,
            'url' => route('teacher.group-circles.show', ['subdomain' => $academySubdomain, 'circle' => $circle?->id])
        ];
    }

    $breadcrumbs[] = ['label' => 'تقرير ' . $student?->name];
}

// Build header stats
$headerStats = [];
if ($isIndividual && isset($overall)) {
    $headerStats = [
        [
            'icon' => 'ri-calendar-line',
            'label' => 'تاريخ البداية',
            'value' => $overall['started_at'] ? $overall['started_at']->format('Y-m-d') : 'لم تبدأ'
        ],
        [
            'icon' => 'ri-file-list-line',
            'label' => 'الجلسات المخططة',
            'value' => $overall['total_sessions_planned'] ?? 0
        ],
        [
            'icon' => 'ri-time-line',
            'label' => 'الجلسات المتبقية',
            'value' => $overall['sessions_remaining'] ?? 0
        ],
    ];
} elseif ($isGroup && isset($enrollment)) {
    $headerStats = [
        [
            'icon' => 'ri-calendar-line',
            'label' => 'تاريخ الانضمام',
            'value' => $enrollment['enrolled_at'] ? $enrollment['enrolled_at']->format('Y-m-d') : '-'
        ],
    ];
}

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

// Build stats grid data
$statsGridData = [
    [
        'label' => $isStudent ? 'نسبة حضوري' : 'نسبة الحضور',
        'value' => ($attendance['attendance_rate'] ?? 0) . '%',
        'color' => 'green',
        'icon' => 'ri-user-star-line'
    ],
    [
        'label' => 'الصفحات المحفوظة',
        'value' => number_format($progress['pages_memorized'] ?? 0, 1),
        'color' => 'purple',
        'icon' => 'ri-book-open-line'
    ],
    [
        'label' => 'الصفحات المُراجعة',
        'value' => $progress['pages_reviewed'] ?? 0,
        'color' => 'blue',
        'icon' => 'ri-refresh-line'
    ],
    [
        'label' => $isStudent ? 'تقييمي العام' : 'التقييم العام',
        'value' => ($progress['overall_assessment'] ?? 0) . '/10',
        'color' => 'yellow',
        'icon' => 'ri-star-line'
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="$pageTitle . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الشامل'"
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
            :title="$isStudent ? 'تطور أدائي' : 'تطور الأداء'" />
    @endif

    <!-- Stats Grid -->
    <x-reports.stats-grid :stats="$statsGridData" />

    <!-- Attendance and Performance Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Summary -->
        <x-reports.attendance-summary
            :data="$attendance"
            :title="$isStudent ? 'إحصائيات حضوري' : 'إحصائيات الحضور'" />

        <!-- Performance Summary -->
        <x-reports.performance-summary
            :data="$performance ?? $progress"
            :title="$isStudent ? 'أدائي في الحفظ' : 'التقييم العام'"
            type="quran" />
    </div>
</div>

</x-reports.layouts.base-report>
