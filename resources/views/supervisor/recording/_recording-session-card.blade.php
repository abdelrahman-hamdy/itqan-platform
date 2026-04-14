@php
    $type = $session->getAttribute('_type') ?? 'quran';
    $recordingStatus = $session->getAttribute('_recording_status') ?? 'none';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
    $isTrial = method_exists($session, 'isTrial') ? $session->isTrial() : false;

    $teacherName = match($type) {
        'academic' => $session->academicTeacher?->user?->name ?? '-',
        'interactive' => $session->course?->assignedTeacher?->user?->name ?? '-',
        default => $session->quranTeacher?->name ?? '-',
    };

    $studentName = match($type) {
        'academic' => $session->student?->name ?? '-',
        'interactive' => $session->course?->title ?? '-',
        default => $session->circle?->name
            ?? $session->student?->name
            ?? $session->trialRequest?->student?->name
            ?? $session->trialRequest?->student_name
            ?? '-',
    };

    $typeConfig = match(true) {
        $type === 'academic' => ['label' => __('supervisor.sessions.type_private_lesson'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        $type === 'interactive' => ['label' => __('supervisor.sessions.type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        $isTrial => ['label' => __('supervisor.sessions.type_quran_trial'), 'icon' => 'ri-gift-line', 'bg' => 'bg-orange-50', 'text' => 'text-orange-600'],
        (bool) $session->circle => ['label' => __('supervisor.sessions.type_quran_group'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
        default => ['label' => __('supervisor.sessions.type_quran_individual'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
    };

    $showUrl = route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);

    $statusBadge = match($recordingStatus) {
        'recording' => ['class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'pulse' => true],
        'queued' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'pulse' => false],
        'manual' => ['class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400', 'pulse' => false],
        default => ['class' => 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-500', 'pulse' => false],
    };
    $t = 'supervisor.recording.';
@endphp

<div class="p-4">
    <div class="flex items-start justify-between mb-2">
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $typeConfig['bg'] }}">
                <i class="{{ $typeConfig['icon'] }} {{ $typeConfig['text'] }}"></i>
            </span>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $typeConfig['label'] }} <span class="text-[10px] text-gray-400 font-mono">#{{ $session->id }}</span></p>
                <div class="flex items-center gap-2 mt-0.5">
                    <x-sessions.status-badge :status="$status" size="sm" />
                    @if($session->duration_minutes)
                        <span class="text-xs text-gray-400">{{ $session->duration_minutes }} {{ __('supervisor.sessions.minutes_short') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge['class'] }}">
            @if($statusBadge['pulse'] ?? false)
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            @endif
            {{ __($t.'status_' . $recordingStatus) }}
        </span>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-3 text-xs text-gray-500 dark:text-gray-400">
        <span><i class="ri-user-line me-1"></i>{{ $teacherName }}</span>
        <span><i class="ri-group-line me-1"></i>{{ $studentName }}</span>
        <span dir="ltr"><i class="ri-time-line me-1"></i>{{ $session->scheduled_at ? toAcademyTimezone($session->scheduled_at)->format('M d, H:i') : '-' }}</span>
    </div>

    @if($isLive)
        <div class="flex flex-wrap gap-1.5" onclick="event.stopPropagation();">
            <a href="{{ $showUrl }}?mode=observer"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                <i class="ri-eye-2-line"></i>
                {{ __('supervisor.sessions.observe_meeting') }}
            </a>
            <a href="{{ $showUrl }}?mode=participant"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                <i class="ri-video-chat-line"></i>
                {{ __('supervisor.sessions.join_meeting') }}
            </a>
        </div>
    @endif
</div>
