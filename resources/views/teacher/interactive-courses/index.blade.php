@extends('components.layouts.teacher')

@section('title', 'الدورات التفاعلية - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => 'الدورات التفاعلية'],
    ];

    $filterOptions = [
        '' => 'جميع الدورات',
        'draft' => 'مسودة',
        'published' => 'منشور',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ];

    $stats = [
        [
            'icon' => 'ri-book-open-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->total(),
            'label' => 'إجمالي الدورات',
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $courses->filter(fn($c) => ($c->status?->value ?? $c->status) === 'active')->count(),
            'label' => 'دورات نشطة',
        ],
        [
            'icon' => 'ri-checkbox-circle-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->filter(fn($c) => ($c->status?->value ?? $c->status) === 'completed')->count(),
            'label' => 'دورات مكتملة',
        ],
        [
            'icon' => 'ri-user-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $courses->sum(fn($c) => $c->enrollments->count()),
            'label' => 'إجمالي الطلاب',
        ],
    ];
@endphp

<x-teacher.entity-list-page
    title="الدورات التفاعلية"
    subtitle="إدارة ومتابعة الدورات التفاعلية المكلف بها"
    :items="$courses"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="blue"
    list-title="قائمة الدورات التفاعلية"
    empty-icon="ri-book-open-line"
    empty-title="لا توجد دورات تفاعلية"
    empty-description="لم يتم تكليفك بأي دورات تفاعلية بعد"
    empty-filter-description="لا توجد دورات بالحالة المحددة"
    :clear-filter-route="route('teacher.interactive-courses.index', ['subdomain' => $subdomain])"
    clear-filter-text="عرض جميع الدورات"
>
    @foreach($courses as $course)
        @php
            // Handle both enum and string status
            $statusValue = is_object($course->status) ? $course->status->value : $course->status;
            $statusConfig = match($statusValue) {
                'draft' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'مسودة'],
                'published' => ['class' => 'bg-green-100 text-green-800', 'text' => 'منشور'],
                'active' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'نشط'],
                'completed' => ['class' => 'bg-purple-100 text-purple-800', 'text' => 'مكتمل'],
                'cancelled' => ['class' => 'bg-red-100 text-red-800', 'text' => 'ملغي'],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? 'غير محدد']
            };

            $metadata = [];
            if ($course->subject) {
                $metadata[] = ['icon' => 'ri-book-line', 'text' => $course->subject->name];
            }
            if ($course->gradeLevel) {
                $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $course->gradeLevel->name];
            }
            $metadata[] = ['icon' => 'ri-user-line', 'text' => $course->enrollments->count() . ' طالب مسجل'];
            if ($course->start_date) {
                $metadata[] = ['icon' => 'ri-calendar-line', 'text' => \Carbon\Carbon::parse($course->start_date)->format('Y/m/d')];
            }

            $actions = [
                [
                    'href' => route('interactive-courses.show', ['subdomain' => request()->route('subdomain'), 'courseId' => $course->id]),
                    'icon' => 'ri-eye-line',
                    'label' => 'عرض التفاصيل',
                    'shortLabel' => 'عرض',
                    'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                ],
            ];

            // Add report action for active/completed courses
            if (in_array($statusValue, ['active', 'completed'])) {
                $actions[] = [
                    'href' => route('teacher.interactive-courses.report', ['subdomain' => request()->route('subdomain'), 'course' => $course->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => 'التقرير',
                    'shortLabel' => 'التقرير',
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
