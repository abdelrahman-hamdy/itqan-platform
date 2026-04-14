<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $t = 'supervisor.recording.';

    $filterUrl = fn(array $overrides = []) => route('manage.recording.index', array_filter(array_merge([
        'subdomain' => $subdomain, 'tab' => $activeTab, 'session_tab' => $sessionTab,
        'recording_status' => $statusFilter, 'teacher_id' => $teacherId, 'student_id' => $studentId,
        'search' => $search, 'date_from' => request('date_from'), 'date_to' => request('date_to'),
    ], $overrides)));
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        <!-- Active Recordings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 {{ ($capacityStatus['active_count'] ?? 0) > 0 ? 'ring-2 ring-red-200' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                    <i class="ri-record-circle-line text-lg text-red-600" :class="activeCount > 0 ? 'animate-pulse' : ''"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span x-text="activeCount">{{ $capacityStatus['active_count'] }}</span>
                        <span class="text-sm font-normal text-gray-400">/ <span x-text="maxCount">{{ $capacityStatus['max_count'] }}</span></span>
                    </p>
                    <p class="text-xs text-gray-500">{{ __($t.'capacity_active') }}</p>
                </div>
            </div>
            <div class="mt-2 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     :class="utilization >= 100 ? 'bg-red-500' : utilization >= 70 ? 'bg-amber-500' : 'bg-green-500'"
                     :style="'width: ' + Math.min(utilization, 100) + '%'"></div>
            </div>
        </div>

        <!-- Queued -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                    <i class="ri-time-line text-lg text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="queuedCount">{{ $capacityStatus['queued_count'] }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'capacity_queued') }}</p>
                </div>
            </div>
        </div>

        <!-- Server Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                    <i class="ri-server-line text-lg text-gray-600"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full"
                              :class="serverStatus === 'healthy' ? 'bg-green-500' : serverStatus === 'at_capacity' ? 'bg-amber-500' : 'bg-red-500'"></span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white"
                              x-text="serverStatus === 'healthy' ? '{{ __($t.'server_healthy') }}' : serverStatus === 'at_capacity' ? '{{ __($t.'server_at_capacity') }}' : '{{ __($t.'server_error') }}'"></span>
                    </div>
                    <p class="text-xs text-gray-500">{{ __($t.'capacity_server_status') }}</p>
                </div>
            </div>
        </div>

        <!-- Recorded Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                    <i class="ri-checkbox-circle-line text-lg text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="recordedToday">{{ $recordedToday }}</p>
                    <p class="text-xs text-gray-500">{{ __($t.'capacity_recorded_today') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Tabs: Live / History -->
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

    {{-- Live / History tabs --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700">
        <a href="{{ $filterUrl(['tab' => 'live', 'session_tab' => 'all']) }}"
           class="px-6 py-3 text-sm font-medium border-b-2 transition {{ $activeTab === 'live' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="ri-live-line"></i> {{ __($t.'tab_live') }}
        </a>
        <a href="{{ $filterUrl(['tab' => 'history', 'session_tab' => 'all']) }}"
           class="px-6 py-3 text-sm font-medium border-b-2 transition {{ $activeTab === 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="ri-history-line"></i> {{ __($t.'tab_history') }}
        </a>
    </div>

    {{-- Session Type Sub-Tabs (same as sessions management) --}}
    @php
        $sessionTabs = [
            'all' => ['label' => __('supervisor.sessions.tab_all'), 'icon' => 'ri-apps-line', 'color' => 'indigo'],
            'quran' => ['label' => __('supervisor.sessions.tab_quran'), 'icon' => 'ri-book-read-line', 'color' => 'green'],
            'academic' => ['label' => __('supervisor.sessions.tab_academic'), 'icon' => 'ri-graduation-cap-line', 'color' => 'violet'],
            'interactive' => ['label' => __('supervisor.sessions.tab_interactive'), 'icon' => 'ri-video-chat-line', 'color' => 'blue'],
        ];
    @endphp
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-0 overflow-x-auto px-4" aria-label="Session Type Tabs">
            @foreach($sessionTabs as $tabKey => $tabConfig)
                <a href="{{ $filterUrl(['session_tab' => $tabKey]) }}"
                   class="whitespace-nowrap border-b-2 py-3 px-3 md:px-4 text-sm font-medium transition-colors flex items-center gap-1.5
                       {{ $sessionTab === $tabKey
                           ? 'border-'.$tabConfig['color'].'-500 text-'.$tabConfig['color'].'-600'
                           : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    <i class="{{ $tabConfig['icon'] }}"></i>
                    <span class="hidden sm:inline">{{ $tabConfig['label'] }}</span>
                    @if($activeTab === 'live' && isset($tabCounts[$tabKey]))
                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $sessionTab === $tabKey ? 'bg-'.$tabConfig['color'].'-100 text-'.$tabConfig['color'].'-700' : 'bg-gray-100 text-gray-600' }}">{{ $tabCounts[$tabKey] }}</span>
                    @elseif($activeTab === 'live' && $tabKey === 'all' && !empty($tabCounts))
                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $sessionTab === 'all' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ array_sum($tabCounts) }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Filters (grid layout matching sessions page) --}}
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
            {{-- Recording Status --}}
            <select onchange="if(this.value !== '') { window.location.href = this.value; }"
                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="{{ $filterUrl(['recording_status' => null]) }}" {{ !$statusFilter ? 'selected' : '' }}>
                    {{ __($t.'filter_recording_status') }}: {{ __($t.'filter_all_statuses') }}
                </option>
                <option value="{{ $filterUrl(['recording_status' => 'recording']) }}" {{ $statusFilter === 'recording' ? 'selected' : '' }}>{{ __($t.'status_recording') }}</option>
                <option value="{{ $filterUrl(['recording_status' => 'queued']) }}" {{ $statusFilter === 'queued' ? 'selected' : '' }}>{{ __($t.'status_queued') }}</option>
                @if($activeTab === 'history')
                    <option value="{{ $filterUrl(['recording_status' => 'completed']) }}" {{ $statusFilter === 'completed' ? 'selected' : '' }}>{{ __($t.'status_completed') }}</option>
                    <option value="{{ $filterUrl(['recording_status' => 'skipped']) }}" {{ $statusFilter === 'skipped' ? 'selected' : '' }}>{{ __($t.'status_skipped') }}</option>
                    <option value="{{ $filterUrl(['recording_status' => 'failed']) }}" {{ $statusFilter === 'failed' ? 'selected' : '' }}>{{ __($t.'status_failed') }}</option>
                @else
                    <option value="{{ $filterUrl(['recording_status' => 'manual']) }}" {{ $statusFilter === 'manual' ? 'selected' : '' }}>{{ __($t.'status_manual') }}</option>
                    <option value="{{ $filterUrl(['recording_status' => 'none']) }}" {{ $statusFilter === 'none' ? 'selected' : '' }}>{{ __($t.'status_none') }}</option>
                @endif
            </select>

            {{-- Teacher --}}
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

            {{-- Student --}}
            @if(!empty($students))
            <x-ui.searchable-select
                name="student_id"
                :options="$students"
                :selected="$studentId"
                :placeholder="__('supervisor.sessions.all_students')"
                :showGenderFilter="true"
                :maleLabel="__('supervisor.recording.student_male')"
                :femaleLabel="__('supervisor.recording.student_female')"
                :showTypeFilter="false"
            />
            @endif

            {{-- Search --}}
            <div class="relative">
                <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <form method="GET" action="{{ route('manage.recording.index', ['subdomain' => $subdomain]) }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="session_tab" value="{{ $sessionTab }}">
                    @if($statusFilter)<input type="hidden" name="recording_status" value="{{ $statusFilter }}">@endif
                    @if($teacherId)<input type="hidden" name="teacher_id" value="{{ $teacherId }}">@endif
                    @if($studentId)<input type="hidden" name="student_id" value="{{ $studentId }}">@endif
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="{{ __($t.'filter_search') }}"
                        class="w-full ps-9 pe-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-indigo-500 focus:ring-indigo-500"
                        onkeydown="if(event.key==='Enter') this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="p-0">
    @if($activeTab === 'live')
        @if($sessions->isEmpty())
            <div class="p-12 text-center">
                <i class="ri-live-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __($t.'no_live_sessions') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __($t.'no_live_sessions_description') }}</p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'type') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'teacher') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_student') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'time') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_duration') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_status') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'status') }}</th>
                            <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($sessions as $session)
                            @include('supervisor.recording._recording-session-row', ['session' => $session, 'subdomain' => $subdomain])
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Mobile Cards --}}
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($sessions as $session)
                    @include('supervisor.recording._recording-session-card', ['session' => $session, 'subdomain' => $subdomain])
                @endforeach
            </div>
        @endif

    @else
        {{-- History Tab --}}
        @if($historyData)
            {{-- Stats (same card design as capacity dashboard) --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center"><i class="ri-checkbox-circle-line text-lg text-green-600"></i></div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($historyData['stats']->total_recorded) }}</p>
                                <p class="text-xs text-gray-500">{{ __($t.'history_total_recorded') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center"><i class="ri-skip-forward-line text-lg text-gray-600"></i></div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($historyData['stats']->total_skipped) }}</p>
                                <p class="text-xs text-gray-500">{{ __($t.'history_total_skipped') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center"><i class="ri-error-warning-line text-lg text-red-600"></i></div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($historyData['stats']->total_failed) }}</p>
                                <p class="text-xs text-gray-500">{{ __($t.'history_total_failed') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center"><i class="ri-time-line text-lg text-blue-600"></i></div>
                            <div>
                                @php $dur = $historyData['stats']->total_duration; $h = intval($dur/3600); $m = intval(($dur%3600)/60); @endphp
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $h > 0 ? $h.'h '.$m.'m' : $m.'m' }}</p>
                                <p class="text-xs text-gray-500">{{ __($t.'history_total_duration') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center"><i class="ri-hard-drive-2-line text-lg text-purple-600"></i></div>
                            <div>
                                @php
                                    $bytes = $historyData['stats']->total_storage; $units = ['B','KB','MB','GB'];
                                    $i = 0; $val = $bytes; while ($val > 1024 && $i < 3) { $val /= 1024; $i++; }
                                @endphp
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ round($val, 1) }} {{ $units[$i] }}</p>
                                <p class="text-xs text-gray-500">{{ __($t.'history_storage_used') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- History Table with Bulk Selection --}}
            @if($historyData['recordings']->isEmpty())
                <div class="p-12 text-center">
                    <i class="ri-history-line text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">{{ __($t.'no_history') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __($t.'no_history_description') }}</p>
                </div>
            @else
                @php
                    // Build playlist for audio player navigation
                    $playableRecordings = $historyData['recordings']->filter(fn ($r) => $r->isCompleted() && $r->isAvailable());
                    $playlist = $playableRecordings->values()->map(fn ($r) => [
                        'id' => $r->id,
                        'streamUrl' => $r->getDirectUrl() ?? $r->getStreamUrl(),
                        'downloadUrl' => $r->getDownloadUrl(),
                        'date' => $r->started_at ? toAcademyTimezone($r->started_at)->translatedFormat('d M h:i A') : '',
                        'duration' => $r->formatted_duration,
                        'durationSeconds' => $r->duration,
                        'size' => $r->formatted_file_size,
                        'title' => $r->recordable?->session_code ?? $r->display_name,
                    ])->toArray();
                    $playlistJs = \Illuminate\Support\Js::from($playlist);
                @endphp

                <div x-data="bulkRecordings({{ $playlistJs }})" class="relative">
                    {{-- Bulk action bar --}}
                    <div x-show="selected.length > 0" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="sticky top-0 z-10 bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 px-4 py-2.5 flex items-center justify-between">
                        <span class="text-sm font-medium" x-text="'{{ __($t.'bulk_selected') }}: ' + selected.length"></span>
                        <div class="flex items-center gap-2">
                            <button @click="bulkDownload()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                <i class="ri-download-line"></i> {{ __($t.'bulk_download') }}
                            </button>
                            <button @click="bulkDeleteConfirm()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">
                                <i class="ri-delete-bin-line"></i> {{ __($t.'bulk_delete') }}
                            </button>
                            <button @click="selected = []" class="p-1 hover:bg-indigo-200 dark:hover:bg-indigo-800 rounded transition-colors">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10"><input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'type') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'teacher') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_student') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_scheduled') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_status') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __('supervisor.sessions.col_duration') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'status') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'history_storage_used') }}</th>
                                    <th class="px-4 py-3 text-start text-gray-600 dark:text-gray-400 font-medium">{{ __($t.'actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($historyData['recordings'] as $recording)
                                    @php
                                        $rec = $recording;
                                        $sess = $rec->recordable;
                                        $recType = match(true) {
                                            $sess instanceof \App\Models\AcademicSession => 'academic',
                                            $sess instanceof \App\Models\InteractiveCourseSession => 'interactive',
                                            default => 'quran',
                                        };
                                        $recTeacher = match($recType) {
                                            'academic' => $sess?->academicTeacher?->user?->name ?? '-',
                                            'interactive' => $sess?->course?->assignedTeacher?->user?->name ?? '-',
                                            default => $sess?->quranTeacher?->name ?? '-',
                                        };
                                        $recStudent = match($recType) {
                                            'academic' => $sess?->student?->name ?? '-',
                                            'interactive' => $sess?->course?->title ?? '-',
                                            default => $sess?->circle?->name ?? $sess?->student?->name ?? $sess?->trialRequest?->student?->name ?? '-',
                                        };
                                        $isTrial = $recType === 'quran' && $sess && method_exists($sess, 'isTrial') && $sess->isTrial();
                                        $typeConfig = match(true) {
                                            $recType === 'academic' => ['label' => __('supervisor.sessions.type_private_lesson'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
                                            $recType === 'interactive' => ['label' => __('supervisor.sessions.type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
                                            $isTrial => ['label' => __('supervisor.sessions.type_quran_trial'), 'icon' => 'ri-gift-line', 'bg' => 'bg-orange-50', 'text' => 'text-orange-600'],
                                            $sess && $sess->circle => ['label' => __('supervisor.sessions.type_quran_group'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
                                            default => ['label' => __('supervisor.sessions.type_quran_individual'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
                                        };
                                        $sBadge = match($rec->status) {
                                            \App\Enums\RecordingStatus::COMPLETED => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            \App\Enums\RecordingStatus::SKIPPED => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            \App\Enums\RecordingStatus::FAILED => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                        $playlistIndex = $playableRecordings->search(fn ($r) => $r->id === $rec->id);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3"><input type="checkbox" value="{{ $rec->id }}" x-model="selected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        {{-- Type --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-6 h-6 rounded flex items-center justify-center {{ $typeConfig['bg'] }}">
                                                    <i class="{{ $typeConfig['icon'] }} text-xs {{ $typeConfig['text'] }}"></i>
                                                </span>
                                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $typeConfig['label'] }}</span>
                                            </div>
                                        </td>
                                        {{-- Teacher --}}
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">{{ $recTeacher }}</td>
                                        {{-- Student --}}
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">{{ $recStudent }}</td>
                                        {{-- Scheduled At (matching sessions page) --}}
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($sess?->scheduled_at)
                                                <span class="text-sm text-gray-700">{{ toAcademyTimezone($sess->scheduled_at)->translatedFormat('d M') }}</span>
                                                <span class="text-xs text-gray-500 block">{{ toAcademyTimezone($sess->scheduled_at)->translatedFormat('h:i A') }}</span>
                                            @else
                                                <span class="text-sm text-gray-400">-</span>
                                            @endif
                                        </td>
                                        {{-- Session Status --}}
                                        <td class="px-4 py-3">
                                            @if($sess?->status)
                                                <x-sessions.status-badge :status="$sess->status" size="sm" />
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                        {{-- Duration --}}
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                                            {{ $sess?->duration_minutes ? $sess->duration_minutes . ' ' . __('supervisor.sessions.minutes_short') : $rec->formatted_duration }}
                                        </td>
                                        {{-- Recording Status --}}
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sBadge }}">
                                                <i class="{{ $rec->status->icon() }} me-1"></i>
                                                {{ $rec->status->label() }}
                                            </span>
                                        </td>
                                        {{-- Size --}}
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm" dir="ltr">{{ $rec->formatted_file_size }}</td>
                                        {{-- Actions --}}
                                        <td class="px-4 py-3">
                                            @if($rec->isCompleted() && $rec->isAvailable())
                                                <div class="flex items-center gap-1">
                                                    <button @click="$dispatch('open-audio-player-playlist', { playlist: playlist, startIndex: {{ $playlistIndex !== false ? $playlistIndex : 0 }} })"
                                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                                        <i class="ri-play-circle-line"></i> {{ __($t.'play') }}
                                                    </button>
                                                    <a href="{{ $rec->getDownloadUrl() }}"
                                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition-colors">
                                                        <i class="ri-download-line"></i> {{ __($t.'download') }}
                                                    </a>
                                                    <button @click="deleteRecording({{ $rec->id }})"
                                                       class="inline-flex items-center px-1.5 py-1 text-xs rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            @elseif($rec->status->canDelete())
                                                <button @click="deleteRecording({{ $rec->id }}, '{{ $sess?->session_code ?? '' }}')"
                                                   class="inline-flex items-center px-1.5 py-1 text-xs rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Bulk delete form (hidden) --}}
                    <form x-ref="bulkDeleteForm" method="POST" action="{{ route('manage.recording.bulk-delete', ['subdomain' => $subdomain]) }}">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="recording_ids[]" :value="id">
                        </template>
                    </form>
                </div>

                <div class="p-4">
                    {{ $historyData['recordings']->links() }}
                </div>
            @endif
        @endif
    @endif
    </div>
</div>

{{-- Audio Player Modal (confirmation modal already in supervisor layout) --}}
<x-recordings.audio-player-modal />

{{-- Delete form (single) --}}
<form id="deleteRecordingForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
function bulkRecordings(playlistData) {
    return {
        selected: [],
        playlist: playlistData || [],

        toggleAll(event) {
            if (event.target.checked) {
                this.selected = Array.from(document.querySelectorAll('tbody input[type="checkbox"]')).map(cb => cb.value).filter(v => v);
            } else {
                this.selected = [];
            }
        },

        bulkDownload() {
            const links = [];
            document.querySelectorAll('tbody tr').forEach(row => {
                const cb = row.querySelector('input[type="checkbox"]');
                if (cb && this.selected.includes(cb.value)) {
                    const dlLink = row.querySelector('a[href*="download"]');
                    if (dlLink) links.push(dlLink.href);
                }
            });
            // Sequential download via hidden <a> clicks to avoid popup blocker
            links.forEach((href, i) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = href;
                    a.download = '';
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }, i * 500);
            });
        },

        bulkDeleteConfirm() {
            if (typeof window.confirmAction === 'function') {
                window.confirmAction({
                    title: '{{ __($t."bulk_delete_title") }}',
                    message: '{{ __($t."bulk_delete_confirm") }}',
                    confirmText: '{{ __($t."bulk_delete") }}',
                    cancelText: '{{ __("common.cancel") }}',
                    isDangerous: true,
                    icon: 'ri-delete-bin-line',
                    onConfirm: () => { this.$refs.bulkDeleteForm.submit(); }
                });
            } else {
                if (confirm('{{ __($t."bulk_delete_confirm") }}')) {
                    this.$refs.bulkDeleteForm.submit();
                }
            }
        },

        deleteRecording(id) {
            const doDelete = () => {
                const form = document.getElementById('deleteRecordingForm');
                form.action = '{{ route("manage.recording.delete", ["subdomain" => $subdomain, "recordingId" => "__ID__"]) }}'.replace('__ID__', id);
                form.submit();
            };
            if (typeof window.confirmAction === 'function') {
                window.confirmAction({
                    title: '{{ __($t."delete_title") }}',
                    message: '{{ __($t."delete_confirm") }}',
                    confirmText: '{{ __($t."delete_action") }}',
                    cancelText: '{{ __("common.cancel") }}',
                    isDangerous: true,
                    icon: 'ri-delete-bin-line',
                    onConfirm: doDelete
                });
            } else {
                if (confirm('{{ __($t."delete_confirm") }}')) doDelete();
            }
        }
    };
}

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
            } catch (e) {}
        }
    };
}
</script>

</x-layouts.supervisor>
