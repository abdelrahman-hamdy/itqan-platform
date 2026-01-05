@extends('components.layouts.teacher')

@section('title', __('teacher.courses_list.page_title') . ' - ' . config('app.name', __('common.app_name')))

@section('content')
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.courses_list.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.courses_list.all_courses'),
        'published' => __('teacher.courses_list.published_courses'),
        'active' => __('teacher.courses_list.active_courses'),
        'completed' => __('teacher.courses_list.completed_courses'),
    ];

    $stats = [
        [
            'icon' => 'ri-book-open-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->total(),
            'label' => __('teacher.courses_list.total_courses'),
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $courses->filter(fn($c) => $c->status === \App\Enums\InteractiveCourseStatus::ACTIVE)->count(),
            'label' => __('teacher.courses_list.active_courses_stat'),
        ],
        [
            'icon' => 'ri-checkbox-circle-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->filter(fn($c) => $c->status === \App\Enums\InteractiveCourseStatus::COMPLETED)->count(),
            'label' => __('teacher.courses_list.completed_courses_stat'),
        ],
        [
            'icon' => 'ri-user-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->sum(fn($c) => $c->enrollments->count()),
            'label' => __('teacher.courses_list.total_students_stat'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.courses_list.page_title')"
    :subtitle="__('teacher.courses_list.page_subtitle')"
    :items="$courses"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="blue"
    :list-title="__('teacher.courses_list.list_title')"
    empty-icon="ri-book-open-line"
    :empty-title="__('teacher.courses_list.empty_title')"
    :empty-description="__('teacher.courses_list.empty_description')"
    :empty-filter-description="__('teacher.courses_list.empty_filter_description')"
    :clear-filter-route="route('teacher.interactive-courses.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.courses_list.view_all_courses')"
>
    @foreach($courses as $course)
        @php
            // Handle both enum and string status
            $statusValue = is_object($course->status) ? $course->status->value : $course->status;
            $statusConfig = match($statusValue) {
                'draft' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.courses_list.status_draft')],
                'published' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.courses_list.status_published')],
                'active' => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.courses_list.status_active')],
                'completed' => ['class' => 'bg-purple-100 text-purple-800', 'text' => __('teacher.courses_list.status_completed')],
                'cancelled' => ['class' => 'bg-red-100 text-red-800', 'text' => __('teacher.courses_list.status_cancelled')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? __('teacher.courses_list.status_unspecified')]
            };

            $metadata = [];
            if ($course->subject) {
                $metadata[] = ['icon' => 'ri-book-line', 'text' => $course->subject->name];
            }
            if ($course->gradeLevel) {
                $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $course->gradeLevel->getDisplayName()];
            }
            $metadata[] = ['icon' => 'ri-user-line', 'text' => __('teacher.courses_list.students_enrolled_count', ['count' => $course->enrollments->count()])];
            if ($course->start_date) {
                $metadata[] = ['icon' => 'ri-calendar-line', 'text' => \Carbon\Carbon::parse($course->start_date)->format('Y/m/d')];
            }

            $actions = [
                [
                    'href' => route('interactive-courses.show', ['subdomain' => request()->route('subdomain'), 'courseId' => $course->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('teacher.courses_list.view_details'),
                    'shortLabel' => __('teacher.courses_list.view_short'),
                    'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                ],
            ];

            // Add report action for active/completed courses
            if (in_array($statusValue, ['active', 'completed'])) {
                $actions[] = [
                    'href' => route('teacher.interactive-courses.report', ['subdomain' => request()->route('subdomain'), 'course' => $course->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => __('teacher.courses_list.report'),
                    'shortLabel' => __('teacher.courses_list.report'),
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ];
            }
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
@endsection
