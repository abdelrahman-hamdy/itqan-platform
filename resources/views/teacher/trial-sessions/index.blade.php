<x-layouts.teacher
    :title="__('teacher.trial_sessions_list.page_title') . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.trial_sessions_list.page_description')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.trial_sessions_list.breadcrumb')],
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
        [
            'icon' => 'ri-user-add-line',
            'bgColor' => 'bg-amber-100',
            'iconColor' => 'text-amber-600',
            'value' => $trialRequests->total(),
            'label' => __('teacher.trial_sessions_list.total_requests'),
        ],
        [
            'icon' => 'ri-time-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::PENDING)->count(),
            'label' => __('teacher.trial_sessions_list.pending_requests'),
        ],
        [
            'icon' => 'ri-calendar-check-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::SCHEDULED)->count(),
            'label' => __('teacher.trial_sessions_list.scheduled_requests'),
        ],
        [
            'icon' => 'ri-check-double-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $trialRequests->where('status', \App\Enums\TrialRequestStatus::COMPLETED)->count(),
            'label' => __('teacher.trial_sessions_list.completed_requests'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.trial_sessions_list.page_title')"
    :subtitle="__('teacher.trial_sessions_list.page_description')"
    :items="$trialRequests"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="amber"
    :list-title="__('teacher.trial_sessions_list.list_title')"
    empty-icon="ri-user-add-line"
    :empty-title="__('teacher.trial_sessions_list.empty_title')"
    :empty-description="__('teacher.trial_sessions_list.empty_description')"
    :empty-filter-description="__('teacher.trial_sessions_list.empty_filter_description')"
    :clear-filter-route="route('teacher.trial-sessions.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.trial_sessions_list.view_all_requests')"
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
                'no_show' => ['class' => 'bg-orange-100 text-orange-800', 'text' => __('teacher.trial_sessions_list.status_no_show')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue]
            };

            $metadata = [];

            // Student level
            if ($request->current_level) {
                $levelText = \App\Models\QuranTrialRequest::LEVELS[$request->current_level] ?? $request->current_level;
                $metadata[] = ['icon' => 'ri-bar-chart-line', 'text' => $levelText];
            }

            // Preferred time
            if ($request->preferred_time) {
                $timeText = \App\Models\QuranTrialRequest::TIMES[$request->preferred_time] ?? $request->preferred_time;
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $timeText];
            }

            // Scheduled date if available
            if ($request->trialSession && $request->trialSession->scheduled_at) {
                $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $request->trialSession->scheduled_at->format('Y/m/d H:i')];
            }

            // Request date
            $metadata[] = ['icon' => 'ri-calendar-2-line', 'text' => __('teacher.trial_sessions_list.requested_at') . ': ' . $request->created_at->format('Y/m/d')];

            $actions = [];

            // View details action
            $actions[] = [
                'href' => route('teacher.trial-sessions.show', ['subdomain' => $subdomain, 'trialRequest' => $request->id]),
                'icon' => 'ri-eye-line',
                'label' => __('teacher.trial_sessions_list.view_details'),
                'shortLabel' => __('teacher.trial_sessions_list.view_short'),
                'class' => 'bg-blue-500 hover:bg-blue-600 text-white shadow-sm',
            ];

            // Chat with student if student exists and individual circle is created (Supervised)
            if ($request->student && $request->student->user && auth()->user()->hasSupervisor() && $request->trialSession?->quran_individual_circle_id) {
                $actions[] = [
                    'href' => route('chat.start-supervised', [
                        'subdomain' => $subdomain,
                        'teacher' => auth()->id(),
                        'student' => $request->student->user->id,
                        'entityType' => 'quran_individual',
                        'entityId' => $request->trialSession->quran_individual_circle_id,
                    ]),
                    'icon' => 'ri-shield-user-line',
                    'label' => __('teacher.trial_sessions_list.message_student'),
                    'shortLabel' => __('teacher.trial_sessions_list.message_short'),
                    'class' => 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm',
                    'title' => __('chat.supervised_chat_tooltip'),
                ];
            }

            // Join meeting if scheduled and meeting exists
            if ($statusValue === 'scheduled' && $request->trialSession && $request->trialSession->meeting) {
                $actions[] = [
                    'href' => route('individual-circles.show', ['subdomain' => $subdomain, 'circle' => $request->trialSession->quran_individual_circle_id ?? 0]),
                    'icon' => 'ri-video-line',
                    'label' => __('teacher.trial_sessions_list.join_session'),
                    'shortLabel' => __('teacher.trial_sessions_list.join_short'),
                    'class' => 'bg-green-600 hover:bg-green-700 text-white',
                ];
            }

            // Student name
            $studentName = $request->student?->name ?? $request->student_name ?? __('teacher.trial_sessions_list.unknown_student');

            // Student age if available
            $description = '';
            if ($request->student_age) {
                $description = __('teacher.trial_sessions_list.age_label') . ': ' . $request->student_age . ' ' . __('teacher.trial_sessions_list.years');
            }
            if ($request->notes) {
                $description .= ($description ? ' - ' : '') . $request->notes;
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$studentName"
            :description="$description"
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

</x-layouts.teacher>
