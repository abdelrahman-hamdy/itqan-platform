@props([
    'subscription',
    'viewType' => 'teacher'
])

@php
use App\Enums\AttendanceStatus;

    $isTeacher = $viewType === 'teacher';
    $student = $subscription->student;
    $attendanceRate = $subscription->attendance_rate ?? 85;
    $attendanceColor = $attendanceRate >= 80 ? 'green' : ($attendanceRate >= 60 ? 'yellow' : 'red');
@endphp

<!-- Academic Attendance Overview Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ri-user-check-line text-{{ $attendanceColor }}-600 ms-2"></i>
            {{ __('components.academic.attendance.overview_title') }}
        </h3>
    </div>

    <!-- Attendance Stats -->
    <div class="grid grid-cols-1 gap-4 mb-6">
        <!-- Overall Attendance Rate -->
        <div class="bg-{{ $attendanceColor }}-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-{{ $attendanceColor }}-800">{{ __('components.academic.attendance.overall_rate') }}</p>
                    <p class="text-3xl font-bold text-{{ $attendanceColor }}-600">{{ number_format($attendanceRate, 1) }}%</p>
                </div>
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="#e5e7eb" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="currentColor" stroke-width="3"
                              stroke-dasharray="{{ $attendanceRate }}, 100"
                              class="text-{{ $attendanceColor }}-500"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Attendance Breakdown -->
        <div class="grid grid-cols-3 gap-2">
            <div class="bg-green-50 rounded-lg p-3 text-center">
                <p class="text-lg font-bold text-green-600">{{ $subscription->sessions_attended ?? 12 }}</p>
                <p class="text-xs text-green-800">{{ __('components.academic.attendance.attended') }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                <p class="text-lg font-bold text-yellow-600">{{ $subscription->sessions_partial ?? 2 }}</p>
                <p class="text-xs text-yellow-800">{{ __('components.academic.attendance.partial') }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-3 text-center">
                <p class="text-lg font-bold text-red-600">{{ $subscription->sessions_absent ?? 1 }}</p>
                <p class="text-xs text-red-800">{{ __('components.academic.attendance.absent') }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="space-y-3">
        <h4 class="text-sm font-semibold text-gray-700 flex items-center">
            <i class="ri-calendar-check-line text-gray-500 ms-1"></i>
            {{ __('components.academic.attendance.recent_records') }}
        </h4>

        @php
        $recentAttendance = [
            [
                'date' => '2024-01-15',
                'status' => AttendanceStatus::ATTENDED->value,
                'duration' => 60,
                'notes' => __('components.academic.attendance.sample_notes.full_attendance')
            ],
            [
                'date' => '2024-01-13',
                'status' => AttendanceStatus::LEFT->value,
                'duration' => 45,
                'notes' => __('components.academic.attendance.sample_notes.joined_late')
            ],
            [
                'date' => '2024-01-10',
                'status' => AttendanceStatus::ATTENDED->value,
                'duration' => 60,
                'notes' => __('components.academic.attendance.sample_notes.excellent')
            ],
            [
                'date' => '2024-01-08',
                'status' => AttendanceStatus::ABSENT->value,
                'duration' => 0,
                'notes' => __('components.academic.attendance.sample_notes.emergency')
            ]
        ];
        @endphp

        @foreach($recentAttendance as $record)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3 space-x-reverse">
                <!-- Status Icon -->
                <div class="flex-shrink-0">
                    @if($record['status'] === AttendanceStatus::ATTENDED->value)
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    @elseif($record['status'] === AttendanceStatus::LEFT->value)
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    @else
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    @endif
                </div>
                
                <!-- Date and Duration -->
                <div class="flex-1">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <span class="text-sm font-medium text-gray-900">{{ $record['date'] }}</span>
                        <span class="text-xs text-gray-500">
                            {{ $record['duration'] }} {{ __('components.academic.attendance.minutes') }}
                        </span>
                    </div>
                    @if($record['notes'])
                    <p class="text-xs text-gray-600 mt-1">{{ $record['notes'] }}</p>
                    @endif
                </div>
            </div>

            <!-- Status Badge -->
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                {{ $record['status'] === AttendanceStatus::ATTENDED->value ? 'bg-green-100 text-green-800' :
                   ($record['status'] === AttendanceStatus::LEFT->value ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ $record['status'] === AttendanceStatus::ATTENDED->value ? __('components.academic.attendance.status_attended') :
                   ($record['status'] === AttendanceStatus::LEFT->value ? __('components.academic.attendance.status_left_early') : __('components.academic.attendance.status_absent')) }}
            </span>
        </div>
        @endforeach
    </div>

    <!-- Attendance Trends (for teacher view) -->
    @if($isTeacher)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
            <i class="ri-line-chart-line text-gray-500 ms-1"></i>
            {{ __('components.academic.attendance.trends_title') }}
        </h4>

        <div class="space-y-2">
            <!-- This Week -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">{{ __('components.academic.attendance.this_week') }}</span>
                <div class="flex items-center">
                    <div class="w-20 bg-gray-200 rounded-full h-2 ms-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">100%</span>
                </div>
            </div>

            <!-- Last Week -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">{{ __('components.academic.attendance.last_week') }}</span>
                <div class="flex items-center">
                    <div class="w-20 bg-gray-200 rounded-full h-2 ms-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">75%</span>
                </div>
            </div>

            <!-- This Month -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">{{ __('components.academic.attendance.this_month') }}</span>
                <div class="flex items-center">
                    <div class="w-20 bg-gray-200 rounded-full h-2 ms-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $attendanceRate }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">{{ number_format($attendanceRate, 0) }}%</span>
                </div>
            </div>
        </div>

        <!-- Attendance Action Buttons -->
        <div class="mt-4 pt-3 border-t border-gray-100" x-data>
            <div class="flex items-center space-x-2 space-x-reverse">
                <button @click="window.location.href = '/teacher/academic/lessons/{{ $subscription->id }}/attendance'"
                        class="flex-1 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-lg transition-colors">
                    <i class="ri-bar-chart-line ms-1"></i>
                    {{ __('components.academic.attendance.detailed_report') }}
                </button>
                <button @click="window.toast?.info('{{ __('components.academic.attendance.export_coming_soon') }}')"
                        class="flex-1 px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                    <i class="ri-download-line ms-1"></i>
                    {{ __('components.academic.attendance.export') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Attendance Goal (for student view) -->
    @if(!$isTeacher)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-target-line text-blue-600 ms-2"></i>
                <div>
                    <div class="text-sm font-medium text-blue-800">{{ __('components.academic.attendance.monthly_goal') }}</div>
                    <div class="text-xs text-blue-600">{{ __('components.academic.attendance.maintain_rate') }}</div>
                </div>
            </div>
            <div class="text-end">
                <div class="text-sm font-bold text-blue-600">{{ number_format($attendanceRate, 1) }}%</div>
                <div class="text-xs text-blue-500">
                    {{ $attendanceRate >= 90 ? __('components.academic.attendance.goal_achieved') : __('components.academic.attendance.needs_improvement') }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

