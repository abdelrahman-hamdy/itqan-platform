<x-layouts.teacher
    :title="__('teacher.individual_circles_list.page_title') . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.individual_circles_list.page_description')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.individual_circles_list.breadcrumb')],
    ];

    $filterOptions = [
        '' => __('teacher.individual_circles_list.filter_all'),
        'active' => __('teacher.individual_circles_list.filter_active'),
        'paused' => __('teacher.individual_circles_list.filter_paused'),
        'completed' => __('teacher.individual_circles_list.filter_completed'),
    ];

    $stats = [
        [
            'icon' => 'ri-user-star-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $circles->total(),
            'label' => __('teacher.individual_circles_list.total_circles'),
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $circles->where('status', 'active')->count(),
            'label' => __('teacher.individual_circles_list.active_circles'),
        ],
        [
            'icon' => 'ri-pause-circle-line',
            'bgColor' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'value' => $circles->where('status', 'paused')->count(),
            'label' => __('teacher.individual_circles_list.paused_circles'),
        ],
        [
            'icon' => 'ri-check-circle-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $circles->where('status', 'completed')->count(),
            'label' => __('teacher.individual_circles_list.completed_circles'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.individual_circles_list.page_title')"
    :subtitle="__('teacher.individual_circles_list.page_description')"
    :items="$circles"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="yellow"
    :list-title="__('teacher.individual_circles_list.list_title')"
    empty-icon="ri-user-star-line"
    :empty-title="__('teacher.individual_circles_list.empty_title')"
    :empty-description="__('teacher.individual_circles_list.empty_description')"
    :empty-filter-description="__('teacher.individual_circles_list.empty_filter_description')"
    :clear-filter-route="route('teacher.individual-circles.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.individual_circles_list.view_all_circles')"
>
    @foreach($circles as $circle)
        @php
            $statusConfig = match($circle->status) {
                'active' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.individual_circles_list.status_active')],
                'paused' => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.individual_circles_list.status_paused')],
                'completed' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.individual_circles_list.status_completed')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $circle->status]
            };

            $metadata = [];
            if ($circle->subscription && $circle->subscription->package) {
                $metadata[] = ['icon' => 'ri-bookmark-line', 'text' => $circle->subscription->package->name];
            }
            $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')];
            if ($circle->sessions_count) {
                $metadata[] = ['icon' => 'ri-play-list-line', 'text' => __('teacher.individual_circles_list.sessions_count', ['count' => $circle->sessions_count])];
            }

            $actions = [
                [
                    'href' => route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('teacher.individual_circles_list.view_details'),
                    'shortLabel' => __('teacher.individual_circles_list.view_short'),
                    'class' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
                ],
            ];

            if ($circle->status === \App\Enums\SubscriptionStatus::ACTIVE) {
                $actions[] = [
                    'href' => route('teacher.individual-circles.progress', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]),
                    'icon' => 'ri-bar-chart-line',
                    'label' => __('teacher.individual_circles_list.report'),
                    'shortLabel' => __('teacher.individual_circles_list.report'),
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ];

                // Chat action (Supervised)
                if ($circle->subscription && $circle->subscription->student && auth()->user()->hasSupervisor()) {
                    $studentUser = ($circle->student instanceof \App\Models\User) ? $circle->student : ($circle->student->user ?? null);
                    if ($studentUser) {
                        $actions[] = [
                            'href' => route('chat.start-supervised', [
                                'subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy',
                                'teacher' => auth()->id(),
                                'student' => $studentUser->id,
                                'entityType' => 'quran_individual',
                                'entityId' => $circle->id,
                            ]),
                            'icon' => 'ri-shield-user-line',
                            'label' => __('teacher.individual_circles_list.message_student'),
                            'shortLabel' => __('teacher.individual_circles_list.message_short'),
                            'class' => 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm',
                            'title' => __('chat.supervised_chat_tooltip'),
                        ];
                    }
                }
            }
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

</x-layouts.teacher>
