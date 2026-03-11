<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ __('supervisor.attendance.title') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('supervisor.attendance.subtitle') }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-pie-chart-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $attendanceRate }}%</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.attendance_rate') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $presentCount }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.present') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="ri-close-line text-red-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $absentCount }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.absent') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i class="ri-time-line text-amber-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $lateCount }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.late') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="ri-error-warning-line text-orange-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $chronicAbsentees }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.attendance.chronic_absentees') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('manage.attendance.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.session_type') }}</label>
                <select name="session_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    <option value="quran" {{ request('session_type') === 'quran' ? 'selected' : '' }}>{{ __('supervisor.attendance.quran') }}</option>
                    <option value="academic" {{ request('session_type') === 'academic' ? 'selected' : '' }}>{{ __('supervisor.attendance.academic') }}</option>
                    <option value="interactive" {{ request('session_type') === 'interactive' ? 'selected' : '' }}>{{ __('supervisor.attendance.interactive') }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.student_search') }}</label>
                <input type="text" name="student" value="{{ request('student') }}" placeholder="{{ __('supervisor.attendance.search_placeholder') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.teacher') }}</label>
                <select name="teacher_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.date_from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.attendance.date_to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.common.status') }}</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.common.all') }}</option>
                    <option value="attended" {{ request('status') === 'attended' ? 'selected' : '' }}>{{ __('supervisor.attendance.present') }}</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>{{ __('supervisor.attendance.absent') }}</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>{{ __('supervisor.attendance.late') }}</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="ri-search-line"></i> {{ __('supervisor.common.search') }}
                </button>
                <a href="{{ route('manage.attendance.index', ['subdomain' => $subdomain]) }}"
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
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.student_name') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.teacher') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.session_type') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.session_info') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.common.status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">{{ __('supervisor.attendance.duration') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($records as $record)
                            @php
                                $isChronicAbsentee = false;
                                // Simple highlight check - if status is absent
                                $statusValue = $record['status'];
                                if (is_object($statusValue)) {
                                    $statusValue = $statusValue->value;
                                }
                            @endphp
                            <tr class="{{ $statusValue === 'absent' ? 'bg-red-50/30' : '' }} hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900 whitespace-nowrap">
                                    {{ $record['date']?->format('Y/m/d') }}
                                    <span class="text-xs text-gray-500 block">{{ $record['date']?->format('H:i') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($record['student'])
                                            <x-avatar :user="$record['student']" size="xs" user-type="student" />
                                            <span class="font-medium text-gray-900 truncate max-w-[150px]">{{ $record['student']->name }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 truncate max-w-[150px]">{{ $record['teacher_name'] }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $typeBadge = match($record['session_type']) {
                                            'quran' => ['bg-yellow-100 text-yellow-800', __('supervisor.attendance.quran')],
                                            'academic' => ['bg-violet-100 text-violet-800', __('supervisor.attendance.academic')],
                                            'interactive' => ['bg-blue-100 text-blue-800', __('supervisor.attendance.interactive')],
                                            default => ['bg-gray-100 text-gray-800', $record['session_type']],
                                        };
                                    @endphp
                                    <span class="text-xs px-2 py-1 rounded-full {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 truncate max-w-[150px]">{{ $record['session_info'] }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusBadge = match($statusValue) {
                                            'attended' => ['bg-green-100 text-green-700', __('supervisor.attendance.present')],
                                            'absent' => ['bg-red-100 text-red-700', __('supervisor.attendance.absent')],
                                            'late' => ['bg-amber-100 text-amber-700', __('supervisor.attendance.late')],
                                            'left', 'leaved' => ['bg-orange-100 text-orange-700', __('supervisor.attendance.left_early')],
                                            default => ['bg-gray-100 text-gray-700', $statusValue],
                                        };
                                    @endphp
                                    <span class="text-xs px-2 py-1 rounded-full {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if($record['duration'] > 0)
                                        {{ $record['duration'] }} {{ __('supervisor.attendance.minutes') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
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
