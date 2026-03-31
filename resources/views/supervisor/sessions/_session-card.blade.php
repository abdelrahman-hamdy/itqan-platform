@php
    $type = $session->getAttribute('_type') ?? 'quran';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);

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

    $typeConfig = match($type) {
        'academic' => ['label' => __('supervisor.sessions.type_private_lesson'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        'interactive' => ['label' => __('supervisor.sessions.type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        default => $session->circle
            ? ['label' => __('supervisor.sessions.type_quran_group'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600']
            : ['label' => __('supervisor.sessions.type_quran_individual'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
    };

    $showUrl = route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);
@endphp

<div onclick="window.location.href='{{ $showUrl }}'" class="block bg-white rounded-xl shadow-sm border border-gray-200 p-4 transition-all hover:shadow-md cursor-pointer {{ $isLive ? 'ring-2 ring-green-200' : '' }}">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-2 mb-3">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $typeConfig['bg'] }}">
                <i class="{{ $typeConfig['icon'] }} {{ $typeConfig['text'] }}"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $studentName }}</p>
                <p class="text-xs text-gray-500">{{ $typeConfig['label'] }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
            @if($isLive)
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 animate-pulse">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                    {{ __('supervisor.sessions.meeting_active') }}
                </span>
            @endif
            <x-sessions.status-badge :status="$status" size="sm" />
        </div>
    </div>

    {{-- Details --}}
    <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="flex items-center gap-1 text-gray-500">
            <i class="ri-user-line"></i>
            <span>{{ $teacherName }}</span>
        </div>
        <div class="flex items-center gap-1 text-gray-500">
            <i class="ri-calendar-line"></i>
            <span>{{ $session->scheduled_at ? toAcademyTimezone($session->scheduled_at)->translatedFormat('d M - h:i A') : '-' }}</span>
        </div>
        @if($session->duration_minutes)
        <div class="flex items-center gap-1 text-gray-500">
            <i class="ri-time-line"></i>
            <span>{{ __('supervisor.sessions.duration_minutes', ['count' => $session->duration_minutes]) }}</span>
        </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    @if($isLive || $status->canCancel() || $status->canForgive())
    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-1.5" onclick="event.stopPropagation();">
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
        @if($status->canForgive())
            <a href="{{ $showUrl }}#forgive"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                <i class="ri-heart-line"></i>
                {{ __('sessions.actions.forgive') }}
            </a>
        @endif
    </div>
    @endif
</div>
