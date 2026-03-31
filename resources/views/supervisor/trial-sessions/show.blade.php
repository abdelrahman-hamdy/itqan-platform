<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $status = $trialRequest->status;
    $statusValue = $status instanceof \App\Enums\TrialRequestStatus ? $status->value : $status;
    $statusConfig = match($statusValue) {
        'pending' => ['class' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'text' => __('teacher.trial_sessions_list.status_pending'), 'icon' => 'ri-time-line'],
        'approved' => ['class' => 'bg-blue-100 text-blue-800 border-blue-200', 'text' => __('teacher.trial_sessions_list.status_approved'), 'icon' => 'ri-check-line'],
        'scheduled' => ['class' => 'bg-green-100 text-green-800 border-green-200', 'text' => __('teacher.trial_sessions_list.status_scheduled'), 'icon' => 'ri-calendar-check-line'],
        'completed' => ['class' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'text' => __('teacher.trial_sessions_list.status_completed'), 'icon' => 'ri-check-double-line'],
        'cancelled' => ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'text' => __('teacher.trial_sessions_list.status_cancelled'), 'icon' => 'ri-close-line'],
        default => ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'text' => $statusValue, 'icon' => 'ri-question-line'],
    };
@endphp

<div>
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="quran" />
    @endif

    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.trial_sessions.breadcrumb'), 'route' => route('manage.trial-sessions.index', ['subdomain' => $subdomain])],
            ['label' => $trialRequest->student?->name ?? $trialRequest->student_name ?? '', 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Header Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-lg md:text-xl font-bold text-white">{{ $trialRequest->student?->name ?? $trialRequest->student_name ?? '' }}</h1>
                            <p class="text-amber-100 text-sm mt-1">{{ $trialRequest->request_code ?? '' }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium {{ $statusConfig['class'] }}">
                            <i class="{{ $statusConfig['icon'] }}"></i>
                            {{ $statusConfig['text'] }}
                        </span>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @if($trialRequest->current_level)
                            <div><p class="text-xs text-gray-500 mb-1">{{ __('supervisor.common.status') }}</p><p class="text-sm font-medium text-gray-900">{{ \App\Models\QuranTrialRequest::LEVELS[$trialRequest->current_level] ?? $trialRequest->current_level }}</p></div>
                        @endif
                        @if($trialRequest->student_age)
                            <div><p class="text-xs text-gray-500 mb-1">{{ __('teacher.trial_sessions_list.age_label') }}</p><p class="text-sm font-medium text-gray-900">{{ $trialRequest->student_age }}</p></div>
                        @endif
                        @if($trialRequest->preferred_time)
                            <div><p class="text-xs text-gray-500 mb-1">{{ __('supervisor.observation.scheduled_at') }}</p><p class="text-sm font-medium text-gray-900">{{ \App\Models\QuranTrialRequest::TIMES[$trialRequest->preferred_time] ?? $trialRequest->preferred_time }}</p></div>
                        @endif
                        @if($trialRequest->trialSession?->scheduled_at)
                            <div><p class="text-xs text-gray-500 mb-1">{{ __('supervisor.observation.scheduled_at') }}</p><p class="text-sm font-medium text-gray-900">{{ $trialRequest->trialSession->scheduled_at->translatedFormat('d M Y - h:i A') }}</p></div>
                        @endif
                    </div>
                    @if($trialRequest->notes)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.observation.supervisor_notes') }}</p>
                            <p class="text-sm text-gray-700">{{ $trialRequest->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($trialRequest->rating || $trialRequest->feedback)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-3">{{ __('teacher.trial_sessions_list.view_details') }}</h3>
                    @if($trialRequest->rating)
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm text-gray-600">{{ __('supervisor.common.status') }}:</span>
                            <span class="text-lg font-bold text-amber-600">{{ $trialRequest->rating }}/10</span>
                        </div>
                    @endif
                    @if($trialRequest->feedback)
                        <p class="text-sm text-gray-700">{{ $trialRequest->feedback }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.dashboard.quick_actions') }}</h3>
                <div class="space-y-2">
                    @if(!$trialRequest->trialSession && !in_array($statusValue, ['cancelled', 'completed']))
                        <a href="{{ route('manage.calendar.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher?->id]) }}"
                           class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                            <i class="ri-calendar-schedule-line"></i>
                            {{ __('supervisor.trial_sessions.schedule_session') }}
                        </a>
                    @endif
                    @if(!in_array($statusValue, ['cancelled', 'completed']))
                        <form action="{{ route('manage.trial-sessions.cancel', ['subdomain' => $subdomain, 'trialRequest' => $trialRequest->id]) }}"
                              method="POST"
                              onsubmit="return confirm(@json(__('supervisor.trial_sessions.confirm_cancel')))">
                            @csrf
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 transition-colors">
                                <i class="ri-close-circle-line"></i>
                                {{ __('supervisor.trial_sessions.cancel_request') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('manage.trial-sessions.index', ['subdomain' => $subdomain]) }}"
                       class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                        <i class="ri-arrow-right-line"></i>
                        {{ __('supervisor.common.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
