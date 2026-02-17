@php
    $user = auth()->user();
    $academy = $user->academy ?? current_academy();
    $subdomain = $academy->subdomain ?? request()->route('subdomain') ?? 'itqan-academy';

    $isQuranSession = $session instanceof \App\Models\QuranSession;
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    $isInteractiveSession = $session instanceof \App\Models\InteractiveCourseSession;

    // Teacher info
    if ($isQuranSession) {
        $teacherName = trim(($session->quranTeacher?->first_name ?? '') . ' ' . ($session->quranTeacher?->last_name ?? ''));
    } elseif ($isAcademicSession) {
        $teacherUser = $session->academicTeacher?->user;
        $teacherName = $teacherUser ? trim(($teacherUser->first_name ?? '') . ' ' . ($teacherUser->last_name ?? '')) : __('supervisor.observation.unknown');
    } else {
        $teacherUser = $session->course?->assignedTeacher?->user;
        $teacherName = $teacherUser ? trim(($teacherUser->first_name ?? '') . ' ' . ($teacherUser->last_name ?? '')) : __('supervisor.observation.unknown');
    }

    // Student/group info
    if ($isQuranSession) {
        $studentName = $session->session_type === 'individual'
            ? trim(($session->student?->first_name ?? '') . ' ' . ($session->student?->last_name ?? ''))
            : ($session->circle?->name ?? __('supervisor.observation.group_session'));
    } elseif ($isAcademicSession) {
        $studentName = trim(($session->student?->first_name ?? '') . ' ' . ($session->student?->last_name ?? ''));
    } else {
        $studentName = $session->course?->title ?? __('supervisor.observation.unknown');
    }

    $tabLabel = match($sessionType) {
        'academic' => __('supervisor.observation.academic_sessions'),
        'interactive' => __('supervisor.observation.interactive_sessions'),
        default => __('supervisor.observation.quran_sessions'),
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $session->title ?? $session->session_code ?? __('supervisor.observation.observe_session') }} - {{ $academy->name ?? config('app.name') }}</title>
    {!! getFaviconLinkTag($academy) !!}
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 font-arabic">

{{-- Top Navigation Bar --}}
<nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Right: Logo & Title --}}
            <div class="flex items-center gap-3">
                @if($academy->logo_path)
                    <img src="{{ asset('storage/' . $academy->logo_path) }}" alt="{{ $academy->name }}" class="h-8 w-8 rounded-lg object-cover">
                @endif
                <div>
                    <h1 class="text-lg font-bold text-gray-900">{{ $session->title ?? $session->session_code ?? '' }}</h1>
                    <p class="text-xs text-gray-500">{{ $tabLabel }} &bull; {{ $teacherName }}</p>
                </div>
            </div>

            {{-- Left: Observer Badge & Back --}}
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="ri-eye-line ms-1"></i>
                    {{ __('supervisor.observation.observer_mode') }}
                </span>
                <a href="{{ route('sessions.monitoring', ['subdomain' => $subdomain, 'tab' => $sessionType]) }}"
                   class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="ri-arrow-right-line"></i>
                    {{ __('supervisor.observation.sessions_monitoring') }}
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="space-y-6">

        {{-- Session Header (reused component) --}}
        <x-sessions.session-header :session="$session" view-type="student" />

        {{-- LiveKit Meeting Interface (reused component with observer type) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <x-meetings.livekit-interface
                :session="$session"
                user-type="observer"
            />
        </div>

        {{-- Teacher & Student Info Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                <i class="ri-group-line text-primary ms-2"></i>
                {{ __('supervisor.observation.session_participants') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Teacher --}}
                <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                        <i class="ri-user-star-line text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ __('supervisor.observation.teacher') }}</p>
                        <p class="font-medium text-gray-900">{{ $teacherName ?: __('supervisor.observation.unknown') }}</p>
                    </div>
                </div>
                {{-- Student/Group --}}
                <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
                    <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                        <i class="ri-user-line text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ __('supervisor.observation.student_info') }}</p>
                        <p class="font-medium text-gray-900">{{ $studentName ?: __('supervisor.observation.unknown') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Session Content (Read-Only) --}}
        @if($session->lesson_content)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-file-text-line text-primary ms-2"></i>
                    {{ __('supervisor.observation.session_content') }}
                </h3>
                <div class="prose prose-sm md:prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3 md:p-4">
                    {!! nl2br(e($session->lesson_content)) !!}
                </div>
            </div>
        @endif

        {{-- Homework Display (Read-Only, reused component) --}}
        @if($isQuranSession && $session->sessionHomework && $session->sessionHomework->count() > 0)
            <x-sessions.homework-display
                :session="$session"
                :homework="$session->sessionHomework"
                view-type="student"
                session-type="quran"
            />
        @elseif(($isAcademicSession || $isInteractiveSession) && !empty($session->homework_description))
            <x-sessions.homework-display
                :session="$session"
                view-type="student"
                :session-type="$isAcademicSession ? 'academic' : 'interactive'"
            />
        @endif

        {{-- Learning Outcomes (Academic sessions) --}}
        @if($isAcademicSession && $session->learning_outcomes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-lightbulb-line text-primary ms-2"></i>
                    {{ __('supervisor.observation.learning_outcomes') }}
                </h3>
                <div class="text-gray-700 bg-gray-50 rounded-xl p-3 md:p-4">
                    {!! nl2br(e($session->learning_outcomes)) !!}
                </div>
            </div>
        @endif

    </div>
</main>

</body>
</html>
