@php
    $type = $session->getAttribute('_type') ?? 'quran';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
    $isCompleted = $status === \App\Enums\SessionStatus::COMPLETED;
    // Trials don't affect subscriptions or teacher earnings — hide counting controls for them.
    $isTrial = $type === 'quran' && ($session->session_type ?? null) === 'trial';

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
        // Fall back to session-level flag when MeetingAttendance flag is NULL
        // (individual sessions track on session.subscription_counted).
        $sCounts = $studentMeeting?->counts_for_subscription ?? (bool) $session->subscription_counted;
        $duration = $session->duration_minutes ?? 0;
        $toggleTeacherUrl = route('manage.sessions.toggle-counts-teacher', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id]);
        $toggleStudentUrl = $studentMeeting ? route('manage.sessions.toggle-counts-subscription', ['subdomain' => $subdomain, 'sessionType' => $type, 'sessionId' => $session->id, 'attendanceId' => $studentMeeting->id]) : null;
    }
@endphp

<div onclick="window.location.href='{{ $showUrl }}'" class="block bg-white rounded-xl shadow-sm border border-gray-200 p-4 transition-all hover:shadow-md cursor-pointer {{ $isLive ? 'ring-2 ring-green-200' : '' }}"
    @if($isCompleted) x-data="{ tc: {{ $tCounts ? 'true' : 'false' }}, sc: {{ $sCounts ? 'true' : 'false' }}, busy: false }" @endif
>
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

    {{-- Attendance & Counting (completed sessions only) --}}
    @if($isCompleted)
        <div class="mt-3 pt-3 border-t border-gray-100 space-y-2">
            {{-- Attendance --}}
            <div class="space-y-1">
                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ __('supervisor.sessions.col_attendance') }}</p>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-gray-400 w-10 shrink-0">{{ __('supervisor.sessions.teacher_short') }}</span>
                        @if($tAtt)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium whitespace-nowrap {{ $attColors[$tAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $tAtt) }}</span>
                            <span class="text-[10px] text-gray-400">{{ $tMinutes }}/{{ $duration }}{{ __('settings.minutes') }}</span>
                        @else
                            <span class="text-[10px] text-gray-300">-</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-gray-400 w-10 shrink-0">{{ __('supervisor.sessions.student_short') }}</span>
                        @if($sAtt)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium whitespace-nowrap {{ $attColors[$sAtt] ?? 'bg-gray-100 text-gray-600' }}">{{ __('enums.attendance_status.' . $sAtt) }}</span>
                            <span class="text-[10px] text-gray-400">{{ $sMinutes }}/{{ $duration }}{{ __('settings.minutes') }}</span>
                        @else
                            <span class="text-[10px] text-gray-300">-</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Counting (not applicable to trial sessions) --}}
            @if(!$isTrial)
            <div class="space-y-1" onclick="event.stopPropagation()">
                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ __('supervisor.sessions.col_counting') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    {{-- Teacher counting button --}}
                    <button type="button"
                        @click.stop="if(busy) return; window.confirmAction({
                            title: tc ? '{{ __("supervisor.sessions.uncount_for_teacher") }}' : '{{ __("supervisor.sessions.count_for_teacher") }}',
                            message: tc ? '{{ __("supervisor.sessions.uncount_teacher_confirm") }}' : '{{ __("supervisor.sessions.count_teacher_confirm") }}',
                            isDangerous: tc,
                            theme: tc ? null : 'green',
                            icon: tc ? 'ri-subtract-line' : 'ri-add-line',
                            onConfirm: async () => {
                                busy = true;
                                try {
                                    const r = await fetch('{{ $toggleTeacherUrl }}', {
                                        method: 'PATCH',
                                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                        body: JSON.stringify({counts: !tc})
                                    });
                                    if (r.ok) tc = !tc;
                                } catch(e) {}
                                busy = false;
                            }
                        })"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-lg transition-colors cursor-pointer"
                        :class="tc ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200' : 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'">
                        <i class="ri-user-star-line text-[10px]"></i>
                        <i :class="tc ? 'ri-check-line' : 'ri-close-line'"></i>
                        <span x-text="tc ? '{{ __('supervisor.sessions.counted') }}' : '{{ __('supervisor.sessions.not_counted') }}'"></span>
                    </button>

                    {{-- Student counting button --}}
                    <button type="button"
                        @click.stop="@if($toggleStudentUrl) if(busy) return; window.confirmAction({
                            title: sc ? '{{ __("supervisor.sessions.uncount_for_student") }}' : '{{ __("supervisor.sessions.count_for_student") }}',
                            message: sc ? '{{ __("supervisor.sessions.uncount_student_confirm", ["name" => ""]) }}' : '{{ __("supervisor.sessions.count_student_confirm", ["name" => ""]) }}',
                            isDangerous: sc,
                            theme: sc ? null : 'green',
                            icon: sc ? 'ri-subtract-line' : 'ri-add-line',
                            onConfirm: async () => {
                                busy = true;
                                try {
                                    const r = await fetch('{{ $toggleStudentUrl }}', {
                                        method: 'PATCH',
                                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                        body: JSON.stringify({counts: !sc})
                                    });
                                    if (r.ok) sc = !sc;
                                } catch(e) {}
                                busy = false;
                            }
                        }) @else window.location.href='{{ $showUrl }}' @endif"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-lg transition-colors cursor-pointer"
                        :class="sc ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200' : 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'">
                        <i class="ri-user-line text-[10px]"></i>
                        <i :class="sc ? 'ri-check-line' : 'ri-close-line'"></i>
                        <span x-text="sc ? '{{ __('supervisor.sessions.counted') }}' : '{{ __('supervisor.sessions.not_counted') }}'"></span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    @endif

    {{-- Quick Actions --}}
    @if($isLive || $status->canCancel())
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
    </div>
    @endif
</div>
