<!-- Teacher Stats Component -->
@php
$teacherStats = [
    [
        'label' => __('teacher.quick_stats.total_students'),
        'value' => $stats['totalStudents'] ?? 0,
        'icon' => 'ri-group-line',
        'color' => 'blue',
        'subtitle' => isset($stats['activeCircles'])
            ? ($stats['activeCircles'] . ' ' . ($stats['activeCircles'] > 1 ? __('teacher.quick_stats.active_circles_plural') : __('teacher.quick_stats.active_circles')))
            : (isset($stats['activeCourses']) ? ($stats['activeCourses'] . ' ' . ($stats['activeCourses'] > 1 ? __('teacher.quick_stats.active_courses_plural') : __('teacher.quick_stats.active_courses'))) : null),
    ],
    [
        'label' => __('teacher.quick_stats.month_sessions'),
        'value' => $stats['thisMonthSessions'] ?? 0,
        'icon' => 'ri-calendar-check-line',
        'color' => 'purple',
        'subtitle' => ($stats['thisMonthSessions'] ?? 0) > 1 ? __('teacher.quick_stats.completed_sessions') : __('teacher.quick_stats.completed_session'),
    ],
    [
        'label' => __('teacher.quick_stats.month_earnings'),
        'value' => number_format($stats['monthlyEarnings'] ?? 0, 0),
        'icon' => 'ri-money-dollar-circle-line',
        'color' => 'green',
        'subtitle' => __('teacher.quick_stats.currency'),
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
