<x-layouts.teacher
    :title="__('teacher.trial_sessions.page_title') . ' - ' . ($trialRequest->student?->name ?? $trialRequest->student_name) . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.trial_sessions.page_description')">

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

    $studentName = $trialRequest->student?->name ?? $trialRequest->student_name ?? __('teacher.trial_sessions.unknown_student');
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.trial_sessions_list.breadcrumb'), 'route' => route('teacher.trial-sessions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName, 'truncate' => true],
        ]"
        view-type="teacher"
    />

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
            <i class="ri-check-line text-green-500 text-xl"></i>
            <span class="text-green-800">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
            <i class="ri-error-warning-line text-red-500 text-xl"></i>
            <span class="text-red-800">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Header Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-l from-amber-500 to-orange-500 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                                <i class="ri-book-read-line text-white text-2xl"></i>
                            </div>
                            <div class="text-white">
                                <h1 class="text-xl font-bold">{{ __('teacher.trial_sessions.detail_title') }}</h1>
                                <p class="text-white/80 text-sm">{{ $trialRequest->request_code }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $statusConfig['class'] }}">
                            <i class="{{ $statusConfig['icon'] }} {{ $statusConfig['iconClass'] }}"></i>
                            {{ $trialRequest->status->label() }}
                        </span>
                    </div>
                </div>

                @php
                    // Get academy timezone and convert created_at
                    $requestTimezone = getAcademyTimezone();
                    $requestDateInTz = toAcademyTimezone($trialRequest->created_at);

                    // Gregorian date - Arabic format
                    $requestGregorianDate = $requestDateInTz->locale('ar')->translatedFormat('l، d F Y');

                    // Hijri date - Using IntlDateFormatter for accurate Islamic calendar conversion
                    $requestHijriFormatter = new IntlDateFormatter(
                        'ar@calendar=islamic-umalqura',
                        IntlDateFormatter::LONG,
                        IntlDateFormatter::NONE,
                        $requestTimezone,
                        IntlDateFormatter::TRADITIONAL
                    );
                    $requestHijriDate = $requestHijriFormatter->format($requestDateInTz->timestamp);

                    // Time in friendly format
                    $requestTimeFormatted = $requestDateInTz->locale('ar')->translatedFormat('h:i A');
                @endphp
                <div class="p-6">
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                        <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                            <i class="ri-calendar-event-line text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">{{ __('teacher.trial_sessions.requested_at') }}</p>
                            <p class="font-bold text-gray-900">{{ $requestGregorianDate }}</p>
                            <p class="text-sm text-gray-500">{{ $requestHijriDate }} - {{ $requestTimeFormatted }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-user-line text-amber-500 ms-2"></i>
                    {{ __('teacher.trial_sessions.student_info') }}
                </h2>

                <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    @if($trialRequest->student)
                        <x-avatar
                            :user="$trialRequest->student"
                            size="lg"
                            userType="student"
                            :gender="$trialRequest->student->studentProfile?->gender ?? 'male'" />
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                            <i class="ri-user-line text-gray-400 text-2xl"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">{{ $studentName }}</h3>
                        @if($trialRequest->student_age)
                            <p class="text-sm text-gray-500">{{ $trialRequest->student_age }} {{ __('teacher.trial_sessions.years_old') }}</p>
                        @endif
                        @if($trialRequest->student?->studentProfile?->student_code)
                            <p class="text-sm text-gray-500">{{ $trialRequest->student->studentProfile->student_code }}</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <p class="text-xs text-amber-600 mb-1">{{ __('teacher.trial_sessions.current_level') }}</p>
                        <p class="font-medium text-gray-900">{{ $trialRequest->level_label }}</p>
                    </div>

                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <p class="text-xs text-amber-600 mb-1">{{ __('teacher.trial_sessions.preferred_time') }}</p>
                        <p class="font-medium text-gray-900">{{ $trialRequest->time_label }}</p>
                    </div>

                    @if($trialRequest->learning_goals && count($trialRequest->learning_goals) > 0)
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-100 md:col-span-2">
                        <p class="text-xs text-amber-600 mb-2">{{ __('teacher.trial_sessions.learning_goals') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($trialRequest->learning_goals as $goal)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-200 text-amber-800">
                                    {{ __('public.booking.quran.form.goals.' . $goal) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($trialRequest->notes)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 md:col-span-2">
                        <p class="text-xs text-gray-500 mb-1">{{ __('teacher.trial_sessions.student_notes') }}</p>
                        <p class="text-gray-700 whitespace-pre-line">{{ $trialRequest->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Session Details (if scheduled) -->
            @if($trialRequest->trialSession && in_array($statusValue, ['scheduled', 'completed']))
            @php
                // Get academy timezone and convert scheduled_at
                $timezone = getAcademyTimezone();
                $scheduledInTz = toAcademyTimezone($trialRequest->trialSession->scheduled_at);

                // Gregorian date - Arabic format (e.g., "15 يناير 2025")
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
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-calendar-event-line text-green-500 ms-2"></i>
                    {{ __('teacher.trial_sessions.session_details') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-calendar-line text-green-600"></i>
                            <p class="text-xs text-green-600 font-medium">{{ __('teacher.trial_sessions.scheduled_date') }}</p>
                        </div>
                        <p class="font-medium text-gray-900">{{ $gregorianDate }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $hijriDate }}</p>
                    </div>

                    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-time-line text-green-600"></i>
                            <p class="text-xs text-green-600 font-medium">{{ __('teacher.trial_sessions.scheduled_time') }}</p>
                        </div>
                        <p class="font-medium text-gray-900">{{ $timeFormatted }}</p>
                    </div>

                    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-timer-line text-green-600"></i>
                            <p class="text-xs text-green-600 font-medium">{{ __('teacher.trial_sessions.duration') }}</p>
                        </div>
                        <p class="font-medium text-gray-900">{{ $trialRequest->trialSession->duration_minutes ?? 30 }} {{ __('teacher.trial_sessions.minutes') }}</p>
                    </div>
                </div>

                @if($statusValue === 'scheduled')
                <div class="mt-4">
                    <a href="{{ route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                        <i class="ri-video-line ms-2"></i>
                        {{ __('teacher.trial_sessions.join_meeting') }}
                    </a>
                </div>
                @elseif($statusValue === 'completed')
                <div class="mt-4">
                    <a href="{{ route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                        <i class="ri-eye-line ms-2"></i>
                        {{ __('teacher.trial_sessions.view_session_details') }}
                    </a>
                </div>
                @endif
            </div>
            @endif

            <!-- Evaluation Form -->
            @if(in_array($statusValue, ['scheduled', 'completed']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-star-line text-blue-500 ms-2"></i>
                    {{ __('teacher.trial_sessions.evaluation') }}
                </h2>

                <form action="{{ route('teacher.trial-sessions.evaluate', ['subdomain' => $subdomain, 'trialRequest' => $trialRequest->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Rating -->
                    <div class="mb-6">
                        <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">{{ __('teacher.trial_sessions.rating') }} <span class="text-gray-400 font-normal">(1-10)</span></label>
                        <input type="number" id="rating" name="rating"
                               min="1" max="10" step="1"
                               value="{{ old('rating', $trialRequest->rating) }}"
                               placeholder="{{ __('teacher.trial_sessions.rating_placeholder') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-2 text-xs text-gray-500">{{ __('teacher.trial_sessions.rating_hint') }}</p>
                    </div>

                    <!-- Feedback for Student -->
                    <div class="mb-6">
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('teacher.trial_sessions.feedback') }}
                            <span class="text-xs text-gray-400 font-normal me-2">({{ __('teacher.trial_sessions.visible_to_student') }})</span>
                        </label>
                        <textarea id="feedback" name="feedback" rows="4"
                                  placeholder="{{ __('teacher.trial_sessions.feedback_placeholder') }}"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ old('feedback', $trialRequest->feedback) }}</textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" name="save" value="1"
                                class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            <i class="ri-save-line ms-2"></i>
                            {{ __('teacher.trial_sessions.save_evaluation') }}
                        </button>
                    </div>
                </form>
            </div>
            @endif

        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-flashlight-line text-amber-500 ms-2"></i>
                    {{ __('teacher.trial_sessions.quick_actions') }}
                </h3>

                <div class="space-y-3">
                    <a href="{{ route('teacher.trial-sessions.index', ['subdomain' => $subdomain]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <i class="ri-arrow-go-back-line text-gray-600"></i>
                        </div>
                        <span class="text-gray-700 font-medium">{{ __('teacher.trial_sessions.back_to_list') }}</span>
                    </a>
                </div>
            </div>

                    </div>
    </div>
</div>

</x-layouts.teacher>
