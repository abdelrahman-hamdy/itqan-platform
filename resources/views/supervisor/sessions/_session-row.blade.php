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
        default => $session->circle?->name
            ?? $session->student?->name
            ?? $session->trialRequest?->student?->name
            ?? $session->trialRequest?->student_name
            ?? '-',
    };

    // Type config with combined label
    $typeConfig = match($type) {
        'academic' => ['label' => __('supervisor.sessions.type_private_lesson'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        'interactive' => ['label' => __('supervisor.sessions.type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        default => $session->circle
            ? ['label' => __('supervisor.sessions.type_quran_group'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600']
            : ['label' => __('supervisor.sessions.type_quran_individual'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
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

    {{-- Attendance (Teacher / Student) --}}
    <td class="px-4 py-3">
        @if($session->status === \App\Enums\SessionStatus::COMPLETED)
            @php
                $tAtt = $session->teacher_attendance_status;
                $sAttRaw = $session->meetingAttendances?->where('user_type', 'student')->first()?->attendance_status;
                $sAtt = $sAttRaw instanceof \BackedEnum ? $sAttRaw->value : $sAttRaw;
                $tAtt = $tAtt instanceof \BackedEnum ? $tAtt->value : $tAtt;
                $attColors = ['attended' => 'bg-green-100 text-green-700', 'partially_attended' => 'bg-amber-100 text-amber-700', 'late' => 'bg-yellow-100 text-yellow-700', 'left' => 'bg-orange-100 text-orange-700', 'absent' => 'bg-red-100 text-red-700'];
            @endphp
            <div class="space-y-1">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-gray-400 w-10">{{ __('supervisor.sessions.teacher_short') }}</span>
                    @if($tAtt)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $attColors[$tAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $tAtt) }}</span>
                    @else
                        <span class="text-[10px] text-gray-300">-</span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-gray-400 w-10">{{ __('supervisor.sessions.student_short') }}</span>
                    @if($sAtt)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $attColors[$sAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $sAtt) }}</span>
                    @else
                        <span class="text-[10px] text-gray-300">-</span>
                    @endif
                </div>
            </div>
        @else
            <span class="text-xs text-gray-300">-</span>
        @endif
    </td>

    {{-- Counting (Teacher / Student) --}}
    <td class="px-4 py-3">
        @if($session->status === \App\Enums\SessionStatus::COMPLETED)
            @php
                $tCounts = $session->counts_for_teacher ?? true;
                $sCounts = $session->meetingAttendances?->where('user_type', 'student')->first()?->counts_for_subscription ?? true;
            @endphp
            <div class="space-y-1">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-gray-400 w-10">{{ __('supervisor.sessions.teacher_short') }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $tCounts ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        <i class="{{ $tCounts ? 'ri-check-line' : 'ri-close-line' }} text-[9px]"></i>
                        {{ $tCounts ? __('supervisor.sessions.counted') : __('supervisor.sessions.not_counted') }}
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-gray-400 w-10">{{ __('supervisor.sessions.student_short') }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $sCounts ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        <i class="{{ $sCounts ? 'ri-check-line' : 'ri-close-line' }} text-[9px]"></i>
                        {{ $sCounts ? __('supervisor.sessions.counted') : __('supervisor.sessions.not_counted') }}
                    </span>
                </div>
            </div>
        @else
            <span class="text-xs text-gray-300">-</span>
        @endif
    </td>

    {{-- Actions (inline buttons) --}}
    <td class="px-4 py-3" onclick="event.stopPropagation()">
        <div class="flex items-center gap-1.5">
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
