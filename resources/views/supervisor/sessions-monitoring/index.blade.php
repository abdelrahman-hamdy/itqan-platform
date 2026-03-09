<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('supervisor.dashboard', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.observation.sessions_monitoring')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.observation.sessions_monitoring') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.observation.sessions_monitoring_description') }}</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-4 md:gap-6 overflow-x-auto" aria-label="Tabs">
            <a href="{{ route('supervisor.sessions-monitoring.index', ['subdomain' => $subdomain, 'tab' => 'quran', 'date' => $dateFilter]) }}"
               class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors flex items-center gap-1.5
                   {{ $tab === 'quran' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <i class="ri-book-read-line"></i>
                {{ __('supervisor.observation.quran_sessions') }}
                <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">{{ $quranSessions->count() }}</span>
            </a>
            <a href="{{ route('supervisor.sessions-monitoring.index', ['subdomain' => $subdomain, 'tab' => 'academic', 'date' => $dateFilter]) }}"
               class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors flex items-center gap-1.5
                   {{ $tab === 'academic' ? 'border-violet-500 text-violet-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <i class="ri-graduation-cap-line"></i>
                {{ __('supervisor.observation.academic_sessions') }}
                <span class="text-xs bg-violet-100 text-violet-700 px-1.5 py-0.5 rounded-full">{{ $academicSessions->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- Date Filters -->
    <div class="flex gap-2 mb-6">
        @foreach(['all' => __('supervisor.observation.filter_all'), 'today' => __('supervisor.observation.filter_today'), 'week' => __('supervisor.observation.filter_week')] as $value => $label)
            <a href="{{ route('supervisor.sessions-monitoring.index', ['subdomain' => $subdomain, 'tab' => $tab, 'date' => $value]) }}"
               class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                   {{ $dateFilter === $value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Sessions List -->
    @php
        $sessions = match($tab) {
            'academic' => $academicSessions,
            'interactive' => $interactiveSessions,
            default => $quranSessions,
        };
    @endphp

    @if($sessions->isNotEmpty())
        <div class="space-y-3">
            @foreach($sessions as $session)
                @php
                    $sessionStatus = is_object($session->status) ? $session->status->value : $session->status;
                    $isLive = in_array($sessionStatus, ['live', 'ongoing', 'ready']);
                    $hasMeeting = $session->meeting && $session->meeting->meeting_link;

                    if ($tab === 'quran') {
                        $title = $session->circle?->name ?? $session->individualCircle?->student?->name ?? $session->student?->name ?? '';
                        $teacherName = $session->quranTeacher?->name ?? '';
                        $sessionType = $session->circle ? __('supervisor.observation.session_type_group') : __('supervisor.observation.session_type_individual');
                    } else {
                        $title = $session->student?->name ?? '';
                        $teacherName = $session->academicTeacher?->user?->name ?? '';
                        $sessionType = __('supervisor.observation.session_type_individual');
                    }

                    $statusClass = match($sessionStatus) {
                        'live', 'ongoing', 'ready' => 'bg-green-100 text-green-700 border-green-200',
                        'scheduled' => 'bg-blue-100 text-blue-700 border-blue-200',
                        'completed' => 'bg-gray-100 text-gray-700 border-gray-200',
                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                        default => 'bg-gray-100 text-gray-700 border-gray-200',
                    };
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5 flex flex-col md:flex-row md:items-center gap-3 md:gap-4
                    {{ $isLive ? 'ring-2 ring-green-200' : '' }}">
                    <!-- Session Info -->
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                            {{ $isLive ? 'bg-green-100' : 'bg-gray-100' }}">
                            <i class="{{ $tab === 'quran' ? 'ri-book-read-line' : 'ri-graduation-cap-line' }}
                                {{ $isLive ? 'text-green-600' : 'text-gray-500' }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $title }}</p>
                                @if($isLive)
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 animate-pulse">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        {{ __('supervisor.observation.meeting_active') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $teacherName }} · {{ $sessionType }}
                                · {{ $session->scheduled_at?->translatedFormat('d M - h:i A') ?? '' }}
                            </p>
                        </div>
                    </div>

                    <!-- Status & Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs px-2.5 py-1 rounded-full border {{ $statusClass }}">
                            {{ $sessionStatus }}
                        </span>

                        @if($isLive && $hasMeeting)
                            <a href="{{ route('sessions.monitoring.show', ['subdomain' => $subdomain, 'sessionType' => $tab, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                <i class="ri-eye-line"></i>
                                {{ __('supervisor.observation.join_observation') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-live-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">{{ __('supervisor.observation.no_sessions') }}</h3>
            <p class="text-sm text-gray-500">{{ __('supervisor.observation.no_sessions_description') }}</p>
        </div>
    @endif
</div>

</x-layouts.supervisor>
