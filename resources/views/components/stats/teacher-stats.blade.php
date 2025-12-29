<!-- Teacher Stats Component -->
@php
$teacherStats = [
    [
        'label' => 'إجمالي الطلاب',
        'value' => $stats['totalStudents'] ?? 0,
        'icon' => 'ri-group-line',
        'color' => 'blue',
        'subtitle' => isset($stats['activeCircles'])
            ? ($stats['activeCircles'] . ' دائرة نشطة')
            : (isset($stats['activeCourses']) ? ($stats['activeCourses'] . ' دورة نشطة') : null),
    ],
    [
        'label' => 'جلسات هذا الشهر',
        'value' => $stats['thisMonthSessions'] ?? 0,
        'icon' => 'ri-calendar-check-line',
        'color' => 'green',
        'subtitle' => 'جلسة مكتملة',
    ],
    [
        'label' => 'أرباح هذا الشهر',
        'value' => number_format($stats['monthlyEarnings'] ?? 0, 0),
        'icon' => 'ri-money-dollar-circle-line',
        'color' => 'yellow',
        'subtitle' => 'ريال سعودي',
    ],
    [
        'label' => 'تقييم المعلم',
        'value' => number_format($stats['teacherRating'] ?? 0, 1),
        'icon' => 'ri-star-line',
        'color' => 'purple',
        'subtitle' => 'من 5 نجوم',
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
                    <i class="{{ $stat['icon'] }} ml-1"></i>
                    {{ $stat['subtitle'] }}
                </p>
            @endif
        </x-ui.stat-card>
    @endforeach
</div>