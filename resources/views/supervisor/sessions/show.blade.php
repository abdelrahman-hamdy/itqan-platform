<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $t = 'supervisor.sessions.';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
    $isFinal = $status->isFinal();

    // Session type info
    $typeConfig = match($sessionType) {
        'academic' => ['label' => __($t.'type_academic'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        'interactive' => ['label' => __($t.'type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        default => ['label' => __($t.'type_quran'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
    };

    // Resolve teacher User object
    $teacherUser = match($sessionType) {
        'academic' => $session->academicTeacher?->user,
        'interactive' => $session->course?->assignedTeacher?->user,
        default => $session->quranTeacher,
    };
    $teacherName = $teacherUser?->name ?? '-';

    // Collect student User objects for participants list
    $studentUsers = collect();
    if ($sessionType === 'interactive' && $session->course?->enrolledStudents) {
        $studentUsers = $session->course->enrolledStudents->map(fn ($enrollment) => $enrollment->student?->user)->filter();
    } elseif ($sessionType === 'quran' && $session->circle) {
        $studentUsers = collect($session->circle->students ?? []);
    } elseif ($sessionType === 'quran' && $session->session_type === 'trial') {
        $studentUsers = $session->getStudentsForSession();
    } elseif ($sessionType === 'academic') {
        $studentUsers = collect([$session->student])->filter();
    } else {
        $studentUsers = collect([$session->student])->filter();
    }

    // Build student_id → report map for direct links
    $reportsByStudent = collect();
    if ($sessionType === 'quran') {
        $reportsByStudent = ($session->studentReports ?? collect())->keyBy('student_id');
    } elseif ($sessionType === 'academic') {
        $reportsByStudent = ($session->sessionReports ?? collect())->keyBy('student_id');
    } elseif ($sessionType === 'interactive') {
        $reportsByStudent = ($session->studentReports ?? collect())->keyBy('student_id');
    }

    $reportTypeSlug = match($sessionType) {
        'academic' => 'academic',
        'interactive' => 'interactive',
        default => 'quran',
    };

    // Check user roles
    $currentUser = auth()->user();
    $isSupervisor = $currentUser->hasRole('supervisor');
    $isAdmin = $currentUser->isAdmin(); // admin or super_admin

    // Entity & subscription URLs for navigation buttons
    $entityUrl = match($sessionType) {
        'academic' => $session->academic_subscription_id
            ? route('manage.academic-lessons.show', ['subdomain' => $subdomain, 'subscription' => $session->academic_subscription_id])
            : null,
        'interactive' => $session->course_id
            ? route('manage.interactive-courses.show', ['subdomain' => $subdomain, 'course' => $session->course_id])
            : null,
        default => $session->session_type === 'trial' && $session->trial_request_id
            ? route('manage.trial-sessions.show', ['subdomain' => $subdomain, 'trialRequest' => $session->trial_request_id])
            : ($session->session_type === 'individual' && $session->individual_circle_id
                ? route('manage.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $session->individual_circle_id])
                : ($session->circle_id ? route('manage.group-circles.show', ['subdomain' => $subdomain, 'circle' => $session->circle_id]) : null)),
    };
    $entityLabel = match($sessionType) {
        'academic' => __('sessions.actions.view_lesson'),
        'interactive' => __('sessions.actions.view_course'),
        default => $session->session_type === 'trial'
            ? __('sessions.actions.view_trial_request')
            : ($session->session_type === 'individual'
                ? __('sessions.actions.view_individual_circle')
                : __('sessions.actions.view_circle')),
    };
    $subscriptionUrl = match($sessionType) {
        'quran' => $session->quran_subscription_id
            ? route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => 'quran', 'subscription' => $session->quran_subscription_id])
            : null,
        'academic' => $session->academic_subscription_id
            ? route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => 'academic', 'subscription' => $session->academic_subscription_id])
            : null,
        default => null,
    };
@endphp

<div x-data="sessionDetail()" class="space-y-6">
    {{-- Breadcrumb --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __($t.'breadcrumb'), 'route' => route('manage.sessions.index', ['subdomain' => $subdomain])],
            ['label' => $session->title ?? $session->session_code ?? $typeConfig['label'], 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    {{-- Session Header --}}
    <x-sessions.session-header :session="$session" view-type="supervisor" />

    {{-- Cancellation Reason (shown when session is cancelled) --}}
    @if($session->status === \App\Enums\SessionStatus::CANCELLED && $session->cancellation_reason)
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="ri-error-warning-line text-red-500 text-lg mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-red-900">{{ __($t.'cancel_reason_label') }}</p>
                    <p class="text-sm text-red-800 mt-1">{{ $session->cancellation_reason }}</p>
                    @if($session->cancelledBy)
                        <p class="text-xs text-red-500 mt-1">{{ $session->cancelledBy->name }} &middot; {{ toAcademyTimezone($session->cancelled_at)->translatedFormat('d M Y - h:i A') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Action Bar --}}
    @if(($canObserve && $isLive) || $status->canCancel() || $filamentUrl || $entityUrl || $subscriptionUrl)
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-3">
            {{-- Observer / Participant Mode Toggle --}}
            @if($canObserve && $isLive)
                <div class="flex items-center bg-gray-100 rounded-lg p-1">
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors cursor-pointer {{ $mode === 'observer' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-eye-line me-1"></i>
                        {{ __('supervisor.observation.observer_mode') }}
                    </a>
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'participant']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors cursor-pointer {{ $mode === 'participant' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-video-chat-line me-1"></i>
                        {{ __('supervisor.observation.participant_mode') }}
                    </a>
                </div>
            @endif

            {{-- View linked entity (circle, lesson, course) --}}
            @if($entityUrl)
                <a href="{{ $entityUrl }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-green-50 hover:bg-green-100 text-green-700 transition-colors cursor-pointer">
                    <i class="ri-arrow-right-up-line"></i>
                    {{ $entityLabel }}
                </a>
            @endif

            {{-- View linked subscription --}}
            @if($subscriptionUrl)
                <a href="{{ $subscriptionUrl }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors cursor-pointer">
                    <i class="ri-bank-card-line"></i>
                    {{ __('sessions.actions.view_subscription') }}
                </a>
            @endif

            {{-- Cancel button --}}
            @if($status->canCancel())
                <button @click="showCancelModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors cursor-pointer">
                    <i class="ri-close-circle-line"></i>
                    {{ __($t.'cancel_session') }}
                </button>
            @endif

            {{-- Counting controls are now in the Counting Management section below --}}

            {{-- View in Panel --}}
            @if($filamentUrl)
                <a href="{{ $filamentUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer ms-auto">
                    <i class="ri-external-link-line"></i>
                    {{ __($t.'view_in_panel') }}
                </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Meeting Interface --}}
    @if($isLive)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($mode === 'observer' && $canObserve)
                <x-meetings.observer-interface :session="$session" :session-type="$sessionType" />
            @else
                <x-meetings.livekit-interface :session="$session" user-type="supervisor" />
            @endif
        </div>
    @endif

    {{-- Session Recordings (supervisors always see these) --}}
    @if($session instanceof \App\Contracts\RecordingCapable
        && $session->status === \App\Enums\SessionStatus::COMPLETED
        && $session->shouldShowRecordingToUser(auth()->user()))
        <x-recordings.session-recordings :session="$session" view-type="supervisor" />
    @endif

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Participants --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5" x-data="{ showAll: false }">
                <h3 class="text-base font-semibold text-gray-900 mb-4">
                    {{ __($t.'participants') }}
                    <span class="text-xs text-gray-400 font-normal">({{ 1 + $studentUsers->count() }})</span>
                </h3>
                <div class="space-y-3">
                    {{-- Teacher --}}
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$teacherUser" size="xs" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $teacherName }}</p>
                                <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_teacher') }}</p>
                            </div>
                        </div>
                        @if($isSupervisor && $teacherUser)
                            <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $teacherUser->id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors cursor-pointer">
                                <i class="ri-message-3-line"></i>
                                {{ __($t.'message') }}
                            </a>
                        @endif
                    </div>

                    {{-- Students --}}
                    @foreach($studentUsers as $index => $studentUser)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                             x-show="showAll || {{ $index }} < 10" x-transition>
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$studentUser" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $studentUser->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_student') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $studentReport = $reportsByStudent->get($studentUser->id);
                                @endphp
                                @if($studentReport)
                                    <a href="{{ route('manage.session-reports.show', ['subdomain' => $subdomain, 'type' => $reportTypeSlug, 'id' => $studentReport->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700 transition-colors cursor-pointer">
                                        <i class="ri-file-list-3-line"></i>
                                        {{ __($t.'view_report') }}
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-gray-200 text-gray-500 cursor-default">
                                        <i class="ri-file-list-3-line"></i>
                                        {{ __('reports.no_report_available') }}
                                    </span>
                                @endif
                                @if($isSupervisor)
                                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $studentUser->id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors cursor-pointer">
                                        <i class="ri-message-3-line"></i>
                                        {{ __($t.'message') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Show more/less toggle for large lists --}}
                    @if($studentUsers->count() > 10)
                        <button @click="showAll = !showAll" class="w-full text-center py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors cursor-pointer">
                            <span x-show="!showAll"><i class="ri-arrow-down-s-line"></i> {{ __($t.'show_more') }} ({{ $studentUsers->count() - 10 }})</span>
                            <span x-show="showAll" x-cloak><i class="ri-arrow-up-s-line"></i> {{ __($t.'show_less') }}</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Session Content --}}
            @if($session->lesson_content)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-file-text-line text-gray-400"></i>
                        {{ __($t.'session_content') }}
                    </h3>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! nl2br(e($session->lesson_content)) !!}
                    </div>
                </div>
            @endif

            {{-- Homework --}}
            @if($sessionType === 'quran')
                <x-sessions.homework-display :session="$session" view-type="supervisor" />
            @endif

            {{-- Learning Outcomes (Academic) --}}
            @if($sessionType === 'academic' && $session->learning_outcomes)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-lightbulb-line text-gray-400"></i>
                        {{ __($t.'learning_outcomes') }}
                    </h3>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! nl2br(e($session->learning_outcomes)) !!}
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Meeting Info (moved above notes) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="ri-video-line text-gray-400"></i>
                    {{ __($t.'meeting_info') }}
                </h3>
                <div class="space-y-3 text-sm">
                    @if($isLive)
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            {{ __($t.'meeting_active') }}
                        </div>
                        <div class="space-y-2">
                            <a href="#" onclick="document.querySelector('.bg-white.rounded-xl.border.border-gray-200.shadow-sm.overflow-hidden')?.scrollIntoView({behavior:'smooth'}); return false;"
                               class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors cursor-pointer">
                                <i class="ri-video-chat-line"></i>
                                {{ __($t.'join_meeting_btn') }}
                            </a>
                            @if($canObserve)
                                <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors cursor-pointer">
                                    <i class="ri-eye-line"></i>
                                    {{ __($t.'observe_silently') }}
                                </a>
                            @endif
                        </div>
                    @elseif($isFinal)
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-stop-circle-line"></i>
                            @if($status === \App\Enums\SessionStatus::CANCELLED)
                                {{ __($t.'session_cancelled_text') }}
                            @else
                                {{ __($t.'meeting_ended') }}
                            @endif
                        </div>
                    @else
                        {{-- Scheduled / not started --}}
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-time-line"></i>
                            {{ __($t.'meeting_not_started') }}
                        </div>
                        @php
                            $scheduledAt = $session->scheduled_at;
                        @endphp
                        @if($scheduledAt)
                            <div x-data="sessionCountdown({{ $scheduledAt->getTimestampMs() }})">
                                <p class="text-sm text-amber-600 font-medium">
                                    <i class="ri-timer-line me-1"></i>
                                    {{ __($t.'starts_in') }}: <span x-text="remaining"></span>
                                </p>
                            </div>
                        @endif
                        <button disabled class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                            <i class="ri-video-chat-line"></i>
                            {{ __($t.'session_not_started_yet') }}
                        </button>
                    @endif
                    @if($session->meeting_room_name)
                        <p class="text-xs text-gray-400 font-mono">{{ $session->meeting_room_name }}</p>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-sticky-note-line text-gray-400"></i>
                    {{ __($t.'notes_section') }}
                </h3>
                <div class="space-y-4">
                    {{-- Supervisor Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'supervisor_notes_label') }}</label>
                        @if($isSupervisor && !$isAdmin)
                            <textarea x-model="editForm.supervisor_notes" rows="3"
                                placeholder="{{ __($t.'notes_placeholder') }}"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                        @else
                            <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 min-h-[3rem]">{{ $session->supervisor_notes ?: '-' }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1"><i class="ri-eye-line me-0.5"></i> {{ __($t.'supervisor_notes_hint') }}</p>
                    </div>

                    {{-- Admin Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'admin_notes_label') }}</label>
                        @if($isAdmin)
                            <textarea x-model="editForm.admin_notes" rows="3"
                                placeholder="{{ __($t.'admin_notes_placeholder') }}"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                        @else
                            <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 min-h-[3rem]">{{ $session->admin_notes ?: '-' }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1"><i class="ri-eye-line me-0.5"></i> {{ __($t.'admin_notes_hint') }}</p>
                    </div>

                    {{-- Save button (only if user can edit at least one field) --}}
                    @if($isSupervisor || $isAdmin)
                        <div class="flex items-center justify-between">
                            <button @click="saveNotes()" :disabled="savingNotes"
                                class="px-4 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50 cursor-pointer">
                                <span x-show="!savingNotes">{{ __($t.'save_notes') }}</span>
                                <span x-show="savingNotes" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                            </button>
                            <span x-show="notesSaved" x-transition x-cloak class="text-xs text-green-600">
                                <i class="ri-check-line"></i> {{ __($t.'notes_saved') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Counting Management Section --}}
    @if($session->status === \App\Enums\SessionStatus::COMPLETED)
    @php
        // Use meeting_attendances as source of truth (quran_session_attendances is empty)
        $allMeetingAtt = $session->meetingAttendances ?? collect();
        $studentAttendances = $allMeetingAtt->where('user_type', 'student');
        $teacherMeetingAtt = $allMeetingAtt->whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])->first();
        $teacherAttStatusRaw = $session->teacher_attendance_status ?? $teacherMeetingAtt?->attendance_status;
        $teacherAttStatus = $teacherAttStatusRaw instanceof \BackedEnum ? $teacherAttStatusRaw->value : $teacherAttStatusRaw;
        $teacherCounts = $session->counts_for_teacher ?? true;
        $teacherMinutes = $teacherMeetingAtt?->total_duration_minutes ?? 0;

        $attStatusClasses = [
            'attended' => 'bg-green-100 text-green-800',
            'partially_attended' => 'bg-amber-100 text-amber-800',
            'late' => 'bg-yellow-100 text-yellow-800',
            'left' => 'bg-orange-100 text-orange-800',
            'absent' => 'bg-red-100 text-red-800',
        ];
        $countedClass = 'bg-emerald-100 text-emerald-700';
        $notCountedClass = 'bg-red-100 text-red-700';
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6" x-data="countingControls()">
        <h3 class="text-lg font-semibold text-gray-900 mb-5 flex items-center gap-2">
            <i class="ri-calculator-line text-indigo-500"></i>
            {{ __('settings.counting_management') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Teacher Column --}}
            <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                    <i class="ri-user-star-line text-indigo-500"></i>
                    {{ __('settings.teacher_attendance') }}
                </h4>

                {{-- Auto-calculated attendance --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ __('settings.teacher_attendance_status') }}</span>
                    @if($teacherAttStatus)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $attStatusClasses[$teacherAttStatus] ?? 'bg-gray-100 text-gray-600' }}">
                            <i class="{{ \App\Enums\AttendanceStatus::tryFrom($teacherAttStatus)?->icon() ?? 'ri-question-line' }} text-[10px]"></i>
                            {{ __('enums.attendance_status.' . $teacherAttStatus) }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">{{ __('settings.auto_calculated') }}</span>
                    @endif
                </div>

                {{-- Duration --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ __('supervisor.sessions.duration') }}</span>
                    <span class="text-xs font-medium text-gray-700">{{ $teacherMinutes }} {{ __('settings.minutes') }} / {{ $session->duration_minutes ?? '-' }} {{ __('settings.minutes') }}</span>
                </div>

                {{-- Counted status --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ __('settings.counts_for_teacher') }}</span>
                    <span x-show="countsForTeacher" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $countedClass }}">
                        <i class="ri-check-line text-[10px]"></i> {{ __('supervisor.sessions.counted') }}
                    </span>
                    <span x-show="!countsForTeacher" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $notCountedClass }}">
                        <i class="ri-close-line text-[10px]"></i> {{ __('supervisor.sessions.not_counted') }}
                    </span>
                </div>

                @if($session->counts_for_teacher_set_by)
                    <p class="text-[11px] text-gray-400"><i class="ri-edit-line"></i> {{ __('settings.override_by') }}: {{ $session->countsForTeacherSetBy?->name }}</p>
                @endif

                {{-- Action Button --}}
                <button
                    @click="confirmToggleTeacher()"
                    class="w-full mt-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg transition-colors"
                    :class="countsForTeacher
                        ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'
                        : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200'"
                >
                    <i :class="countsForTeacher ? 'ri-close-circle-line' : 'ri-check-circle-line'"></i>
                    <span x-text="countsForTeacher ? '{{ __('supervisor.sessions.uncount_for_teacher') }}' : '{{ __('supervisor.sessions.count_for_teacher') }}'"></span>
                </button>
            </div>

            {{-- Student Column --}}
            <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                    <i class="ri-user-line text-indigo-500"></i>
                    {{ __('settings.student_attendance') }}
                </h4>

                @forelse($studentAttendances as $sAtt)
                    @php
                        $studentUser = $sAtt->user;
                        $studentMinutes = $sAtt->total_duration_minutes ?? 0;
                        $studentAttStatusRaw = $sAtt->attendance_status;
                        $studentAttStatus = $studentAttStatusRaw instanceof \BackedEnum ? $studentAttStatusRaw->value : $studentAttStatusRaw;
                    @endphp
                    <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-800">{{ $studentUser?->name ?? __('supervisor.sessions.student_short') }}</span>
                            @if($studentAttStatus)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium {{ $attStatusClasses[$studentAttStatus] ?? 'bg-gray-100 text-gray-600' }}">
                                    <i class="{{ \App\Enums\AttendanceStatus::tryFrom($studentAttStatus)?->icon() ?? 'ri-question-line' }} text-[10px]"></i>
                                    {{ __('enums.attendance_status.' . $studentAttStatus) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $studentMinutes }} {{ __('settings.minutes') }} / {{ $session->duration_minutes ?? '-' }} {{ __('settings.minutes') }}</span>
                            <span x-show="studentCounts[{{ $sAtt->id }}]" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium {{ $countedClass }}">
                                <i class="ri-check-line text-[10px]"></i> {{ __('supervisor.sessions.counted') }}
                            </span>
                            <span x-show="!studentCounts[{{ $sAtt->id }}]" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium {{ $notCountedClass }}">
                                <i class="ri-close-line text-[10px]"></i> {{ __('supervisor.sessions.not_counted') }}
                            </span>
                        </div>

                        <button
                            @click="confirmToggleStudent({{ $sAtt->id }}, '{{ $studentUser?->name ?? '' }}')"
                            class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1.5 text-[11px] font-medium rounded-lg transition-colors"
                            :class="studentCounts[{{ $sAtt->id }}]
                                ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'
                                : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200'"
                        >
                            <i :class="studentCounts[{{ $sAtt->id }}] ? 'ri-close-circle-line' : 'ri-check-circle-line'"></i>
                            <span x-text="studentCounts[{{ $sAtt->id }}] ? '{{ __('supervisor.sessions.uncount_for_student') }}' : '{{ __('supervisor.sessions.count_for_student') }}'"></span>
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">{{ __('supervisor.sessions.no_attendance_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function countingControls() {
        return {
            countsForTeacher: @json($session->counts_for_teacher ?? true),
            studentCounts: @json($studentAttendances->pluck('counts_for_subscription', 'id')->map(fn($v) => $v ?? true)),
            submitting: false,

            confirmToggleTeacher() {
                const newVal = !this.countsForTeacher;
                const self = this;
                window.confirmAction({
                    title: newVal ? '{{ __("supervisor.sessions.count_for_teacher") }}' : '{{ __("supervisor.sessions.uncount_for_teacher") }}',
                    message: newVal
                        ? '{{ __("supervisor.sessions.count_teacher_confirm") }}'
                        : '{{ __("supervisor.sessions.uncount_teacher_confirm") }}',
                    confirmText: '{{ __("components.ui.confirmation_modal.confirm") }}',
                    isDangerous: !newVal,
                    theme: newVal ? 'green' : null,
                    onConfirm: () => self.doToggleTeacher(newVal),
                });
            },

            async doToggleTeacher(counts) {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const res = await fetch('{{ route("manage.sessions.toggle-counts-teacher", ["subdomain" => $subdomain, "sessionType" => $sessionType, "sessionId" => $session->id]) }}', {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ counts })
                    });
                    if (!res.ok) throw new Error('Failed');
                    this.countsForTeacher = counts;
                } catch (e) { alert('{{ __("supervisor.sessions.toggle_error") }}'); }
                this.submitting = false;
            },

            confirmToggleStudent(attId, name) {
                const newVal = !this.studentCounts[attId];
                const self = this;
                window.confirmAction({
                    title: newVal ? '{{ __("supervisor.sessions.count_for_student") }}' : '{{ __("supervisor.sessions.uncount_for_student") }}',
                    message: (newVal
                        ? '{{ __("supervisor.sessions.count_student_confirm") }}'
                        : '{{ __("supervisor.sessions.uncount_student_confirm") }}'
                    ).replace(':name', name),
                    confirmText: '{{ __("components.ui.confirmation_modal.confirm") }}',
                    isDangerous: !newVal,
                    theme: newVal ? 'green' : null,
                    onConfirm: () => self.doToggleStudent(attId, newVal),
                });
            },

            async doToggleStudent(attId, counts) {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const res = await fetch('{{ route("manage.sessions.toggle-counts-subscription", ["subdomain" => $subdomain, "sessionType" => $sessionType, "sessionId" => $session->id, "attendanceId" => "__ID__"]) }}'.replace('__ID__', attId), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ counts })
                    });
                    if (!res.ok) throw new Error('Failed');
                    this.studentCounts[attId] = counts;
                } catch (e) { alert('{{ __("supervisor.sessions.toggle_error") }}'); }
                this.submitting = false;
            }
        }
    }
    </script>
    @endpush
    @endif

    {{-- Cancel Confirmation Modal --}}
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>

            <div x-show="showCancelModal" x-transition class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="text-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="ri-error-warning-line text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __($t.'cancel_confirm_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __($t.'cancel_confirm_message') }}</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'cancel_reason_label') }}</label>
                    <textarea x-model="cancellationReason" rows="3" required
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 resize-none"></textarea>
                    <p class="text-xs text-gray-500 mt-1.5">
                        <i class="ri-information-line"></i>
                        {{ __($t.'cancel_reason_note') }}
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showCancelModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                        {{ __($t.'cancel_modal_close') }}
                    </button>
                    <button @click="submitCancel()" :disabled="submittingCancel || !cancellationReason.trim()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50 cursor-pointer">
                        <span x-show="!submittingCancel">{{ __($t.'cancel_session') }}</span>
                        <span x-show="submittingCancel" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $updateUrl = route('manage.sessions.update', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]);
    $cancelUrl = route('manage.sessions.cancel', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]);
    $forgiveUrl = null; // Forgiveness removed — replaced by counting controls
    $initialNotes = $session->supervisor_notes ?? '';
    $initialAdminNotes = $session->admin_notes ?? '';
    $countdownLabels = [
        'soon' => __('supervisor.sessions.session_starting_soon'),
        'h' => __('supervisor.sessions.hours_short'),
        'm' => __('supervisor.sessions.minutes_short'),
        's' => __('supervisor.sessions.seconds_short'),
    ];
@endphp

<script>
var _countdownLabels = @json($countdownLabels);

function sessionCountdown(targetMs) {
    return {
        remaining: '',
        init() {
            const labels = _countdownLabels;
            const update = () => {
                const diff = targetMs - Date.now();
                if (diff <= 0) { this.remaining = labels.soon; return; }
                const h = Math.floor(diff / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                this.remaining = (h > 0 ? h + ' ' + labels.h + ' ' : '') + m + ' ' + labels.m + ' ' + s + ' ' + labels.s;
            };
            update();
            setInterval(update, 1000);
        }
    };
}

function sessionDetail() {
    return {
        showCancelModal: false,
        showForgiveModal: false,
        savingNotes: false,
        notesSaved: false,
        submittingCancel: false,
        submittingForgive: false,
        cancellationReason: '',
        forgivenReason: '',

        editForm: {
            supervisor_notes: @json($initialNotes),
            admin_notes: @json($initialAdminNotes),
        },

        async saveNotes() {
            this.savingNotes = true;
            this.notesSaved = false;
            try {
                const response = await fetch(@json($updateUrl), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        supervisor_notes: this.editForm.supervisor_notes,
                        admin_notes: this.editForm.admin_notes,
                    }),
                });
                if (response.ok) {
                    this.notesSaved = true;
                    setTimeout(() => this.notesSaved = false, 3000);
                } else {
                    const data = await response.json();
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.savingNotes = false;
            }
        },

        async submitCancel() {
            if (!this.cancellationReason.trim()) return;
            this.submittingCancel = true;
            try {
                const response = await fetch(@json($cancelUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ cancellation_reason: this.cancellationReason }),
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.submittingCancel = false;
            }
        },

        async submitForgive() {
            if (!this.forgivenReason.trim()) return;
            this.submittingForgive = true;
            try {
                const forgiveUrl = @json($forgiveUrl);
                const response = await fetch(forgiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ forgiven_reason: this.forgivenReason }),
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.submittingForgive = false;
            }
        },

        init() {
            if (window.location.hash === '#cancel') {
                this.showCancelModal = true;
            }
            if (window.location.hash === '#forgive') {
                this.showForgiveModal = true;
            }
        }
    }
}
</script>

</x-layouts.supervisor>
