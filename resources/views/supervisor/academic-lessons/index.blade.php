<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('supervisor.academic_lessons.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.circles_list.group.all_circles'),
        'active' => __('teacher.circles_list.group.active_filter'),
        'pending' => __('teacher.circles_list.group.paused_filter'),
        'completed' => __('teacher.circles_list.group.closed_filter'),
    ];

    $stats = [
        ['icon' => 'ri-user-3-line', 'bgColor' => 'bg-violet-100', 'iconColor' => 'text-violet-600', 'value' => $subscriptions->total(), 'label' => __('supervisor.academic_lessons.total_lessons')],
        ['icon' => 'ri-play-circle-line', 'bgColor' => 'bg-green-100', 'iconColor' => 'text-green-600', 'value' => $subscriptions->where('status', 'active')->count(), 'label' => __('supervisor.academic_lessons.active_lessons')],
        ['icon' => 'ri-pause-circle-line', 'bgColor' => 'bg-orange-100', 'iconColor' => 'text-orange-600', 'value' => $subscriptions->where('status', 'pending')->count(), 'label' => __('supervisor.academic_lessons.pending_lessons')],
        ['icon' => 'ri-stop-circle-line', 'bgColor' => 'bg-gray-100', 'iconColor' => 'text-gray-600', 'value' => $subscriptions->where('status', 'paused')->count(), 'label' => __('supervisor.academic_lessons.paused_lessons')],
    ];
@endphp

<x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

<x-teacher.entity-list-page
    :title="__('supervisor.academic_lessons.page_title')"
    :subtitle="__('supervisor.academic_lessons.page_subtitle')"
    :items="$subscriptions"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="violet"
    :list-title="__('supervisor.academic_lessons.page_title')"
    empty-icon="ri-user-3-line"
    :empty-title="__('supervisor.common.no_data')"
    :empty-description="__('supervisor.academic_lessons.page_subtitle')"
    :clear-filter-route="route('manage.academic-lessons.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('supervisor.common.back_to_list')"
>
    @foreach($subscriptions as $subscription)
        @php
            $statusValue = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
            $statusConfig = match($statusValue) {
                'active' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.circles_list.group.status_active')],
                'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.circles_list.group.status_paused')],
                'paused' => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.circles_list.group.status_paused')],
                'completed' => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.circles_list.group.status_closed')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? '']
            };

            $metadata = [
                ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $subscription->lesson?->academicTeacher?->user?->name ?? ''])],
            ];
            if ($subscription->lesson?->subject) {
                $metadata[] = ['icon' => 'ri-book-line', 'text' => $subscription->lesson->subject->name];
            }
            if ($subscription->lesson?->gradeLevel) {
                $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $subscription->lesson->gradeLevel->getDisplayName()];
            }

            $actions = [
                [
                    'href' => route('manage.academic-lessons.show', ['subdomain' => $subdomain, 'subscription' => $subscription->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('supervisor.common.view_details'),
                    'shortLabel' => __('supervisor.common.view'),
                    'class' => 'bg-violet-600 hover:bg-violet-700 text-white',
                ],
            ];
        @endphp

        <x-teacher.entity-list-item
            :title="$subscription->student->name ?? ''"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$subscription->student"
        />
    @endforeach
</x-teacher.entity-list-page>

</x-layouts.supervisor>
