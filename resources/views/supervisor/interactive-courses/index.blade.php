<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('supervisor.interactive_courses.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.courses_list.all_courses'),
        'published' => __('teacher.courses_list.published_courses'),
        'active' => __('teacher.courses_list.active_courses'),
        'completed' => __('teacher.courses_list.completed_courses'),
    ];

    $stats = [
        ['icon' => 'ri-book-open-line', 'bgColor' => 'bg-blue-100', 'iconColor' => 'text-blue-600', 'value' => $courses->total(), 'label' => __('supervisor.interactive_courses.total_courses')],
        ['icon' => 'ri-play-circle-line', 'bgColor' => 'bg-green-100', 'iconColor' => 'text-green-600', 'value' => $courses->filter(fn($c) => $c->status === \App\Enums\InteractiveCourseStatus::ACTIVE)->count(), 'label' => __('supervisor.interactive_courses.active_courses')],
        ['icon' => 'ri-checkbox-circle-line', 'bgColor' => 'bg-blue-100', 'iconColor' => 'text-blue-600', 'value' => $courses->filter(fn($c) => $c->status === \App\Enums\InteractiveCourseStatus::COMPLETED)->count(), 'label' => __('supervisor.interactive_courses.completed_courses')],
        ['icon' => 'ri-user-line', 'bgColor' => 'bg-blue-100', 'iconColor' => 'text-blue-600', 'value' => $courses->sum(fn($c) => $c->enrollments->count()), 'label' => __('supervisor.interactive_courses.total_enrolled')],
    ];
@endphp

<x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

<x-teacher.entity-list-page
    :title="__('supervisor.interactive_courses.page_title')"
    :subtitle="__('supervisor.interactive_courses.page_subtitle')"
    :items="$courses"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="blue"
    :list-title="__('supervisor.interactive_courses.page_title')"
    empty-icon="ri-book-open-line"
    :empty-title="__('supervisor.common.no_data')"
    :empty-description="__('supervisor.interactive_courses.page_subtitle')"
    :clear-filter-route="route('manage.interactive-courses.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('supervisor.common.back_to_list')"
>
    @foreach($courses as $course)
        @php
            $statusValue = is_object($course->status) ? $course->status->value : $course->status;
            $statusConfig = match($statusValue) {
                'draft' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.courses_list.status_draft')],
                'published' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.courses_list.status_published')],
                'active' => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.courses_list.status_active')],
                'completed' => ['class' => 'bg-purple-100 text-purple-800', 'text' => __('teacher.courses_list.status_completed')],
                'cancelled' => ['class' => 'bg-red-100 text-red-800', 'text' => __('teacher.courses_list.status_cancelled')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? '']
            };

            $metadata = [
                ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $course->assignedTeacher?->user?->name ?? ''])],
            ];
            if ($course->subject) { $metadata[] = ['icon' => 'ri-book-line', 'text' => $course->subject->name]; }
            if ($course->gradeLevel) { $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $course->gradeLevel->getDisplayName()]; }
            $metadata[] = ['icon' => 'ri-user-line', 'text' => __('teacher.courses_list.students_enrolled_count', ['count' => $course->enrollments->count()])];

            $actions = [
                [
                    'href' => route('manage.interactive-courses.show', ['subdomain' => $subdomain, 'course' => $course->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('supervisor.common.view_details'),
                    'shortLabel' => __('supervisor.common.view'),
                    'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                ],
            ];
        @endphp

        <x-teacher.entity-list-item
            :title="$course->title"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :description="$course->description"
            icon="ri-book-open-line"
            icon-bg-class="bg-gradient-to-br from-blue-500 to-indigo-600"
        />
    @endforeach
</x-teacher.entity-list-page>

</x-layouts.supervisor>
