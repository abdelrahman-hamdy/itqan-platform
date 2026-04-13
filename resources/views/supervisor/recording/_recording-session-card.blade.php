@php
    $recordingType = $session->_recording_type ?? 'unknown';
    $recordingStatus = $session->_recording_status ?? 'none';
    $t = 'supervisor.recording.';

    $typeBadgeColors = [
        'quran_individual' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'quran_group' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
        'academic_lesson' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'interactive_course' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'trial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    ];

    $statusBadgeColors = [
        'recording' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'queued' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'skipped' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'manual' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        'none' => 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
    ];

    $teacherName = '';
    if ($session instanceof \App\Models\QuranSession) {
        $teacherName = $session->quranTeacher?->name ?? '-';
    } elseif ($session instanceof \App\Models\AcademicSession) {
        $teacherName = $session->academicTeacher?->user?->name ?? '-';
    } elseif ($session instanceof \App\Models\InteractiveCourseSession) {
        $teacherName = $session->course?->assignedTeacher?->user?->name ?? '-';
    }

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $sessionType = match(true) {
        $session instanceof \App\Models\QuranSession => 'quran',
        $session instanceof \App\Models\AcademicSession => 'academic',
        $session instanceof \App\Models\InteractiveCourseSession => 'interactive',
        default => 'unknown',
    };
    $canObserve = $session->meeting_room_name && in_array($session->status?->value, ['ready', 'ongoing']);
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
    <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-2">
            @if(in_array($session->status?->value, ['ready', 'ongoing']))
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse flex-shrink-0"></span>
            @endif
            <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $session->session_code ?? $session->title ?? '#'.$session->id }}</span>
        </div>
        <!-- Recording status badge -->
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeColors[$recordingStatus] ?? 'bg-gray-100 text-gray-600' }}">
            @if($recordingStatus === 'recording')
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            @endif
            {{ __($t.'status_' . $recordingStatus) }}
        </span>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $typeBadgeColors[$recordingType] ?? 'bg-gray-100 text-gray-600' }}">
            {{ __($t.'type_' . $recordingType) }}
        </span>
        <span><i class="ri-user-line"></i> {{ $teacherName }}</span>
        <span dir="ltr"><i class="ri-time-line"></i> {{ $session->scheduled_at ? toAcademyTimezone($session->scheduled_at)->format('H:i') : '-' }}</span>
    </div>

    @if($canObserve)
        <div class="flex items-center gap-2">
            <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]) }}?mode=observer"
               class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition">
                <i class="ri-eye-line"></i>
                {{ __($t.'observe_session') }}
            </a>
            <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]) }}"
               class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 hover:bg-primary-200 transition">
                <i class="ri-video-chat-line"></i>
                {{ __($t.'join_session') }}
            </a>
        </div>
    @endif
</div>
