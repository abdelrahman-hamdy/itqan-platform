<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('manage.dashboard', ['subdomain' => $subdomain])],
        ['label' => __('supervisor.individual_circles.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.individual_circles_list.filter_all'),
        'active' => __('teacher.individual_circles_list.filter_active'),
        'paused' => __('teacher.individual_circles_list.filter_paused'),
        'completed' => __('teacher.individual_circles_list.filter_completed'),
    ];

    $activeCount = $circles->filter(fn($c) => $c->is_active && !$c->completed_at)->count();
    $pausedCount = $circles->filter(fn($c) => !$c->is_active && !$c->completed_at)->count();
    $completedCount = $circles->filter(fn($c) => $c->completed_at !== null)->count();

    $stats = [
        ['icon' => 'ri-user-star-line', 'bgColor' => 'bg-yellow-100', 'iconColor' => 'text-yellow-600', 'value' => $circles->total(), 'label' => __('supervisor.individual_circles.total_circles')],
        ['icon' => 'ri-play-circle-line', 'bgColor' => 'bg-green-100', 'iconColor' => 'text-green-600', 'value' => $activeCount, 'label' => __('supervisor.individual_circles.active_circles')],
        ['icon' => 'ri-pause-circle-line', 'bgColor' => 'bg-orange-100', 'iconColor' => 'text-orange-600', 'value' => $pausedCount, 'label' => __('supervisor.individual_circles.paused_circles')],
        ['icon' => 'ri-check-circle-line', 'bgColor' => 'bg-yellow-100', 'iconColor' => 'text-yellow-600', 'value' => $completedCount, 'label' => __('supervisor.individual_circles.completed_circles')],
    ];
@endphp

<x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

<x-teacher.entity-list-page
    :title="__('supervisor.individual_circles.page_title')"
    :subtitle="__('supervisor.individual_circles.page_subtitle')"
    :items="$circles"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="yellow"
    :list-title="__('supervisor.individual_circles.page_title')"
    empty-icon="ri-user-star-line"
    :empty-title="__('supervisor.common.no_data')"
    :empty-description="__('supervisor.individual_circles.page_subtitle')"
    :clear-filter-route="route('manage.individual-circles.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('supervisor.common.back_to_list')"
>
    @foreach($circles as $circle)
        @php
            $isActive = $circle->is_active && !$circle->completed_at;
            $isCompleted = $circle->completed_at !== null;
            $statusConfig = match(true) {
                $isActive => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.individual_circles_list.status_active')],
                $isCompleted => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.individual_circles_list.status_completed')],
                default => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.individual_circles_list.status_paused')],
            };

            $metadata = [
                ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $circle->quranTeacher?->name ?? ''])],
                ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')],
            ];
            if ($circle->sessions_count) {
                $metadata[] = ['icon' => 'ri-play-list-line', 'text' => __('supervisor.common.sessions_count', ['count' => $circle->sessions_count])];
            }

            $actions = [
                [
                    'href' => route('manage.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('supervisor.common.view_details'),
                    'shortLabel' => __('supervisor.common.view'),
                    'class' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
                ],
            ];
        @endphp

        <x-teacher.entity-list-item
            :title="$circle->student->name ?? __('teacher.individual_circles_list.unknown_student')"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$circle->student"
        />
    @endforeach
</x-teacher.entity-list-page>

</x-layouts.supervisor>
