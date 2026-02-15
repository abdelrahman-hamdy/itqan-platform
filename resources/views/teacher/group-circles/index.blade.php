<x-layouts.teacher
    :title="__('teacher.circles_list.group.page_title') . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.circles_list.group.page_subtitle')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.circles_list.group.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.circles_list.group.all_circles'),
        'active' => __('teacher.circles_list.group.active_filter'),
        'full' => __('teacher.circles_list.group.full_filter'),
        'paused' => __('teacher.circles_list.group.paused_filter'),
        'closed' => __('teacher.circles_list.group.closed_filter'),
    ];

    $stats = [
        [
            'icon' => 'ri-group-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->total(),
            'label' => __('teacher.circles_list.group.total_circles'),
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->where('status', 'active')->count(),
            'label' => __('teacher.circles_list.group.active_circles'),
        ],
        [
            'icon' => 'ri-user-add-line',
            'bgColor' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'value' => $circles->where('status', 'full')->count(),
            'label' => __('teacher.circles_list.group.full_capacity'),
        ],
        [
            'icon' => 'ri-team-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->sum('enrolled_students') ?? 0,
            'label' => __('teacher.circles_list.group.total_students'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.circles_list.group.page_title')"
    :subtitle="__('teacher.circles_list.group.page_subtitle')"
    :items="$circles"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="green"
    :list-title="__('teacher.circles_list.group.list_title')"
    empty-icon="ri-group-line"
    :empty-title="__('teacher.circles_list.group.empty_title')"
    :empty-description="__('teacher.circles_list.group.empty_description')"
    :empty-filter-description="__('teacher.circles_list.group.empty_filter_description')"
    :clear-filter-route="route('teacher.group-circles.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.circles_list.group.view_all_circles')"
>
    @foreach($circles as $circle)
        @php
            $statusConfig = match($circle->status) {
                'active' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.circles_list.group.status_active')],
                'full' => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.circles_list.group.status_full')],
                'paused' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.circles_list.group.status_paused')],
                'closed' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.circles_list.group.status_closed')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $circle->status ?? __('teacher.circles_list.group.status_unspecified')]
            };

            $metadata = [
                ['icon' => 'ri-user-3-line', 'text' => __('teacher.circles_list.group.students_per_max', ['enrolled' => $circle->enrolled_students ?? 0, 'max' => $circle->max_students ?? 15])],
                ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')],
            ];

            // Add schedule days if available
            if ($circle->schedule && is_array($circle->schedule->days_of_week)) {
                $daysText = implode('، ', array_map(fn($day) => match($day) {
                    'sunday' => __('teacher.circles_list.days.sunday'),
                    'monday' => __('teacher.circles_list.days.monday'),
                    'tuesday' => __('teacher.circles_list.days.tuesday'),
                    'wednesday' => __('teacher.circles_list.days.wednesday'),
                    'thursday' => __('teacher.circles_list.days.thursday'),
                    'friday' => __('teacher.circles_list.days.friday'),
                    'saturday' => __('teacher.circles_list.days.saturday'),
                    default => $day
                }, $circle->schedule->days_of_week));
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $daysText, 'class' => 'hidden sm:flex'];
            }

            $actions = [
                [
                    'href' => route('teacher.group-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('teacher.circles_list.group.view_details'),
                    'shortLabel' => __('teacher.circles_list.group.view_short'),
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ],
            ];

            if ($circle->status) {
                $actions[] = [
                    'href' => route('teacher.group-circles.progress', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => __('teacher.circles_list.group.report'),
                    'shortLabel' => __('teacher.circles_list.group.report'),
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ];

                $actions[] = [
                    'onclick' => "openGroupChat({$circle->id}, '{$circle->name}')",
                    'icon' => 'ri-chat-3-line',
                    'label' => __('teacher.circles_list.group.group_chat'),
                    'shortLabel' => __('teacher.circles_list.group.chat_short'),
                    'class' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
                ];
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$circle->name ?? __('teacher.circles.group.title')"
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

@php
    $teacherUser = auth()->user();
    $teacherHasSupervisor = $teacherUser && $teacherUser->hasSupervisor();

    // Build circle data for JavaScript
    $circleStudentData = [];
    foreach($circles as $circle) {
        $firstStudent = $circle->students?->first();
        $circleStudentData[$circle->id] = [
            'firstStudentId' => $firstStudent?->id,
            'hasStudents' => $circle->students?->count() > 0
        ];
    }
@endphp

<x-slot:scripts>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const teacherHasSupervisor = {{ $teacherHasSupervisor ? 'true' : 'false' }};
    const teacherId = {{ $teacherUser->id ?? 'null' }};
    const circleStudents = @json($circleStudentData);

    window.openGroupChat = function(circleId, circleName) {
        // Check if teacher has supervisor
        if (!teacherHasSupervisor) {
            window.toast?.error('{{ __("chat.teacher_no_supervisor") }}');
            return;
        }

        const circleData = circleStudents[circleId];

        // Check if circle has enrolled students
        if (!circleData || !circleData.hasStudents || !circleData.firstStudentId) {
            window.toast?.error('{{ __("chat.no_students_in_circle") }}');
            return;
        }

        // Navigate to supervised chat - the route will add all enrolled students
        const chatUrl = `/chat/start-supervised/${teacherId}/${circleData.firstStudentId}/quran_circle/${circleId}`;
        window.location.href = chatUrl;
    };
});
</script>
</x-slot:scripts>

</x-layouts.teacher>
