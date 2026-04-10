<!-- Teacher Stats Component -->
@php
$teacherStats = [
    [
        'label' => __('teacher.quick_stats.total_students'),
        'value' => $stats['totalStudents'] ?? 0,
        'icon' => 'ri-group-line',
        'color' => 'blue',
        'subtitle' => isset($stats['activeCircles'])
            ? __('teacher.quick_stats.individual_students_label')
            : (isset($stats['activeCourses']) ? __('teacher.quick_stats.private_students_label') : null),
    ],
    [
        'label' => __('teacher.quick_stats.month_duration'),
        'value' => floor(($stats['thisMonthDuration'] ?? 0) / 60),
        'icon' => 'ri-time-line',
        'color' => 'purple',
        'subtitle' => __('teacher.quick_stats.hours'),
    ],
    [
        'label' => __('teacher.quick_stats.month_earnings'),
        'value' => number_format($stats['monthlyEarnings'] ?? 0, 0),
        'icon' => 'ri-money-dollar-circle-line',
        'color' => 'green',
        'subtitle' => $currencySymbol ?? __('teacher.quick_stats.currency'),
    ],
    [
        'label' => __('teacher.quick_stats.teacher_rating'),
        'value' => number_format($stats['teacherRating'] ?? 0, 1),
        'icon' => 'ri-star-line',
        'color' => 'yellow',
        'subtitle' => __('teacher.quick_stats.out_of_stars'),
    ],
];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @foreach($teacherStats as $stat)
        <x-ui.stat-card
            :label="$stat['label']"
            :value="$stat['value']"
            :icon="$stat['icon']"
            :color="$stat['color']"
        >
            @if($stat['subtitle'])
                <p class="text-xs text-{{ $stat['color'] }}-600 mt-1">
                    <i class="{{ $stat['icon'] }} ms-1"></i>
                    {{ $stat['subtitle'] }}
                </p>
            @endif
        </x-ui.stat-card>
    @endforeach
</div>
