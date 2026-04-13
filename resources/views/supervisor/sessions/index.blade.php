<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $t = 'supervisor.sessions.';
@endphp

<div x-data="sessionsFilters()" class="space-y-6">
    {{-- Breadcrumb --}}
    <x-ui.breadcrumb
        :items="[['label' => __($t.'breadcrumb')]]"
        view-type="supervisor"
    />

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __($t.'page_title') }}</h1>
            <p class="mt-1 text-sm md:text-base text-gray-600">{{ __($t.'page_subtitle') }}</p>
        </div>
        <x-ui.timezone-clock />
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        {{-- Total --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <i class="ri-stack-line text-lg text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'stat_total') }}</p>
                </div>
            </div>
        </div>
        {{-- Live Now --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 {{ $stats['live_now'] > 0 ? 'ring-2 ring-green-200' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <i class="ri-live-line text-lg text-green-600 {{ $stats['live_now'] > 0 ? 'animate-pulse' : '' }}"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['live_now']) }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'stat_live') }}</p>
                </div>
            </div>
        </div>
        {{-- Scheduled Today --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="ri-calendar-line text-lg text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['scheduled_today']) }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'stat_scheduled_today') }}</p>
                </div>
            </div>
        </div>
        {{-- Completed This Week --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <i class="ri-check-double-line text-lg text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed_week']) }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'stat_completed_week') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        {{-- Session Type Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-0 overflow-x-auto px-4" aria-label="Session Type Tabs">
                @php
                    $tabs = [
                        'all' => ['label' => __($t.'tab_all'), 'icon' => 'ri-apps-line', 'color' => 'indigo', 'count' => $tabCounts['quran'] + $tabCounts['academic'] + $tabCounts['interactive']],
                        'quran' => ['label' => __($t.'tab_quran'), 'icon' => 'ri-book-read-line', 'color' => 'green', 'count' => $tabCounts['quran']],
                        'academic' => ['label' => __($t.'tab_academic'), 'icon' => 'ri-graduation-cap-line', 'color' => 'violet', 'count' => $tabCounts['academic']],
                        'interactive' => ['label' => __($t.'tab_interactive'), 'icon' => 'ri-video-chat-line', 'color' => 'blue', 'count' => $tabCounts['interactive']],
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tabConfig)
                    <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'tab' => $tabKey, 'date' => $dateFilter, 'status' => $statusFilter, 'teacher_id' => $teacherId, 'student_id' => $studentId, 'search' => $search]) }}"
                       class="whitespace-nowrap border-b-2 py-3 px-3 md:px-4 text-sm font-medium transition-colors flex items-center gap-1.5
                           {{ $activeTab === $tabKey
                               ? 'border-'.$tabConfig['color'].'-500 text-'.$tabConfig['color'].'-600'
                               : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        <i class="{{ $tabConfig['icon'] }}"></i>
                        <span class="hidden sm:inline">{{ $tabConfig['label'] }}</span>
                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $activeTab === $tabKey ? 'bg-'.$tabConfig['color'].'-100 text-'.$tabConfig['color'].'-700' : 'bg-gray-100 text-gray-600' }}">{{ $tabConfig['count'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Filters Row --}}
        @php
            $filterUrl = fn(array $overrides = []) => route('manage.sessions.index', array_filter(array_merge([
                'subdomain' => $subdomain, 'tab' => $activeTab, 'date' => $dateFilter,
                'status' => $statusFilter, 'teacher_id' => $teacherId, 'student_id' => $studentId,
                'search' => $search, 'date_from' => request('date_from'), 'date_to' => request('date_to'),
            ], $overrides)));
            $hasFilters = $statusFilter || $dateFilter !== 'all' || $teacherId || $studentId || $search || request('date_from');
        @endphp
        <div class="p-4 space-y-3" x-data="{ showCustomDate: {{ $dateFilter === 'custom' ? 'true' : 'false' }} }">
            {{-- Date pills --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-gray-500">{{ __($t.'col_scheduled') }}:</span>
                @foreach(['all' => __($t.'filter_date_all'), 'today' => __($t.'filter_date_today'), 'week' => __($t.'filter_date_week'), 'month' => __($t.'filter_date_month')] as $dateVal => $dateLabel)
                    <a href="{{ $filterUrl(['date' => $dateVal, 'date_from' => null, 'date_to' => null]) }}"
                       @click="showCustomDate = false"
                       class="px-3 py-1 text-xs font-medium rounded-full transition-colors
                           {{ $dateFilter === $dateVal ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $dateLabel }}
                    </a>
                @endforeach
                <button @click="showCustomDate = !showCustomDate"
                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors
                        {{ $dateFilter === 'custom' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    <i class="ri-calendar-2-line me-0.5"></i>
                    {{ __($t.'filter_date_custom') }}
                </button>
            </div>

            {{-- Custom date range (collapsible) --}}
            <div x-show="showCustomDate" x-collapse class="flex flex-wrap items-end gap-2">
                <form method="GET" action="{{ route('manage.sessions.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="date" value="custom">
                    @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                    @if($teacherId)<input type="hidden" name="teacher_id" value="{{ $teacherId }}">@endif
                    @if($studentId)<input type="hidden" name="student_id" value="{{ $studentId }}">@endif
                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __($t.'filter_date_from') }}</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __($t.'filter_date_to') }}</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="ri-filter-line me-1"></i>{{ __($t.'btn_search') }}
                    </button>
                </form>
            </div>

            {{-- Status + Teacher + Student + Search Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                {{-- Status dropdown --}}
                <select onchange="if(this.value !== '') { window.location.href = this.value; }"
                    class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="{{ $filterUrl(['status' => null]) }}" {{ !$statusFilter ? 'selected' : '' }}>
                        {{ __($t.'filter_status') }}: {{ __($t.'filter_date_all') }}
                    </option>
                    @foreach(\App\Enums\SessionStatus::cases() as $statusEnum)
                        @if(!in_array($statusEnum, [\App\Enums\SessionStatus::UNSCHEDULED, \App\Enums\SessionStatus::SUSPENDED]))
                        <option value="{{ $filterUrl(['status' => $statusEnum->value]) }}" {{ $statusFilter === $statusEnum->value ? 'selected' : '' }}>
                            {{ $statusEnum->label() }}
                        </option>
                        @endif
                    @endforeach
                    <option value="{{ $filterUrl(['status' => 'need_review']) }}" {{ $statusFilter === 'need_review' ? 'selected' : '' }}>
                        {{ __($t.'filter_need_review') }}
                    </option>
                </select>

                {{-- Teacher searchable select with gender filter --}}
                @if(!empty($teachers))
                <x-ui.searchable-select
                    name="teacher_id"
                    :options="$teachers"
                    :selected="$teacherId"
                    :placeholder="__('supervisor.common.all_teachers')"
                    :showGenderFilter="true"
                    :showTypeFilter="false"
                />
                @endif

                {{-- Student searchable select with gender filter --}}
                @if(!empty($students))
                <x-ui.searchable-select
                    name="student_id"
                    :options="$students"
                    :selected="$studentId"
                    :placeholder="__($t.'all_students')"
                    :showGenderFilter="true"
                    :showTypeFilter="false"
                    :searchPlaceholder="__($t.'search_placeholder')"
                    :emptyMessage="__($t.'no_sessions')"
                    :maleLabel="__('common.form.male')"
                    :femaleLabel="__('common.form.female')"
                />
                @endif

                {{-- Search input --}}
                <div class="relative">
                    <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <form method="GET" action="{{ route('manage.sessions.index', ['subdomain' => $subdomain]) }}">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <input type="hidden" name="date" value="{{ $dateFilter }}">
                        @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                        @if($teacherId)<input type="hidden" name="teacher_id" value="{{ $teacherId }}">@endif
                        @if($studentId)<input type="hidden" name="student_id" value="{{ $studentId }}">@endif
                        @if(request('date_from'))<input type="hidden" name="date_from" value="{{ request('date_from') }}">@endif
                        @if(request('date_to'))<input type="hidden" name="date_to" value="{{ request('date_to') }}">@endif
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="{{ __($t.'search_placeholder') }}"
                            class="w-full ps-9 pe-3 py-2 text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            onkeydown="if(event.key==='Enter') this.form.submit()">
                    </form>
                </div>
            </div>

            {{-- Action buttons row --}}
            @if($hasFilters)
            <div class="flex items-center justify-between">
                <span class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg whitespace-nowrap">
                    {{ $sessions->total() }} {{ __($t.'results') }}
                </span>
                <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'tab' => $activeTab]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                    <i class="ri-refresh-line"></i>
                    {{ __($t.'btn_reset') }}
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Student filter banner --}}
    @if($studentId && $studentName)
        <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
            <i class="ri-user-line"></i>
            <span>{{ __($t.'filtering_by_student', ['name' => $studentName]) }}</span>
            <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'tab' => $activeTab, 'date' => $dateFilter, 'status' => $statusFilter, 'teacher_id' => $teacherId, 'search' => $search]) }}"
               class="ms-auto inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 rounded transition-colors">
                <i class="ri-close-line"></i>
                {{ __($t.'clear_filters') }}
            </a>
        </div>
    @endif

    {{-- Sessions Table / List --}}
    @if($sessions->isNotEmpty())
        {{-- Desktop Table --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <table class="min-w-[1440px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'col_status') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'session_info') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'col_type') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'col_teacher') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'col_student') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[110px]">{{ __($t.'col_scheduled') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[140px]">{{ __('supervisor.sessions.col_attendance') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">{{ __($t.'col_counting') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($t.'col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($sessions as $session)
                        @include('supervisor.sessions._session-row', ['session' => $session, 'subdomain' => $subdomain])
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-3">
            @foreach($sessions as $session)
                @include('supervisor.sessions._session-card', ['session' => $session, 'subdomain' => $subdomain])
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-calendar-check-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">{{ __($t.'no_sessions') }}</h3>
            <p class="text-sm text-gray-500">{{ __($t.'no_sessions_description') }}</p>
        </div>
    @endif
</div>

<script>
function sessionsFilters() {
    return {}
}

document.addEventListener('alpine:init', () => {
    Alpine.store('presence', { sessions: {} });
});

document.addEventListener('DOMContentLoaded', () => {
    const liveSessions = @json($liveSessions);
    if (!liveSessions.length) return;

    const presenceUrl = @json(route('manage.sessions.live-presence', ['subdomain' => $subdomain]));

    async function fetchPresence() {
        try {
            const params = new URLSearchParams();
            liveSessions.forEach(id => params.append('sessions[]', id));
            const res = await fetch(`${presenceUrl}?${params}`);
            if (res.ok) {
                Alpine.store('presence').sessions = await res.json();
            }
        } catch (e) { /* silent */ }
    }

    fetchPresence();
    setInterval(fetchPresence, 15000);
});
</script>

</x-layouts.supervisor>
