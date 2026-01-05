<x-layouts.student
    :title="__('student.trial_request.page_title') . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('student.trial_request.page_description')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $status = $trialRequest->status;
    $statusValue = $status instanceof \App\Enums\TrialRequestStatus ? $status->value : $status;

    $statusConfig = match($statusValue) {
        'pending' => ['class' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => 'ri-time-line', 'iconClass' => 'text-yellow-500'],
        'approved' => ['class' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'ri-check-line', 'iconClass' => 'text-blue-500'],
        'scheduled' => ['class' => 'bg-green-100 text-green-800 border-green-200', 'icon' => 'ri-calendar-check-line', 'iconClass' => 'text-green-500'],
        'completed' => ['class' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => 'ri-check-double-line', 'iconClass' => 'text-emerald-500'],
        'cancelled' => ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'ri-close-line', 'iconClass' => 'text-gray-500'],
        'rejected' => ['class' => 'bg-red-100 text-red-800 border-red-200', 'icon' => 'ri-close-circle-line', 'iconClass' => 'text-red-500'],
        'no_show' => ['class' => 'bg-orange-100 text-orange-800 border-orange-200', 'icon' => 'ri-user-unfollow-line', 'iconClass' => 'text-orange-500'],
        default => ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'ri-question-line', 'iconClass' => 'text-gray-500']
    };
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.subscriptions.title'), 'route' => route('student.subscriptions', ['subdomain' => $subdomain]), 'icon' => 'ri-wallet-line'],
            ['label' => __('student.trial_request.breadcrumb'), 'truncate' => true],
        ]"
        view-type="student"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Header Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-l from-amber-500 to-orange-500 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                                <i class="ri-gift-line text-white text-2xl"></i>
                            </div>
                            <div class="text-white">
                                <h1 class="text-xl font-bold">{{ __('student.trial_request.title') }}</h1>
                                <p class="text-white/80 text-sm">{{ $trialRequest->request_code }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $statusConfig['class'] }}">
                            <i class="{{ $statusConfig['icon'] }} {{ $statusConfig['iconClass'] }}"></i>
                            {{ $trialRequest->status->label() }}
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                        <i class="ri-calendar-line"></i>
                        <span>{{ __('student.trial_request.requested_at') }}: {{ formatDateArabic($trialRequest->created_at, 'd/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">
                    <i class="ri-git-commit-line text-amber-500 ms-2"></i>
                    {{ __('student.trial_request.status_timeline') }}
                </h2>

                <div class="relative">
                    @php
                        $steps = [
                            ['status' => 'pending', 'label' => __('student.trial_request.step_pending'), 'icon' => 'ri-time-line'],
                            ['status' => 'approved', 'label' => __('student.trial_request.step_approved'), 'icon' => 'ri-check-line'],
                            ['status' => 'scheduled', 'label' => __('student.trial_request.step_scheduled'), 'icon' => 'ri-calendar-check-line'],
                            ['status' => 'completed', 'label' => __('student.trial_request.step_completed'), 'icon' => 'ri-check-double-line'],
                        ];

                        $statusOrder = ['pending' => 1, 'approved' => 2, 'scheduled' => 3, 'completed' => 4];
                        $currentStatusOrder = $statusOrder[$statusValue] ?? 0;
                    @endphp

                    <div class="flex items-center justify-between">
                        @foreach($steps as $index => $step)
                            @php
                                $stepOrder = $statusOrder[$step['status']] ?? 0;
                                $isActive = $stepOrder <= $currentStatusOrder;
                                $isCurrent = $step['status'] === $statusValue;
                            @endphp
                            <div class="flex flex-col items-center flex-1 {{ $loop->last ? '' : 'relative' }}">
                                @if(!$loop->last)
                                    <div class="absolute top-6 start-1/2 end-[-50%] h-0.5 {{ $isActive && $stepOrder < $currentStatusOrder ? 'bg-amber-500' : 'bg-gray-200' }}"></div>
                                @endif
                                <div class="relative z-10 w-12 h-12 rounded-full flex items-center justify-center bg-white {{ $isActive ? '!bg-amber-500 text-white' : '!bg-gray-100 text-gray-400' }} {{ $isCurrent ? 'ring-4 ring-amber-200' : '' }}">
                                    <i class="{{ $step['icon'] }} text-xl"></i>
                                </div>
                                <span class="mt-2 text-xs font-medium {{ $isActive ? 'text-amber-600' : 'text-gray-400' }}">
                                    {{ $step['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Your Request Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-file-text-line text-amber-500 ms-2"></i>
                    {{ __('student.trial_request.your_request') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">{{ __('student.trial_request.current_level') }}</p>
                        <p class="font-medium text-gray-900">{{ $trialRequest->level_label }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">{{ __('student.trial_request.preferred_time') }}</p>
                        <p class="font-medium text-gray-900">{{ $trialRequest->time_label }}</p>
                    </div>

                    @if($trialRequest->learning_goals && count($trialRequest->learning_goals) > 0)
                    <div class="p-4 bg-gray-50 rounded-lg md:col-span-2">
                        <p class="text-xs text-gray-500 mb-2">{{ __('student.trial_request.learning_goals') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($trialRequest->learning_goals as $goal)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    {{ __('public.booking.quran.form.goals.' . $goal) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($trialRequest->notes)
                    <div class="p-4 bg-gray-50 rounded-lg md:col-span-2">
                        <p class="text-xs text-gray-500 mb-1">{{ __('student.trial_request.your_notes') }}</p>
                        <p class="text-gray-700">{{ $trialRequest->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Session Scheduling Status (Always Shown) -->
            @if($trialRequest->trialSession && $trialRequest->trialSession->scheduled_at)
            @php
                // Get academy timezone and convert scheduled_at
                $timezone = getAcademyTimezone();
                $scheduledInTz = toAcademyTimezone($trialRequest->trialSession->scheduled_at);

                // Gregorian date - Arabic format (e.g., "الأربعاء، 15 يناير 2025")
                $gregorianDate = $scheduledInTz->locale('ar')->translatedFormat('l، d F Y');

                // Hijri date - Using IntlDateFormatter for accurate Islamic calendar conversion
                $hijriFormatter = new IntlDateFormatter(
                    'ar@calendar=islamic-umalqura',
                    IntlDateFormatter::LONG,
                    IntlDateFormatter::NONE,
                    $timezone,
                    IntlDateFormatter::TRADITIONAL
                );
                $hijriDate = $hijriFormatter->format($scheduledInTz->timestamp);

                // Time in friendly format
                $timeFormatted = $scheduledInTz->locale('ar')->translatedFormat('h:i A');
            @endphp
            @endif
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-calendar-event-line {{ $trialRequest->trialSession ? 'text-green-500' : 'text-gray-400' }} ms-2"></i>
                    {{ __('student.trial_request.session_details') }}
                </h2>

                @if($trialRequest->trialSession && $trialRequest->trialSession->scheduled_at)
                    <!-- Session is scheduled -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="ri-calendar-line text-green-600"></i>
                                <p class="text-xs text-green-600 font-medium">{{ __('student.trial_request.scheduled_date') }}</p>
                            </div>
                            <p class="font-medium text-gray-900">{{ $gregorianDate }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $hijriDate }}</p>
                        </div>

                        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="ri-time-line text-green-600"></i>
                                <p class="text-xs text-green-600 font-medium">{{ __('student.trial_request.scheduled_time') }}</p>
                            </div>
                            <p class="font-medium text-gray-900">{{ $timeFormatted }}</p>
                        </div>

                        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="ri-timer-line text-green-600"></i>
                                <p class="text-xs text-green-600 font-medium">{{ __('student.trial_request.duration') }}</p>
                            </div>
                            <p class="font-medium text-gray-900">{{ $trialRequest->trialSession->duration_minutes ?? 30 }} {{ __('student.trial_request.minutes') }}</p>
                        </div>
                    </div>

                    <!-- Join Session Button -->
                    @if($statusValue === 'scheduled')
                    <div class="mt-4">
                        <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}"
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                            <i class="ri-video-line ms-2"></i>
                            {{ __('student.trial_request.join_session') }}
                        </a>
                    </div>
                    @elseif($statusValue === 'completed')
                    <div class="mt-4">
                        <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}"
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                            <i class="ri-eye-line ms-2"></i>
                            {{ __('student.trial_request.view_session_details') }}
                        </a>
                    </div>
                    @endif
                @else
                    <!-- Session not yet scheduled -->
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-calendar-todo-line text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-gray-900 font-medium mb-2">{{ __('student.trial_request.not_scheduled_title') }}</h3>
                        <p class="text-gray-500 text-sm">{{ __('student.trial_request.not_scheduled_description') }}</p>
                    </div>
                @endif
            </div>

            <!-- Teacher Evaluation (if completed) -->
            @if($statusValue === 'completed' && ($trialRequest->rating || $trialRequest->feedback))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        <i class="ri-star-line text-blue-500 ms-2"></i>
                        {{ __('student.trial_request.teacher_evaluation') }}
                    </h2>
                    @if($trialRequest->rating)
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ __('student.trial_request.your_rating') }}:</span>
                        <div class="flex items-center gap-1 px-3 py-1.5 bg-amber-100 rounded-full">
                            <span class="text-lg font-bold text-amber-600">{{ $trialRequest->rating }}</span>
                            <span class="text-lg font-bold text-amber-600">/ 10</span>
                        </div>
                    </div>
                    @endif
                </div>

                @if($trialRequest->feedback)
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs text-blue-600 font-medium mb-2">{{ __('student.trial_request.teacher_feedback') }}</p>
                    <p class="text-gray-700 whitespace-pre-line">{{ $trialRequest->feedback }}</p>
                </div>
                @endif
            </div>
            @endif

        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Teacher Info -->
            @if($trialRequest->teacher)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-user-star-line text-amber-500 ms-2"></i>
                    {{ __('student.trial_request.teacher_info') }}
                </h3>

                <div class="flex items-center gap-4 mb-4">
                    <x-avatar
                        :user="$trialRequest->teacher"
                        size="lg"
                        userType="quran_teacher"
                        :gender="$trialRequest->teacher->gender ?? $trialRequest->teacher->user?->gender ?? 'male'" />
                    <div>
                        <h4 class="font-bold text-gray-900">{{ $trialRequest->teacher->full_name }}</h4>
                        <p class="text-sm text-gray-500">{{ $trialRequest->teacher->teacher_code }}</p>
                    </div>
                </div>

                <a href="{{ route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $trialRequest->teacher->id]) }}"
                   class="block w-full text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                    {{ __('student.trial_request.view_teacher_profile') }}
                </a>
            </div>
            @endif

            <!-- Subscribe CTA (if completed) -->
            @if($statusValue === 'completed' && $trialRequest->teacher)
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="ri-vip-crown-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">{{ __('student.trial_request.subscribe_cta_title') }}</h3>
                    </div>
                </div>

                <p class="text-white/90 text-sm mb-4">
                    {{ __('student.trial_request.subscribe_cta_description') }}
                </p>

                <a href="{{ route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $trialRequest->teacher->id]) }}"
                   class="block w-full text-center px-6 py-3 bg-white text-blue-600 rounded-lg font-bold hover:bg-blue-50 transition-colors">
                    <i class="ri-arrow-left-line ms-2"></i>
                    {{ __('student.trial_request.subscribe_now') }}
                </a>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-flashlight-line text-amber-500 ms-2"></i>
                    {{ __('student.trial_request.quick_actions') }}
                </h3>

                <div class="space-y-3">
                    <a href="{{ route('student.subscriptions', ['subdomain' => $subdomain]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <i class="ri-arrow-go-back-line text-gray-600"></i>
                        </div>
                        <span class="text-gray-700 font-medium">{{ __('student.trial_request.back_to_subscriptions') }}</span>
                    </a>

                    @if($trialRequest->teacher?->user && $trialRequest->teacher->user->hasSupervisor() && $trialRequest->trialSession?->quran_individual_circle_id)
                        <x-chat.supervised-chat-button
                            :teacher="$trialRequest->teacher->user"
                            :student="auth()->user()"
                            entityType="quran_individual"
                            :entityId="$trialRequest->trialSession->quran_individual_circle_id"
                            variant="card"
                            class="w-full"
                        />
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

</x-layouts.student>
