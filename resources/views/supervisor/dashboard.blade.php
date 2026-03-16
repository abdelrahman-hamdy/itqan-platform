<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $currency = getCurrencySymbol();
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

    {{-- Needs Attention Section --}}
    <livewire:supervisor.needs-attention />

    {{-- General Stats --}}
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('supervisor.dashboard.section_general_stats') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-6">
        {{-- Total Users --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-group-line text-lg md:text-xl text-emerald-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalUsers']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_users') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $generalStats['activeUsers'] }} {{ __('supervisor.dashboard.active') }}، {{ $generalStats['inactiveUsers'] }} {{ __('supervisor.dashboard.inactive') }}</p>
        </div>

        {{-- Total Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-money-dollar-circle-line text-lg md:text-xl text-yellow-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalIncome'], 0) }} {{ $currency }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_total_revenue') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Sessions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalSessions']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_total_sessions') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $generalStats['passedSessions'] }} {{ __('supervisor.dashboard.passed') }}، {{ $generalStats['scheduledSessions'] }} {{ __('supervisor.dashboard.scheduled') }}</p>
        </div>

        {{-- Students --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-3-line text-lg md:text-xl text-emerald-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalStudents']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_students') }}</p>
                </div>
            </div>
        </div>

        {{-- Quran Teachers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-book-read-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalQuranTeachers']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_quran_teachers') }}</p>
                </div>
            </div>
        </div>

        {{-- Academic Teachers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-graduation-cap-line text-lg md:text-xl text-violet-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalAcademicTeachers']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_academic_teachers') }}</p>
                </div>
            </div>
        </div>

        {{-- Parents --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-parent-line text-lg md:text-xl text-gray-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalParents']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_parents') }}</p>
                </div>
            </div>
        </div>

        {{-- Supervisors (admin/superadmin only) --}}
        @if($isAdmin)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-shield-user-line text-lg md:text-xl text-indigo-600"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($generalStats['totalSupervisors']) }}</p>
                        <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_supervisors') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Monthly Stats --}}
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('supervisor.dashboard.section_this_month') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-6 md:mb-8">
        {{-- Active Subscriptions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-bank-card-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($monthlyStats['totalActiveSubs']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_active_subs') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $monthlyStats['activeQuranSubs'] }} {{ __('supervisor.dashboard.quran') }}، {{ $monthlyStats['activeAcademicSubs'] }} {{ __('supervisor.dashboard.academic') }}</p>
        </div>

        {{-- Sessions This Month --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-line text-lg md:text-xl text-amber-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($monthlyStats['monthSessions']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_month_sessions') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $monthlyStats['monthQuranSessions'] }} {{ __('supervisor.dashboard.quran') }}، {{ $monthlyStats['monthAcademicSessions'] }} {{ __('supervisor.dashboard.academic') }}، {{ $monthlyStats['monthInteractiveSessions'] }} {{ __('supervisor.dashboard.interactive') }}</p>
        </div>

        {{-- Monthly Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 {{ $monthlyStats['revenueGrowth'] >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-money-dollar-circle-line text-lg md:text-xl {{ $monthlyStats['revenueGrowth'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($monthlyStats['thisMonthRevenue'], 0) }} {{ $currency }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_month_revenue') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs {{ $monthlyStats['revenueGrowth'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                <i class="{{ $monthlyStats['revenueGrowth'] >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line' }}"></i>
                {{ $monthlyStats['revenueGrowth'] >= 0 ? '+' : '' }}{{ $monthlyStats['revenueGrowth'] }}% {{ __('supervisor.dashboard.vs_last_month') }}
            </p>
        </div>

        {{-- New Users --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-add-line text-lg md:text-xl text-indigo-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($monthlyStats['newUsers']) }}</p>
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('supervisor.dashboard.stat_new_users') }}</p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $monthlyStats['newStudents'] }} {{ __('supervisor.dashboard.student') }}، {{ $monthlyStats['newTeachers'] }} {{ __('supervisor.dashboard.teacher') }}، {{ $monthlyStats['newParents'] }} {{ __('supervisor.dashboard.parent') }}</p>
        </div>
    </div>

    @if($isAdmin && $chartData)
        {{-- Admin/SuperAdmin: Analytics Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
            {{-- User Growth Chart --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-line-chart-line text-emerald-500 me-1.5"></i>
                    {{ __('supervisor.dashboard.chart_user_growth') }}
                </h2>
                <div style="height: 300px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            {{-- Session Activity Chart --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-bar-chart-grouped-line text-blue-500 me-1.5"></i>
                    {{ __('supervisor.dashboard.chart_session_activity') }}
                </h2>
                <div style="height: 300px;">
                    <canvas id="sessionActivityChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Earnings Breakdown Chart (full width) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                <i class="ri-money-dollar-circle-line text-yellow-500 me-1.5"></i>
                {{ __('supervisor.dashboard.chart_earnings') }}
            </h2>
            <div style="height: 320px;">
                <canvas id="earningsChart"></canvas>
            </div>
        </div>

        @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartData = @json($chartData);
            const chartFont = { family: 'Tajawal', size: 12 };
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: chartFont } },
                    tooltip: { mode: 'index', intersect: false, bodyFont: chartFont, titleFont: chartFont },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: chartFont, maxRotation: 45 } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0, font: chartFont } },
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false },
            };

            function makeDataset(item, fill) {
                return {
                    label: item.label,
                    data: item.data,
                    borderColor: item.color,
                    backgroundColor: item.color + '1A',
                    pointBackgroundColor: item.color,
                    pointBorderColor: item.color,
                    fill: fill,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 2,
                };
            }

            // User Growth
            new Chart(document.getElementById('userGrowthChart'), {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.userGrowth.map(d => makeDataset(d, true)),
                },
                options: commonOptions,
            });

            // Session Activity
            new Chart(document.getElementById('sessionActivityChart'), {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.sessionActivity.map(d => makeDataset(d, true)),
                },
                options: { ...commonOptions, scales: { ...commonOptions.scales, y: { ...commonOptions.scales.y, ticks: { ...commonOptions.scales.y.ticks, stepSize: 1 } } } },
            });

            // Earnings Breakdown (bar chart, stacked)
            const currency = @json(getCurrencySymbol());
            new Chart(document.getElementById('earningsChart'), {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.earningsBreakdown.map(item => ({
                        label: item.label,
                        data: item.data,
                        backgroundColor: item.color + 'CC',
                        borderColor: item.color,
                        borderWidth: 1,
                        borderRadius: 3,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: chartFont } },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            bodyFont: chartFont,
                            titleFont: chartFont,
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': ' + Number(ctx.parsed.y).toLocaleString() + ' ' + currency;
                                },
                                footer: function(items) {
                                    const total = items.reduce((s, i) => s + i.parsed.y, 0);
                                    return total > 0 ? ('{{ __("supervisor.dashboard.total") }}: ' + total.toLocaleString() + ' ' + currency) : '';
                                },
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: chartFont, maxRotation: 45 } },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                font: chartFont,
                                callback: function(v) { return v.toLocaleString() + ' ' + currency; },
                            },
                        },
                    },
                    interaction: { mode: 'index', intersect: false },
                },
            });
        });
        </script>
        @endpush
    @else
        {{-- Supervisor: Upcoming Sessions + Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-time-line text-blue-500 me-1.5"></i>
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
                                    {{ $session['type'] === 'quran' ? __('supervisor.dashboard.quran') : __('supervisor.dashboard.academic') }}
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
                    <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition-colors">
                        <i class="ri-live-line text-lg"></i>
                        <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_monitoring') }}</span>
                    </a>
                    <a href="{{ route('manage.session-reports.index', ['subdomain' => $subdomain]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg bg-violet-50 hover:bg-violet-100 text-violet-700 transition-colors">
                        <i class="ri-file-chart-line text-lg"></i>
                        <span class="text-sm font-medium">{{ __('supervisor.dashboard.go_to_reports') }}</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

</x-layouts.supervisor>
