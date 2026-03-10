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
        <div class="flex items-center gap-1">
            <a href="{{ $showUrl }}" title="{{ __('supervisor.sessions.view_details') }}"
               class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                <i class="ri-eye-line text-base"></i>
            </a>
            @if($isLive)
                <a href="{{ $showUrl }}?mode=observer" title="{{ __('supervisor.sessions.observe_meeting') }}"
                   class="p-1.5 rounded-lg hover:bg-indigo-50 text-indigo-500 hover:text-indigo-700 transition-colors">
                    <i class="ri-eye-2-line text-base"></i>
                </a>
                <a href="{{ $showUrl }}?mode=participant" title="{{ __('supervisor.sessions.join_meeting') }}"
                   class="p-1.5 rounded-lg hover:bg-green-50 text-green-500 hover:text-green-700 transition-colors">
                    <i class="ri-video-chat-line text-base"></i>
                </a>
            @endif
            @if($status->canCancel())
                <a href="{{ $showUrl }}#cancel" title="{{ __('supervisor.sessions.cancel_session') }}"
                   class="p-1.5 rounded-lg hover:bg-red-50 text-red-400 hover:text-red-600 transition-colors">
                    <i class="ri-close-circle-line text-base"></i>
                </a>
            @endif
        </div>
    </td>
</tr>
