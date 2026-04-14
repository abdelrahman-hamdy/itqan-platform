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
    ];

    $showUrl = route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);

    $statusBadge = match($recordingStatus) {
        'recording' => ['class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'icon' => 'ri-record-circle-line', 'pulse' => true],
        'queued' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'icon' => 'ri-time-line', 'pulse' => false],
        'manual' => ['class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400', 'icon' => 'ri-user-settings-line', 'pulse' => false],
        default => ['class' => 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-500', 'icon' => 'ri-subtract-line', 'pulse' => false],
    };
    $t = 'supervisor.recording.';
@endphp

<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
    {{-- Recording Status (first column) --}}
    <td class="px-4 py-3">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge['class'] }}">
            @if($statusBadge['pulse'] ?? false)
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            @else
                <i class="{{ $statusBadge['icon'] }} text-xs"></i>
            @endif
            {{ __($t.'status_' . $recordingStatus) }}
        </span>
    </td>

    {{-- Type --}}
    <td class="px-4 py-3">
        <div class="flex items-center gap-1.5">
            <span class="w-6 h-6 rounded flex items-center justify-center {{ $typeConfig['bg'] }}">
                <i class="{{ $typeConfig['icon'] }} text-xs {{ $typeConfig['text'] }}"></i>
            </span>
            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $typeConfig['label'] }}</span>
        </div>
    </td>

    {{-- Teacher --}}
    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">{{ $teacherName }}</td>

    {{-- Student --}}
    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">{{ $studentName }}</td>

    {{-- Scheduled At (matching sessions page format) --}}
    <td class="px-4 py-3 whitespace-nowrap">
        @if($session->scheduled_at)
            <span class="text-sm text-gray-700">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('d M') }}</span>
            <span class="text-xs text-gray-500 block">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('h:i A') }}</span>
        @else
            <span class="text-sm text-gray-400">-</span>
        @endif
    </td>

    {{-- Duration --}}
    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
        @if($session->duration_minutes)
            {{ $session->duration_minutes }} {{ __('supervisor.sessions.minutes_short') }}
        @else
            -
        @endif
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3">
        @if($isLive)
            <div class="flex items-center gap-1.5" onclick="event.stopPropagation();">
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
    </td>
</tr>
