@php
    $user = auth()->user();
    $academy = $user->academy ?? current_academy();
    $subdomain = $academy->subdomain ?? request()->route('subdomain') ?? 'itqan-academy';
    $isObservable = fn($session) => $session->meeting_room_name
        && in_array(
            $session->status instanceof \App\Enums\SessionStatus ? $session->status : \App\Enums\SessionStatus::tryFrom($session->status),
            [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]
        );
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('supervisor.observation.sessions_monitoring') }} - {{ $academy->name ?? config('app.name') }}</title>
    <link rel="icon" href="{{ $academy->logo_url ?? asset('favicon.ico') }}">
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
                    <h1 class="text-lg font-bold text-gray-900">{{ __('supervisor.observation.sessions_monitoring') }}</h1>
                    <p class="text-xs text-gray-500">{{ $academy->name ?? '' }}</p>
                </div>
            </div>

            {{-- Left: User Info & Back --}}
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    <i class="ri-shield-user-line ms-1"></i>
                    {{ $user->isSuperAdmin() ? __('supervisor.observation.role_super_admin') : __('supervisor.observation.role_supervisor') }}
                </span>
                <a href="{{ route('academy.home', ['subdomain' => $subdomain]) }}"
                   class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="ri-arrow-right-line"></i>
                    {{ $academy->name ?? __('supervisor.observation.all_sessions') }}
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Session Type Tabs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            @php
                $tabs = [
                    'quran' => ['label' => __('supervisor.observation.quran_sessions'), 'icon' => 'ri-book-read-line', 'color' => 'green', 'count' => $counts['quran']],
                    'academic' => ['label' => __('supervisor.observation.academic_sessions'), 'icon' => 'ri-graduation-cap-line', 'color' => 'orange', 'count' => $counts['academic']],
                    'interactive' => ['label' => __('supervisor.observation.interactive_sessions'), 'icon' => 'ri-video-chat-line', 'color' => 'blue', 'count' => $counts['interactive']],
                ];
            @endphp

            @foreach($tabs as $tabKey => $tabData)
                <a href="{{ route('sessions.monitoring', ['subdomain' => $subdomain, 'tab' => $tabKey, 'status' => $statusFilter, 'date' => $dateFilter]) }}"
                   @class([
                       'flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
                       "border-{$tabData['color']}-500 text-{$tabData['color']}-700 bg-{$tabData['color']}-50/50" => $activeTab === $tabKey,
                       'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTab !== $tabKey,
                   ])>
                    <i class="{{ $tabData['icon'] }} text-lg"></i>
                    {{ $tabData['label'] }}
                    <span @class([
                        'inline-flex items-center justify-center min-w-[24px] h-6 text-xs font-bold rounded-full px-1.5',
                        "bg-{$tabData['color']}-100 text-{$tabData['color']}-700" => $activeTab === $tabKey,
                        'bg-gray-100 text-gray-600' => $activeTab !== $tabKey,
                    ])>
                        {{ $tabData['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 flex flex-wrap items-center gap-3 border-b border-gray-100">
            {{-- Date Filter --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                @foreach(['all' => __('supervisor.observation.filter_all'), 'today' => __('supervisor.observation.filter_today'), 'week' => __('supervisor.observation.filter_week')] as $dateKey => $dateLabel)
                    <a href="{{ route('sessions.monitoring', ['subdomain' => $subdomain, 'tab' => $activeTab, 'status' => $statusFilter, 'date' => $dateKey]) }}"
                       @class([
                           'px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                           'bg-white shadow-sm text-gray-900' => $dateFilter === $dateKey,
                           'text-gray-600 hover:text-gray-900' => $dateFilter !== $dateKey,
                       ])>
                        {{ $dateLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Status Filter --}}
            <form method="GET" action="{{ route('sessions.monitoring', ['subdomain' => $subdomain]) }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <input type="hidden" name="date" value="{{ $dateFilter }}">
                <select name="status" onchange="this.form.submit()"
                        class="text-xs border-gray-300 rounded-lg focus:ring-primary focus:border-primary py-1.5 pe-8">
                    <option value="">{{ __('supervisor.observation.filter_status') }}: {{ __('supervisor.observation.filter_all') }}</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Sessions List --}}
    @if($sessions->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="ri-calendar-check-line text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('supervisor.observation.no_sessions') }}</h3>
            <p class="text-sm text-gray-500">{{ __('supervisor.observation.no_sessions_description') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($sessions as $session)
                @php
                    $canObserve = $isObservable($session);
                    $status = $session->status instanceof \App\Enums\SessionStatus
                        ? $session->status
                        : \App\Enums\SessionStatus::tryFrom($session->status);

                    // Get teacher name
                    if ($activeTab === 'quran') {
                        $teacherName = trim(($session->quranTeacher?->first_name ?? '') . ' ' . ($session->quranTeacher?->last_name ?? ''));
                        $studentName = $session->session_type === 'individual'
                            ? trim(($session->student?->first_name ?? '') . ' ' . ($session->student?->last_name ?? ''))
                            : ($session->circle?->name ?? __('supervisor.observation.group_session'));
                        $sessionTypeLabel = match($session->session_type) {
                            'individual' => __('supervisor.observation.session_type_individual'),
                            'group' => __('supervisor.observation.session_type_group'),
                            'trial' => __('supervisor.observation.session_type_trial'),
                            default => $session->session_type,
                        };
                    } elseif ($activeTab === 'academic') {
                        $teacherName = $session->academicTeacher?->user
                            ? trim(($session->academicTeacher->user->first_name ?? '') . ' ' . ($session->academicTeacher->user->last_name ?? ''))
                            : __('supervisor.observation.unknown');
                        $studentName = trim(($session->student?->first_name ?? '') . ' ' . ($session->student?->last_name ?? ''));
                        $sessionTypeLabel = $session->academicIndividualLesson?->academicSubject?->name ?? __('supervisor.observation.session_type_individual');
                    } else {
                        $teacherName = $session->course?->assignedTeacher?->user
                            ? trim(($session->course->assignedTeacher->user->first_name ?? '') . ' ' . ($session->course->assignedTeacher->user->last_name ?? ''))
                            : __('supervisor.observation.unknown');
                        $studentName = $session->course?->title ?? __('supervisor.observation.unknown');
                        $sessionTypeLabel = $session->course?->subject?->name ?? '';
                    }

                    $scheduledAt = $session->scheduled_at ? toAcademyTimezone($session->scheduled_at) : null;
                @endphp

                <a href="{{ route('sessions.monitoring.show', ['subdomain' => $subdomain, 'sessionType' => $activeTab, 'sessionId' => $session->id]) }}"
                   @class([
                    'block bg-white rounded-xl shadow-sm border overflow-hidden transition-all hover:shadow-md',
                    'border-green-300 ring-1 ring-green-200' => $canObserve,
                    'border-gray-200 hover:border-gray-300' => !$canObserve,
                ])>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4">
                        {{-- Session Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-gray-900 truncate">
                                    {{ $session->title ?? $session->session_code ?? '' }}
                                </h3>
                                @if($canObserve)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 animate-pulse">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        {{ __('supervisor.observation.meeting_active') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    <i class="ri-user-star-line text-gray-400"></i>
                                    {{ $teacherName ?: __('supervisor.observation.unknown') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <i class="ri-user-line text-gray-400"></i>
                                    {{ $studentName ?: __('supervisor.observation.unknown') }}
                                </span>
                                @if($sessionTypeLabel)
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-bookmark-line text-gray-400"></i>
                                        {{ $sessionTypeLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Schedule & Status --}}
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="text-end">
                                @if($scheduledAt)
                                    <p class="text-sm font-medium text-gray-900">{{ $scheduledAt->format('H:i') }}</p>
                                    <p class="text-xs text-gray-500">{{ $scheduledAt->format('Y-m-d') }}</p>
                                @endif
                                @if($session->duration_minutes)
                                    <p class="text-xs text-gray-400">{{ $session->duration_minutes }} {{ __('supervisor.observation.duration_minutes') }}</p>
                                @endif
                            </div>

                            <span @class([
                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                'bg-green-100 text-green-700' => $status === \App\Enums\SessionStatus::ONGOING,
                                'bg-yellow-100 text-yellow-700' => $status === \App\Enums\SessionStatus::READY,
                                'bg-blue-100 text-blue-700' => $status === \App\Enums\SessionStatus::SCHEDULED,
                                'bg-gray-100 text-gray-600' => $status === \App\Enums\SessionStatus::COMPLETED,
                                'bg-red-100 text-red-700' => $status === \App\Enums\SessionStatus::CANCELLED || $status === \App\Enums\SessionStatus::ABSENT,
                            ])>
                                {{ $status?->label() ?? '' }}
                            </span>

                            @if($canObserve)
                                <span data-session-id="{{ $session->id }}"
                                      data-session-type="{{ $activeTab }}"
                                      class="observe-btn inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                    <i class="ri-eye-line"></i>
                                    {{ __('supervisor.observation.join_observation') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    @endif

</main>

<script>
// Observer buttons: open the session detail page with meeting in a new tab
document.querySelectorAll('.observe-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const sessionId = this.dataset.sessionId;
        const sessionType = this.dataset.sessionType;

        // Open the session detail (observer) page in a new tab
        const detailUrl = '/sessions-monitoring/' + sessionType + '/' + sessionId;
        window.open(detailUrl, '_blank');
    });
});
</script>

</body>
</html>
