@php
    $type = $session->getAttribute('_type') ?? 'quran';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);

    // Teacher name
    $teacherName = match($type) {
        'academic' => $session->academicTeacher?->user?->name ?? '-',
        'interactive' => $session->course?->assignedTeacher?->user?->name ?? '-',
        default => $session->quranTeacher?->name ?? '-',
    };

    // Student / group name
    $studentName = match($type) {
        'academic' => $session->student?->name ?? '-',
        'interactive' => $session->course?->title ?? '-',
        default => $session->circle?->name ?? $session->student?->name ?? '-',
    };

    // Session sub-type label
    $subType = match($type) {
        'academic' => __('supervisor.sessions.type_individual'),
        'interactive' => __('supervisor.sessions.type_group'),
        default => $session->circle ? __('supervisor.sessions.type_group') : __('supervisor.sessions.type_individual'),
    };

    // Type config
    $typeConfig = match($type) {
        'academic' => ['label' => __('supervisor.sessions.type_academic'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        'interactive' => ['label' => __('supervisor.sessions.type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        default => ['label' => __('supervisor.sessions.type_quran'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
    };

    $showUrl = route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);
@endphp

<tr class="hover:bg-gray-50 cursor-pointer transition-colors {{ $isLive ? 'bg-green-50/50' : '' }}"
    onclick="window.location.href='{{ $showUrl }}'">
    {{-- Status --}}
    <td class="px-4 py-3">
        <div class="flex items-center gap-1.5">
            @if($isLive)
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            @endif
            <x-sessions.status-badge :status="$status" size="sm" />
        </div>
    </td>

    {{-- Session Title + Duration --}}
    <td class="px-4 py-3">
        <p class="text-sm font-medium text-gray-900">{{ $session->title ?: ($session->session_code ?? '-') }}</p>
        @if($session->duration_minutes)
            <p class="text-xs text-gray-400">{{ __('supervisor.sessions.duration_minutes', ['count' => $session->duration_minutes]) }}</p>
        @endif
    </td>

    {{-- Type --}}
    <td class="px-4 py-3">
        <div class="flex items-center gap-1.5">
            <span class="w-6 h-6 rounded flex items-center justify-center {{ $typeConfig['bg'] }}">
                <i class="{{ $typeConfig['icon'] }} text-xs {{ $typeConfig['text'] }}"></i>
            </span>
            <span class="text-xs text-gray-600">{{ $typeConfig['label'] }}</span>
            <span class="text-xs text-gray-400">· {{ $subType }}</span>
        </div>
    </td>

    {{-- Teacher --}}
    <td class="px-4 py-3">
        <span class="text-sm text-gray-700">{{ $teacherName }}</span>
    </td>

    {{-- Student / Group --}}
    <td class="px-4 py-3">
        <span class="text-sm text-gray-700">{{ $studentName }}</span>
    </td>

    {{-- Scheduled At --}}
    <td class="px-4 py-3">
        @if($session->scheduled_at)
            <span class="text-sm text-gray-700">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('d M') }}</span>
            <span class="text-xs text-gray-500 block">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('h:i A') }}</span>
        @else
            <span class="text-sm text-gray-400">-</span>
        @endif
    </td>

    {{-- Actions (inline buttons) --}}
    <td class="px-4 py-3" onclick="event.stopPropagation()">
        <div class="flex items-center gap-1.5">
            <a href="{{ $showUrl }}"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-600 hover:bg-gray-700 text-white transition-colors">
                <i class="ri-eye-line"></i>
                {{ __('supervisor.sessions.view_details') }}
            </a>
            @if($isLive)
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
            @endif
            @if($status->canCancel())
                <a href="{{ $showUrl }}#cancel"
                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors">
                    <i class="ri-close-circle-line"></i>
                    {{ __('supervisor.sessions.cancel_session') }}
                </a>
            @endif
        </div>
    </td>
</tr>
