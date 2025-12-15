@extends('components.layouts.teacher')

@section('title', 'الحلقات الجماعية - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => 'الحلقات الجماعية'],
    ];

    $filterOptions = [
        '' => 'جميع الحلقات',
        'active' => 'نشطة',
        'full' => 'مكتملة العدد',
        'paused' => 'متوقفة',
        'closed' => 'مغلقة',
    ];

    $stats = [
        [
            'icon' => 'ri-group-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->total(),
            'label' => 'إجمالي الحلقات',
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->where('status', 'active')->count(),
            'label' => 'حلقات نشطة',
        ],
        [
            'icon' => 'ri-user-add-line',
            'bgColor' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'value' => $circles->where('status', 'full')->count(),
            'label' => 'مكتملة العدد',
        ],
        [
            'icon' => 'ri-team-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->sum('enrolled_students') ?? 0,
            'label' => 'إجمالي الطلاب',
        ],
    ];
@endphp

<x-teacher.entity-list-page
    title="الحلقات الجماعية"
    subtitle="إدارة ومتابعة حلقات القرآن الجماعية والطلاب المسجلين"
    :items="$circles"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="green"
    list-title="قائمة الحلقات الجماعية"
    empty-icon="ri-group-line"
    empty-title="لا توجد حلقات جماعية"
    empty-description="لم يتم تعيين أي حلقات جماعية لك بعد"
    empty-filter-description="لا توجد حلقات بالحالة المحددة"
    :clear-filter-route="route('teacher.group-circles.index', ['subdomain' => $subdomain])"
    clear-filter-text="عرض جميع الحلقات"
>
    @foreach($circles as $circle)
        @php
            $statusConfig = match($circle->status) {
                'active' => ['class' => 'bg-green-100 text-green-800', 'text' => 'نشطة'],
                'full' => ['class' => 'bg-orange-100 text-orange-800', 'text' => 'مكتملة العدد'],
                'paused' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'متوقفة'],
                'closed' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'مغلقة'],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $circle->status ?? 'غير محدد']
            };

            $metadata = [
                ['icon' => 'ri-user-3-line', 'text' => ($circle->enrolled_students ?? 0) . '/' . ($circle->max_students ?? 15) . ' طالب'],
                ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')],
            ];

            // Add schedule days if available
            if ($circle->schedule && is_array($circle->schedule->days_of_week)) {
                $daysText = implode('، ', array_map(fn($day) => match($day) {
                    'sunday' => 'الأحد',
                    'monday' => 'الاثنين',
                    'tuesday' => 'الثلاثاء',
                    'wednesday' => 'الأربعاء',
                    'thursday' => 'الخميس',
                    'friday' => 'الجمعة',
                    'saturday' => 'السبت',
                    default => $day
                }, $circle->schedule->days_of_week));
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $daysText, 'class' => 'hidden sm:flex'];
            }

            $actions = [
                [
                    'href' => route('teacher.group-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-eye-line',
                    'label' => 'عرض التفاصيل',
                    'shortLabel' => 'عرض',
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ],
            ];

            if ($circle->status === 'active') {
                $actions[] = [
                    'href' => route('teacher.group-circles.progress', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => 'التقرير',
                    'shortLabel' => 'التقرير',
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ];

                $actions[] = [
                    'onclick' => "openGroupChat({$circle->id}, '{$circle->name}')",
                    'icon' => 'ri-chat-3-line',
                    'label' => 'محادثة جماعية',
                    'shortLabel' => 'محادثة',
                    'class' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
                ];
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$circle->name ?? 'حلقة قرآن جماعية'"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :description="$circle->description"
            icon="ri-group-line"
            icon-bg-class="bg-gradient-to-br from-green-500 to-teal-600"
        />
    @endforeach
</x-teacher.entity-list-page>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.openGroupChat = function(circleId, circleName) {
        fetch('/chat/groups/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                type: 'quran_circle',
                entity_id: circleId,
                name: 'حلقة ' + circleName,
                description: 'محادثة جماعية لحلقة ' + circleName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.group) {
                const groupId = data.group ? data.group.id : data.group_id;
                window.location.href = '/chat?group=' + groupId;
            } else {
                alert('حدث خطأ في إنشاء المحادثة الجماعية');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    };
});
</script>
@endsection
