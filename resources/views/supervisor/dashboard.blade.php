<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <!-- Welcome Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">
            {{ __('supervisor.dashboard.welcome_message', ['name' => $user->name]) }}
        </h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
            {{ __('supervisor.dashboard.welcome_subtitle') }}
        </p>
    </div>

    <!-- KPI Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-6 md:mb-8">
        <!-- Total Teachers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-team-line text-lg md:text-xl text-indigo-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $totalTeachers }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.total_assigned_teachers') }}</p>
                </div>
            </div>
            <div class="mt-2 flex gap-3 text-xs text-gray-500">
                @if($quranTeachersCount > 0)
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        {{ $quranTeachersCount }} {{ __('supervisor.dashboard.quran_teachers') }}
                    </span>
                @endif
                @if($academicTeachersCount > 0)
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-violet-400 rounded-full"></span>
                        {{ $academicTeachersCount }} {{ __('supervisor.dashboard.academic_teachers') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Active Entities -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-book-open-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $activeCircles + $activeLessons }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.active_entities') }}</p>
                </div>
            </div>
            <div class="mt-2 flex gap-3 text-xs text-gray-500">
                @if($activeCircles > 0)
                    <span>{{ $activeCircles }} {{ __('supervisor.dashboard.active_circles') }}</span>
                @endif
                @if($activeLessons > 0)
                    <span>{{ $activeLessons }} {{ __('supervisor.dashboard.active_lessons') }}</span>
                @endif
            </div>
        </div>

        <!-- Sessions Today -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-lg md:text-xl text-amber-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $sessionsToday }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.sessions_today') }}</p>
                </div>
            </div>
        </div>

        <!-- Sessions This Week -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $sessionsThisWeek }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.sessions_this_week') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Upcoming Sessions -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                <i class="ri-time-line text-indigo-500 me-1.5"></i>
                {{ __('supervisor.dashboard.upcoming_sessions') }}
            </h2>

            @if($upcomingSessions->isNotEmpty())
                <div class="space-y-3">
                    @foreach($upcomingSessions as $session)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $session['type'] === 'quran' ? 'bg-green-100' : 'bg-violet-100' }}">
                                <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-green-600' : 'ri-graduation-cap-line text-violet-600' }}"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $session['title'] }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $session['teacher_name'] }}
                                    · {{ $session['scheduled_at']->translatedFormat('D d M - h:i A') }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full flex-shrink-0
                                {{ $session['type'] === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                                {{ $session['type'] === 'quran' ? __('supervisor.dashboard.quran_teachers') : __('supervisor.dashboard.academic_teachers') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-sm text-gray-500">{{ __('supervisor.dashboard.no_upcoming_sessions') }}</p>
                </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                <i class="ri-flashlight-line text-amber-500 me-1.5"></i>
                {{ __('supervisor.dashboard.quick_actions') }}
            </h2>

            <div class="space-y-2.5">
                <a href="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}"
                   class="flex items-center gap-3 p-3 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors">
                    <i class="ri-team-line text-lg"></i>
                    <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_teachers') }}</span>
                </a>

                <a href="{{ route('manage.calendar.index', ['subdomain' => $subdomain]) }}"
                   class="flex items-center gap-3 p-3 rounded-lg bg-green-50 hover:bg-green-100 text-green-700 transition-colors">
                    <i class="ri-calendar-schedule-line text-lg"></i>
                    <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_calendar') }}</span>
                </a>

                <a href="{{ route('manage.sessions-monitoring.index', ['subdomain' => $subdomain]) }}"
                   class="flex items-center gap-3 p-3 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors">
                    <i class="ri-live-line text-lg"></i>
                    <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_monitoring') }}</span>
                </a>

                <a href="{{ route('manage.session-reports.index', ['subdomain' => $subdomain]) }}"
                   class="flex items-center gap-3 p-3 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 transition-colors">
                    <i class="ri-file-chart-line text-lg"></i>
                    <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_reports') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
