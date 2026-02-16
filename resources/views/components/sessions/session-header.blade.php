@props([
    'session',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
use App\Enums\SessionStatus;
    $isTeacher = $viewType === 'teacher';
    
    // Detect session type - check if it's an AcademicSession or QuranSession
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    
    if ($isAcademicSession) {
        // Academic session types and descriptions
        $sessionTypeText = __('components.sessions.header.academic_session');
        $sessionDescription = $session->academicSubscription?->subject_name
            ? __('components.sessions.header.lesson_prefix') . ' ' . $session->academicSubscription->subject_name
            : __('components.sessions.header.educational_session');
    } else {
        // Quran session types
        $sessionTypeText = match($session->session_type) {
            'group' => __('components.sessions.header.group_session'),
            'individual' => __('components.sessions.header.individual_session'),
            'trial' => __('components.sessions.header.trial_session'),
            default => __('components.sessions.header.session')
        };
        $sessionDescription = __('components.sessions.header.quran_session');
    }
@endphp

<!-- Enhanced Session Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Session Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $session->title ?? $sessionTypeText }}
                </h1>
                <x-sessions.status-badge :status="$session->status" :session="$session" size="md" />
            </div>
            
            <!-- Session Description -->
            <p class="text-gray-600 mb-4 leading-relaxed">
                {{ $session->description ?? $sessionDescription }}
            </p>
            
            <!-- Session Quick Info -->
            <div class="flex flex-wrap items-center gap-4">
                @if($session->scheduled_at)
                @php
                    // Get academy timezone and convert scheduled_at
                    $timezone = getAcademyTimezone();
                    $scheduledInTz = toAcademyTimezone($session->scheduled_at);

                    // Gregorian date - Arabic format (e.g., "15 يناير 2025")
                    $gregorianDate = $scheduledInTz->locale('ar')->translatedFormat('d F Y');

                    // Hijri date - Using IntlDateFormatter for accurate Islamic calendar conversion
                    $hijriFormatter = new IntlDateFormatter(
                        'ar@calendar=islamic-umalqura',
                        IntlDateFormatter::LONG,
                        IntlDateFormatter::NONE,
                        $timezone,
                        IntlDateFormatter::TRADITIONAL
                    );
                    $hijriDate = $hijriFormatter->format($scheduledInTz->timestamp);
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                    <i class="ri-calendar-line ms-1"></i>
                    <span class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                        <span>{{ $gregorianDate }}</span>
                        <span class="hidden sm:inline text-blue-400">-</span>
                        <span>{{ $hijriDate }}</span>
                    </span>
                </span>
                @endif
                
                @if($session->scheduled_at)
                @php
                    $timezoneLabel = '';
                    $tzEnum = \App\Enums\Timezone::tryFrom($timezone);
                    if ($tzEnum) {
                        $timezoneLabel = $tzEnum->label();
                    }
                @endphp
                <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 text-sm font-medium rounded-full">
                    <i class="ri-time-line ms-1"></i>
                    {{ formatTimeArabic($session->scheduled_at) }}
                    @if($timezoneLabel)
                        <span class="text-green-500 text-xs me-1">({{ $timezoneLabel }})</span>
                    @endif
                </span>
                @endif
                
                @if($session->duration_minutes)
                <span class="inline-flex items-center px-3 py-1 bg-purple-50 text-purple-700 text-sm font-medium rounded-full">
                    <i class="ri-timer-line ms-1"></i>
                    {{ $session->duration_minutes }} {{ __('components.sessions.header.duration_minutes') }}
                </span>
                @endif
                
            </div>
        </div>
    </div>

    <!-- Current Session Info for Group Sessions -->
    @if($session->session_type === 'group' && $session->circle && $isTeacher)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $session->circle->students->count() ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{{ __('components.sessions.header.enrolled_students') }}</div>
                </div>

                @if($session->status === SessionStatus::COMPLETED)
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $session->attendances->where('attendance_status', \App\Enums\AttendanceStatus::ATTENDED->value)->count() ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600">{{ __('components.sessions.header.attendance') }}</div>
                </div>
                @endif

                @if($session->homework && $session->homework->count() > 0)
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $session->homework->count() }}</div>
                    <div class="text-sm text-gray-600">{{ __('components.sessions.header.homework_count') }}</div>
                </div>
                @endif

                @if($session->actual_duration_minutes)
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $session->actual_duration_minutes }}</div>
                    <div class="text-sm text-gray-600">{{ __('components.sessions.header.actual_duration') }}</div>
                </div>
                @endif
            </div>
        </div>
    @endif
</div> 