@extends('components.layouts.teacher')

@section('title', 'الحلقات الفردية - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => 'الملف الشخصي', 'href' => route('teacher.profile', ['subdomain' => $subdomain])],
        ['label' => 'الحلقات الفردية'],
    ];

    $filterOptions = [
        '' => 'جميع الحلقات',
        'active' => 'نشطة',
        'paused' => 'متوقفة',
        'completed' => 'مكتملة',
    ];

    $stats = [
        [
            'icon' => 'ri-user-star-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
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
            'icon' => 'ri-pause-circle-line',
            'bgColor' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'value' => $circles->where('status', 'paused')->count(),
            'label' => 'حلقات متوقفة',
        ],
        [
            'icon' => 'ri-check-circle-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $circles->where('status', 'completed')->count(),
            'label' => 'حلقات مكتملة',
        ],
    ];
@endphp

<x-teacher.entity-list-page
    title="الحلقات الفردية"
    subtitle="إدارة ومتابعة جلسات القرآن الفردية مع الطلاب"
    :items="$circles"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="yellow"
    list-title="قائمة الحلقات الفردية"
    empty-icon="ri-user-star-line"
    empty-title="لا توجد حلقات فردية"
    empty-description="لم يتم تعيين أي حلقات فردية لك بعد"
    empty-filter-description="لا توجد حلقات بالحالة المحددة"
    :clear-filter-route="route('teacher.individual-circles.index', ['subdomain' => $subdomain])"
    clear-filter-text="عرض جميع الحلقات"
>
    @foreach($circles as $circle)
        @php
            $statusConfig = match($circle->status) {
                'active' => ['class' => 'bg-green-100 text-green-800', 'text' => 'نشطة'],
                'paused' => ['class' => 'bg-orange-100 text-orange-800', 'text' => 'متوقفة'],
                'completed' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'مكتملة'],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $circle->status]
            };

            $metadata = [];
            if ($circle->subscription && $circle->subscription->package) {
                $metadata[] = ['icon' => 'ri-bookmark-line', 'text' => $circle->subscription->package->name];
            }
            $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')];
            if ($circle->sessions_count) {
                $metadata[] = ['icon' => 'ri-play-list-line', 'text' => $circle->sessions_count . ' جلسة'];
            }

            $actions = [
                [
                    'href' => route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-eye-line',
                    'label' => 'عرض التفاصيل',
                    'shortLabel' => 'عرض',
                    'class' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
                ],
            ];

            if ($circle->status === 'active') {
                $actions[] = [
                    'href' => route('teacher.individual-circles.progress', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => 'التقرير',
                    'shortLabel' => 'التقرير',
                    'class' => 'bg-amber-600 hover:bg-amber-700 text-white',
                ];

                // Chat action
                if ($circle->subscription && $circle->subscription->student) {
                    $studentUser = ($circle->student instanceof \App\Models\User) ? $circle->student : ($circle->student->user ?? null);
                    $conv = $studentUser ? auth()->user()->getOrCreatePrivateConversation($studentUser) : null;
                    if ($conv) {
                        $actions[] = [
                            'href' => route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'conversation' => $conv->id]),
                            'icon' => 'ri-message-line',
                            'label' => 'راسل الطالب',
                            'shortLabel' => 'راسل',
                            'class' => 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm',
                            'title' => 'راسل الطالب',
                        ];
                    }
                }
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$circle->student->name ?? 'طالب غير محدد'"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$circle->student"
        />
    @endforeach
</x-teacher.entity-list-page>
@endsection
