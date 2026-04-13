<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $t = 'supervisor.recording.';
@endphp

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __($t.'page_title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __($t.'page_subtitle') }}</p>
    </div>
    <x-ui.timezone-clock />
</div>

<!-- Capacity Dashboard -->
<div x-data="recordingCapacity()" x-init="init()" class="mb-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Active Recordings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full" :class="activeCount > 0 ? 'bg-red-500 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __($t.'capacity_active') }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                <span x-text="activeCount">{{ $capacityStatus['active_count'] }}</span>
                <span class="text-sm font-normal text-gray-400">/ <span x-text="maxCount">{{ $capacityStatus['max_count'] }}</span></span>
            </div>
            <!-- Progress bar -->
            <div class="mt-2 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     :class="utilization >= 100 ? 'bg-red-500' : utilization >= 70 ? 'bg-amber-500' : 'bg-green-500'"
                     :style="'width: ' + Math.min(utilization, 100) + '%'"></div>
            </div>
        </div>

        <!-- Queued -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 mb-2">
                <i class="ri-time-line text-blue-500"></i>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __($t.'capacity_queued') }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="queuedCount">{{ $capacityStatus['queued_count'] }}</div>
        </div>

        <!-- Server Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 mb-2">
                <i class="ri-server-line text-gray-500"></i>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __($t.'capacity_server_status') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full"
                      :class="serverStatus === 'healthy' ? 'bg-green-500' : serverStatus === 'at_capacity' ? 'bg-amber-500' : 'bg-red-500'"></span>
                <span class="text-sm font-medium text-gray-900 dark:text-white"
                      x-text="serverStatus === 'healthy' ? '{{ __($t.'server_healthy') }}' : serverStatus === 'at_capacity' ? '{{ __($t.'server_at_capacity') }}' : '{{ __($t.'server_error') }}'">
                    {{ __($t.'server_' . $capacityStatus['server_status']) }}
                </span>
            </div>
        </div>

        <!-- Recorded Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 mb-2">
                <i class="ri-checkbox-circle-line text-green-500"></i>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __($t.'capacity_recorded_today') }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="recordedToday">{{ $recordedToday }}</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-4">
    <a href="{{ route('manage.recording.index', ['subdomain' => $subdomain, 'tab' => 'live']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $activeTab === 'live' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        {{ __($t.'tab_live') }}
    </a>
    <a href="{{ route('manage.recording.index', ['subdomain' => $subdomain, 'tab' => 'history']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $activeTab === 'history' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        {{ __($t.'tab_history') }}
    </a>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 mb-6">
    <form method="GET" action="{{ route('manage.recording.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap gap-3 items-end">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        <!-- Session Type -->
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __($t.'filter_session_type') }}</label>
            <select name="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" onchange="this.form.submit()">
                <option value="all">{{ __($t.'filter_all_types') }}</option>
                @foreach($allowedTypes as $type)
                    <option value="{{ $type }}" {{ $typeFilter === $type ? 'selected' : '' }}>
                        {{ __($t.'type_' . $type) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Recording Status -->
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __($t.'filter_recording_status') }}</label>
            <select name="recording_status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" onchange="this.form.submit()">
                <option value="">{{ __($t.'filter_all_statuses') }}</option>
                <option value="recording" {{ $statusFilter === 'recording' ? 'selected' : '' }}>{{ __($t.'status_recording') }}</option>
                <option value="queued" {{ $statusFilter === 'queued' ? 'selected' : '' }}>{{ __($t.'status_queued') }}</option>
                <option value="manual" {{ $statusFilter === 'manual' ? 'selected' : '' }}>{{ __($t.'status_manual') }}</option>
                <option value="none" {{ $statusFilter === 'none' ? 'selected' : '' }}>{{ __($t.'status_none') }}</option>
            </select>
        </div>

        <!-- Teacher -->
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __($t.'filter_teacher') }}</label>
            <select name="teacher_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" onchange="this.form.submit()">
                <option value="">{{ __($t.'filter_all_teachers') }}</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher['id'] }}" {{ $teacherId == $teacher['id'] ? 'selected' : '' }}>
                        {{ $teacher['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __($t.'filter_search') }}</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __($t.'filter_search') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
        </div>

        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700">
            <i class="ri-search-line"></i>
        </button>
    </form>
</div>

@if($activeTab === 'live')
    <!-- Live Sessions -->
    @if($sessions->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl p-12 border border-gray-200 dark:border-gray-700 text-center">
            <i class="ri-live-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __($t.'no_live_sessions') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __($t.'no_live_sessions_description') }}</p>
        </div>
    @else
        <!-- Desktop Table -->
        <div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'session') }}</th>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'type') }}</th>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'teacher') }}</th>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'time') }}</th>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'status') }}</th>
                        <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($sessions as $session)
                        @include('supervisor.recording._recording-session-row', ['session' => $session])
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
            @foreach($sessions as $session)
                @include('supervisor.recording._recording-session-card', ['session' => $session])
            @endforeach
        </div>
    @endif

@else
    <!-- History Tab (loaded via AJAX) -->
    <div x-data="recordingHistory()" x-init="loadHistory()">
        <!-- Stats bar -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-green-700 dark:text-green-400" x-text="stats.total_recorded">0</div>
                <div class="text-xs text-green-600 dark:text-green-500">{{ __($t.'history_total_recorded') }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-gray-700 dark:text-gray-400" x-text="stats.total_skipped">0</div>
                <div class="text-xs text-gray-600 dark:text-gray-500">{{ __($t.'history_total_skipped') }}</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-red-700 dark:text-red-400" x-text="stats.total_failed">0</div>
                <div class="text-xs text-red-600 dark:text-red-500">{{ __($t.'history_total_failed') }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-blue-700 dark:text-blue-400" x-text="formatDuration(stats.total_duration)">00:00</div>
                <div class="text-xs text-blue-600 dark:text-blue-500">{{ __($t.'history_total_duration') }}</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-purple-700 dark:text-purple-400" x-text="formatBytes(stats.total_storage)">0 B</div>
                <div class="text-xs text-purple-600 dark:text-purple-500">{{ __($t.'history_storage_used') }}</div>
            </div>
        </div>

        <!-- History list -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <template x-if="loading">
                <div class="p-12 text-center">
                    <i class="ri-loader-4-line animate-spin text-2xl text-gray-400"></i>
                </div>
            </template>
            <template x-if="!loading && recordings.length === 0">
                <div class="p-12 text-center">
                    <i class="ri-history-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __($t.'no_history') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __($t.'no_history_description') }}</p>
                </div>
            </template>
            <template x-if="!loading && recordings.length > 0">
                <div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'session') }}</th>
                                <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'time') }}</th>
                                <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="rec in recordings" :key="rec.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <span x-text="rec.display_name" class="font-medium text-gray-900 dark:text-white"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="rec.created_at"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': rec.status === 'completed',
                                                  'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': rec.status === 'skipped',
                                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': rec.status === 'failed',
                                              }"
                                              x-text="rec.status_label"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
@endif

<script>
function recordingCapacity() {
    return {
        activeCount: {{ $capacityStatus['active_count'] }},
        maxCount: {{ $capacityStatus['max_count'] }},
        queuedCount: {{ $capacityStatus['queued_count'] }},
        utilization: {{ $capacityStatus['utilization_percentage'] }},
        serverStatus: '{{ $capacityStatus['server_status'] }}',
        recordedToday: {{ $recordedToday }},

        init() {
            setInterval(() => {
                if (!document.hidden) this.refresh();
            }, 15000);
        },

        async refresh() {
            try {
                const res = await fetch('{{ route('manage.recording.capacity', ['subdomain' => $subdomain]) }}');
                const data = await res.json();
                this.activeCount = data.active_count;
                this.maxCount = data.max_count;
                this.queuedCount = data.queued_count;
                this.utilization = data.utilization_percentage;
                this.serverStatus = data.server_status;
                this.recordedToday = data.recorded_today ?? this.recordedToday;
            } catch (e) {
                console.error('Failed to refresh capacity:', e);
            }
        }
    };
}

function recordingHistory() {
    return {
        recordings: [],
        stats: { total_recorded: 0, total_skipped: 0, total_failed: 0, total_duration: 0, total_storage: 0 },
        loading: true,

        async loadHistory() {
            try {
                const res = await fetch('{{ route('manage.recording.history', ['subdomain' => $subdomain]) }}');
                const data = await res.json();
                this.recordings = data.recordings?.data ?? [];
                this.stats = data.stats ?? this.stats;
            } catch (e) {
                console.error('Failed to load history:', e);
            } finally {
                this.loading = false;
            }
        },

        formatDuration(seconds) {
            if (!seconds) return '00:00';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            if (h > 0) return h + 'h ' + m + 'm';
            return m + 'm';
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes > 1024 && i < units.length - 1) { bytes /= 1024; i++; }
            return Math.round(bytes * 100) / 100 + ' ' + units[i];
        }
    };
}
</script>

</x-layouts.supervisor>
