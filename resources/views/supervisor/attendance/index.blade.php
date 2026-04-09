<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $baseUrl = route('manage.attendance.index', ['subdomain' => $subdomain]);
@endphp

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ __('supervisor.attendance.title') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('supervisor.attendance.subtitle') }}</p>
        </div>
    </div>

    <!-- Tabs: Teachers / Students (centered) -->
    <div class="flex justify-center mb-6">
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
            <a href="{{ $baseUrl }}?tab=teachers"
               class="px-5 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'teachers' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                <i class="ri-user-star-line me-1"></i>{{ __('supervisor.attendance.tab_teachers') }}
            </a>
            <a href="{{ $baseUrl }}?tab=students"
               class="px-5 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'students' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                <i class="ri-graduation-cap-line me-1"></i>{{ __('supervisor.attendance.tab_students') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-pie-chart-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['attendance_rate'] }}%</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.attendance_rate') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['counted'] }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $activeTab === 'teachers' ? __('supervisor.attendance.counted_earnings') : __('supervisor.attendance.counted_subscription') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="ri-close-circle-line text-red-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['not_counted'] }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.not_counted') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="ri-list-check text-gray-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.total_records') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ $baseUrl }}" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- Teacher searchable-select (both tabs) --}}
            <div class="flex-1 min-w-[200px]">
                <x-ui.searchable-select
                    name="teacher_id"
                    :options="$teacherOptions"
                    :selected="request('teacher_id')"
                    :placeholder="__('supervisor.attendance.select_teacher')"
                    :label="__('supervisor.attendance.teacher')"
                    :showGenderFilter="true"
                    :showTypeFilter="true"
                />
            </div>

            @if($activeTab === 'students')
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.search_name') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('supervisor.attendance.search_placeholder') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            @endif

            <div class="flex-1 min-w-[130px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.session_type') }}</label>
                <select name="session_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    <option value="quran" {{ request('session_type') === 'quran' ? 'selected' : '' }}>{{ __('supervisor.attendance.quran') }}</option>
                    <option value="academic" {{ request('session_type') === 'academic' ? 'selected' : '' }}>{{ __('supervisor.attendance.academic') }}</option>
                    <option value="interactive" {{ request('session_type') === 'interactive' ? 'selected' : '' }}>{{ __('supervisor.attendance.interactive') }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.date_from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.date_to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.common.status') }}</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    <option value="attended" {{ request('status') === 'attended' ? 'selected' : '' }}>{{ __('supervisor.attendance.present') }}</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>{{ __('supervisor.attendance.absent') }}</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>{{ __('supervisor.attendance.late') }}</option>
                    <option value="partially_attended" {{ request('status') === 'partially_attended' ? 'selected' : '' }}>{{ __('supervisor.attendance.partially_attended') }}</option>
                    <option value="left" {{ request('status') === 'left' ? 'selected' : '' }}>{{ __('supervisor.attendance.left_early') }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.counted_filter') }}</label>
                <select name="counted" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    <option value="yes" {{ request('counted') === 'yes' ? 'selected' : '' }}>{{ __('supervisor.attendance.counted') }}</option>
                    <option value="no" {{ request('counted') === 'no' ? 'selected' : '' }}>{{ __('supervisor.attendance.not_counted') }}</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="ri-search-line"></i> {{ __('supervisor.common.search') }}
                </button>
                <a href="{{ $baseUrl }}?tab={{ $activeTab }}"
                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    <i class="ri-refresh-line"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($records->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.student_name') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.session_type') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.duration') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.percentage') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.common.status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.counted_filter') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($records as $record)
                            @php
                                $statusValue = $record->attendance_status;
                                if (is_object($statusValue)) {
                                    $statusValue = $statusValue->value;
                                }

                                if (($record->total_duration_minutes ?? 0) == 0 && $statusValue === 'attended') {
                                    $statusValue = 'absent';
                                }

                                $sessionTypeBadge = match($record->session_type) {
                                    'individual' => ['bg-yellow-100 text-yellow-800', __('supervisor.attendance.quran_individual')],
                                    'group' => ['bg-emerald-100 text-emerald-800', __('supervisor.attendance.quran_group')],
                                    'trial' => ['bg-orange-100 text-orange-800', __('supervisor.attendance.quran_trial')],
                                    'academic' => ['bg-violet-100 text-violet-800', __('supervisor.attendance.academic')],
                                    'interactive' => ['bg-blue-100 text-blue-800', __('supervisor.attendance.interactive')],
                                    default => ['bg-gray-100 text-gray-800', $record->session_type ?? '-'],
                                };

                                $statusBadge = match($statusValue) {
                                    'attended' => ['bg-green-100 text-green-700', __('supervisor.attendance.present')],
                                    'absent' => ['bg-red-100 text-red-700', __('supervisor.attendance.absent')],
                                    'late' => ['bg-amber-100 text-amber-700', __('supervisor.attendance.late')],
                                    'left' => ['bg-orange-100 text-orange-700', __('supervisor.attendance.left_early')],
                                    'partially_attended' => ['bg-cyan-100 text-cyan-700', __('supervisor.attendance.partially_attended')],
                                    default => ['bg-gray-100 text-gray-700', $statusValue ?? '-'],
                                };

                                $routeType = match($record->session_type) {
                                    'individual', 'group', 'trial' => 'quran',
                                    'academic' => 'academic',
                                    'interactive' => 'interactive',
                                    default => 'quran',
                                };
                                $sessionUrl = route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $routeType, 'sessionId' => $record->session_id]);

                                $isCounted = (bool) $record->is_counted;
                            @endphp
                            <tr class="{{ $statusValue === 'absent' ? 'bg-red-50/30' : '' }} hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ $sessionUrl }}" class="font-medium text-blue-700 hover:text-blue-900 hover:underline truncate max-w-[180px] block">
                                        {{ $record->user_name ?? '-' }}
                                        <i class="ri-external-link-line text-xs text-gray-400 ms-1"></i>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $sessionTypeBadge[0] }}">{{ $sessionTypeBadge[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @php
                                        $attendanceDuration = $record->total_duration_minutes ?? 0;
                                        $sessionDuration = $record->session_duration_minutes ?? 0;
                                    @endphp
                                    @if($attendanceDuration > 0 || $sessionDuration > 0)
                                        <span class="font-medium">{{ $attendanceDuration }}</span>
                                        <span class="text-gray-400">/</span>
                                        <span>{{ $sessionDuration }}</span>
                                        <span class="text-xs text-gray-500">{{ __('supervisor.attendance.minutes') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if($record->attendance_percentage !== null)
                                        <span class="{{ $record->attendance_percentage >= 80 ? 'text-green-600' : ($record->attendance_percentage >= 50 ? 'text-amber-600' : 'text-red-600') }} font-medium">
                                            {{ round($record->attendance_percentage) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('manage.attendance.toggle-counted', ['subdomain' => $subdomain, 'id' => $record->id]) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="{{ $isCounted ? __('supervisor.attendance.click_to_uncount') : __('supervisor.attendance.click_to_count') }}"
                                                class="text-xs px-2.5 py-1 rounded-full cursor-pointer transition-colors {{ $isCounted ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                            @if($isCounted)
                                                <i class="ri-check-line"></i> {{ __('supervisor.attendance.counted') }}
                                            @else
                                                <i class="ri-close-line"></i> {{ __('supervisor.attendance.not_counted') }}
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-100">
                {{ $records->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-user-follow-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('supervisor.attendance.no_records') }}</h3>
                <p class="text-sm text-gray-500">{{ __('supervisor.attendance.no_records_description') }}</p>
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
