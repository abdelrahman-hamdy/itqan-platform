@extends('components.layouts.teacher')

@section('title', __('teacher.sessions.academic.my_sessions_title') . ' - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    use App\Enums\SessionStatus;

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.sessions.academic.my_sessions_title')],
    ];

    $filterOptions = [
        '' => __('teacher.sessions.academic.all_sessions'),
        SessionStatus::SCHEDULED->value => __('teacher.sessions.academic.status.scheduled'),
        SessionStatus::ONGOING->value => __('teacher.sessions.academic.status.ongoing'),
        SessionStatus::COMPLETED->value => __('teacher.sessions.academic.status.completed'),
        SessionStatus::CANCELLED->value => __('teacher.sessions.academic.status.cancelled'),
    ];

    $stats = [
        [
            'icon' => 'ri-calendar-check-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $sessions->total(),
            'label' => __('teacher.sessions.academic.total_sessions'),
        ],
        [
            'icon' => 'ri-time-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $sessions->filter(fn($s) => $s->status === SessionStatus::SCHEDULED)->count(),
            'label' => __('teacher.sessions.academic.scheduled_sessions'),
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $sessions->filter(fn($s) => $s->status === SessionStatus::ONGOING)->count(),
            'label' => __('teacher.sessions.academic.ongoing_sessions'),
        ],
        [
            'icon' => 'ri-check-double-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $sessions->filter(fn($s) => $s->status === SessionStatus::COMPLETED)->count(),
            'label' => __('teacher.sessions.academic.completed_sessions'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.sessions.academic.my_sessions_title')"
    :subtitle="__('teacher.sessions.academic.subtitle')"
    :items="$sessions"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="violet"
    :list-title="__('teacher.sessions.academic.sessions_list')"
    empty-icon="ri-calendar-check-line"
    :empty-title="__('teacher.sessions.academic.no_sessions')"
    :empty-description="__('teacher.sessions.academic.no_sessions_description')"
    :empty-filter-description="__('teacher.sessions.academic.no_sessions_filter')"
    :clear-filter-route="route('teacher.academic-sessions.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.sessions.academic.all_sessions')"
>
    @foreach($sessions as $session)
        @php
            $statusConfig = match($session->status) {
                SessionStatus::SCHEDULED => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.sessions.academic.status.scheduled')],
                SessionStatus::ONGOING => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.sessions.academic.status.ongoing')],
                SessionStatus::COMPLETED => ['class' => 'bg-violet-100 text-violet-800', 'text' => __('teacher.sessions.academic.status.completed')],
                SessionStatus::CANCELLED => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.sessions.academic.status.cancelled')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.common.not_specified')]
            };

            $metadata = [];
            if ($session->student) {
                $metadata[] = ['icon' => 'ri-user-line', 'text' => $session->student->name];
            }
            if ($session->scheduled_at) {
                $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $session->scheduled_at->format('Y/m/d')];
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $session->scheduled_at->format('H:i')];
            }
            if ($session->duration_minutes) {
                $metadata[] = ['icon' => 'ri-timer-line', 'text' => $session->duration_minutes . ' ' . __('teacher.common.minute')];
            }

            $actions = [
                [
                    'href' => route('teacher.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
                    'icon' => 'ri-eye-line',
                    'text' => __('teacher.sessions.academic.view_details'),
                    'class' => 'text-violet-600 hover:text-violet-800',
                ],
            ];
        @endphp

        <x-teacher.entity-list-item
            :title="$session->title ?? __('teacher.sessions.academic.session_title')"
            :subtitle="$session->academicSubscription?->subject?->name ?? $session->session_type ?? ''"
            :status-class="$statusConfig['class']"
            :status-text="$statusConfig['text']"
            :metadata="$metadata"
            :actions="$actions"
            theme-color="violet"
        />
    @endforeach
</x-teacher.entity-list-page>

@endsection
