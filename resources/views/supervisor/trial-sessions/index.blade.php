<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('manage.dashboard', ['subdomain' => $subdomain])],
        ['label' => __('supervisor.trial_sessions.page_title')],
    ];

    $filterOptions = [
        '' => __('teacher.trial_sessions_list.filter_all'),
        'pending' => __('teacher.trial_sessions_list.filter_pending'),
        'approved' => __('teacher.trial_sessions_list.filter_approved'),
        'scheduled' => __('teacher.trial_sessions_list.filter_scheduled'),
        'completed' => __('teacher.trial_sessions_list.filter_completed'),
        'cancelled' => __('teacher.trial_sessions_list.filter_cancelled'),
    ];

    $stats = [
        ['icon' => 'ri-user-add-line', 'bgColor' => 'bg-amber-100', 'iconColor' => 'text-amber-600', 'value' => $trialRequests->total(), 'label' => __('supervisor.trial_sessions.total_requests')],
        ['icon' => 'ri-time-line', 'bgColor' => 'bg-yellow-100', 'iconColor' => 'text-yellow-600', 'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::PENDING)->count(), 'label' => __('supervisor.trial_sessions.pending_requests')],
        ['icon' => 'ri-calendar-check-line', 'bgColor' => 'bg-green-100', 'iconColor' => 'text-green-600', 'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::SCHEDULED)->count(), 'label' => __('supervisor.trial_sessions.scheduled_requests')],
        ['icon' => 'ri-check-double-line', 'bgColor' => 'bg-blue-100', 'iconColor' => 'text-blue-600', 'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::COMPLETED)->count(), 'label' => __('supervisor.trial_sessions.completed_requests')],
    ];
@endphp

<x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

<x-teacher.entity-list-page
    :title="__('supervisor.trial_sessions.page_title')"
    :subtitle="__('supervisor.trial_sessions.page_subtitle')"
    :items="$trialRequests"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="amber"
    :list-title="__('supervisor.trial_sessions.page_title')"
    empty-icon="ri-user-add-line"
    :empty-title="__('supervisor.common.no_data')"
    :empty-description="__('supervisor.trial_sessions.page_subtitle')"
    :clear-filter-route="route('manage.trial-sessions.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('supervisor.common.back_to_list')"
>
    @foreach($trialRequests as $request)
        @php
            $status = $request->status;
            $statusValue = $status instanceof \App\Enums\TrialRequestStatus ? $status->value : $status;

            $statusConfig = match($statusValue) {
                'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.trial_sessions_list.status_pending')],
                'approved' => ['class' => 'bg-blue-100 text-blue-800', 'text' => __('teacher.trial_sessions_list.status_approved')],
                'scheduled' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.trial_sessions_list.status_scheduled')],
                'completed' => ['class' => 'bg-emerald-100 text-emerald-800', 'text' => __('teacher.trial_sessions_list.status_completed')],
                'cancelled' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.trial_sessions_list.status_cancelled')],
                'rejected' => ['class' => 'bg-red-100 text-red-800', 'text' => __('teacher.trial_sessions_list.status_rejected')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue]
            };

            $metadata = [];
            $metadata[] = ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => ''])];
            if ($request->current_level) {
                $levelText = \App\Models\QuranTrialRequest::LEVELS[$request->current_level] ?? $request->current_level;
                $metadata[] = ['icon' => 'ri-bar-chart-line', 'text' => $levelText];
            }
            if ($request->trialSession && $request->trialSession->scheduled_at) {
                $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $request->trialSession->scheduled_at->format('Y/m/d H:i')];
            }
            $metadata[] = ['icon' => 'ri-calendar-2-line', 'text' => $request->created_at->format('Y/m/d')];

            $actions = [
                [
                    'href' => route('manage.trial-sessions.show', ['subdomain' => $subdomain, 'trialRequest' => $request->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('supervisor.common.view_details'),
                    'shortLabel' => __('supervisor.common.view'),
                    'class' => 'bg-amber-600 hover:bg-amber-700 text-white',
                ],
            ];

            $studentName = $request->student?->name ?? $request->student_name ?? '';
        @endphp

        <x-teacher.entity-list-item
            :title="$studentName"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$request->student"
            icon="ri-user-add-line"
            icon-bg-class="bg-gradient-to-br from-amber-500 to-orange-600"
        />
    @endforeach
</x-teacher.entity-list-page>

</x-layouts.supervisor>
