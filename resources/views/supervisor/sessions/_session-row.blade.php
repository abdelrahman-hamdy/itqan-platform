@php
    $type = $session->getAttribute('_type') ?? 'quran';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
    $isCompleted = $status === \App\Enums\SessionStatus::COMPLETED;

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

    $attColors = $tAtt = $sAtt = $tMinutes = $sMinutes = $tCounts = $sCounts = $duration = $toggleTeacherUrl = $toggleStudentUrl = $studentMeeting = null;
    if ($isCompleted) {
        $attColors = ['attended' => 'bg-green-100 text-green-700', 'partially_attended' => 'bg-amber-100 text-amber-700', 'late' => 'bg-yellow-100 text-yellow-700', 'left' => 'bg-orange-100 text-orange-700', 'absent' => 'bg-red-100 text-red-700'];
        $tAttRaw = $session->teacher_attendance_status;
        $tAtt = $tAttRaw instanceof \BackedEnum ? $tAttRaw->value : $tAttRaw;
        $teacherMeeting = $session->meetingAttendances?->whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])->first();
        $tMinutes = $teacherMeeting?->total_duration_minutes ?? 0;
        $studentMeeting = $session->meetingAttendances?->where('user_type', 'student')->first();
        $sAttRaw = $studentMeeting?->attendance_status;
        $sAtt = $sAttRaw instanceof \BackedEnum ? $sAttRaw->value : $sAttRaw;
        $sMinutes = $studentMeeting?->total_duration_minutes ?? 0;
        $tCounts = $session->counts_for_teacher ?? true;
        $sCounts = $studentMeeting?->counts_for_subscription ?? true;
        $duration = $session->duration_minutes ?? 0;
        $toggleTeacherUrl = route('manage.sessions.toggle-counts-teacher', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);
        $toggleStudentUrl = $studentMeeting ? route('manage.sessions.toggle-counts-subscription', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id, 'attendanceId' => $studentMeeting->id]) : null;
    }
@endphp

<tr class="hover:bg-gray-50 cursor-pointer transition-colors {{ $isLive ? 'bg-green-50/50' : '' }}"
    onclick="window.location.href='{{ $showUrl }}'"
    @if($isCompleted) x-data="{ tc: {{ $tCounts ? 'true' : 'false' }}, sc: {{ $sCounts ? 'true' : 'false' }}, busy: false }" @endif
>
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
    <td class="px-4 py-3 whitespace-nowrap">
        @if($session->scheduled_at)
            <span class="text-sm text-gray-700">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('d M') }}</span>
            <span class="text-xs text-gray-500 block">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('h:i A') }}</span>
        @else
            <span class="text-sm text-gray-400">-</span>
        @endif
    </td>

    {{-- Attendance + Counting --}}
    <td class="px-4 py-3 whitespace-nowrap">
        @if($isCompleted)
            <div class="space-y-1.5">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-gray-400 w-10 shrink-0">{{ __('supervisor.sessions.teacher_short') }}</span>
                    @if($tAtt)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium whitespace-nowrap {{ $attColors[$tAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $tAtt) }}</span>
                        <span class="text-[10px] text-gray-400">{{ $tMinutes }}/{{ $duration }}{{ __('settings.minutes') }}</span>
                    @else
                        <span class="text-[10px] text-gray-300">-</span>
                    @endif
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium transition-colors"
                          :class="tc ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                        <i :class="tc ? 'ri-check-line' : 'ri-close-line'" class="text-[8px]"></i>
                        <span x-text="tc ? '{{ __('supervisor.sessions.counted') }}' : '{{ __('supervisor.sessions.not_counted') }}'"></span>
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-gray-400 w-10 shrink-0">{{ __('supervisor.sessions.student_short') }}</span>
                    @if($sAtt)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium whitespace-nowrap {{ $attColors[$sAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $sAtt) }}</span>
                        <span class="text-[10px] text-gray-400">{{ $sMinutes }}/{{ $duration }}{{ __('settings.minutes') }}</span>
                    @else
                        <span class="text-[10px] text-gray-300">-</span>
                    @endif
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium transition-colors"
                          :class="sc ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                        <i :class="sc ? 'ri-check-line' : 'ri-close-line'" class="text-[8px]"></i>
                        <span x-text="sc ? '{{ __('supervisor.sessions.counted') }}' : '{{ __('supervisor.sessions.not_counted') }}'"></span>
                    </span>
                </div>
            </div>
        @else
            <span class="text-xs text-gray-300">-</span>
        @endif
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3" onclick="event.stopPropagation()">
        <div class="flex flex-wrap items-center gap-1">
            @if($isLive)
                <a href="{{ $showUrl }}?mode=observer"
                   class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                    <i class="ri-eye-2-line"></i>
                    {{ __('supervisor.sessions.observe_meeting') }}
                </a>
                <a href="{{ $showUrl }}?mode=participant"
                   class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                    <i class="ri-video-chat-line"></i>
                    {{ __('supervisor.sessions.join_meeting') }}
                </a>
            @endif
            @if($status->canCancel())
                <a href="{{ $showUrl }}#cancel"
                   class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 transition-colors">
                    <i class="ri-close-circle-line"></i>
                    {{ __('supervisor.sessions.cancel_session') }}
                </a>
            @endif
            @if($isCompleted)
                {{-- Toggle teacher --}}
                <button @click.stop="if(busy) return; window.confirmAction({
                    title: tc ? '{{ __("supervisor.sessions.uncount_for_teacher") }}' : '{{ __("supervisor.sessions.count_for_teacher") }}',
                    message: tc ? '{{ __("supervisor.sessions.uncount_teacher_confirm") }}' : '{{ __("supervisor.sessions.count_teacher_confirm") }}',
                    isDangerous: tc,
                    theme: tc ? null : 'green',
                    onConfirm: async () => {
                        busy = true;
                        try {
                            const r = await fetch('{{ $toggleTeacherUrl }}', {method:'PATCH', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({counts:!tc})});
                            if(r.ok) tc = !tc;
                        } catch(e) {}
                        busy = false;
                    }
                })"
                class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg transition-colors"
                :class="tc ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200'">
                    <i class="ri-user-star-line"></i>
                    <span x-text="tc ? '{{ __("supervisor.sessions.uncount_for_teacher") }}' : '{{ __("supervisor.sessions.count_for_teacher") }}'"></span>
                </button>

                {{-- Toggle student --}}
                <button @click.stop="@if($toggleStudentUrl) if(busy) return; window.confirmAction({
                    title: sc ? '{{ __("supervisor.sessions.uncount_for_student") }}' : '{{ __("supervisor.sessions.count_for_student") }}',
                    message: sc ? '{{ __("supervisor.sessions.uncount_student_confirm", ["name" => ""]) }}' : '{{ __("supervisor.sessions.count_student_confirm", ["name" => ""]) }}',
                    isDangerous: sc,
                    theme: sc ? null : 'green',
                    onConfirm: async () => {
                        busy = true;
                        try {
                            const r = await fetch('{{ $toggleStudentUrl }}', {method:'PATCH', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({counts:!sc})});
                            if(r.ok) sc = !sc;
                        } catch(e) {}
                        busy = false;
                    }
                }) @else window.location.href='{{ $showUrl }}' @endif"
                class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg transition-colors"
                :class="sc ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200'">
                    <i class="ri-user-line"></i>
                    <span x-text="sc ? '{{ __("supervisor.sessions.uncount_for_student") }}' : '{{ __("supervisor.sessions.count_for_student") }}'"></span>
                </button>
            @endif
        </div>
    </td>
</tr>
