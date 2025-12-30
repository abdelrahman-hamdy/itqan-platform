@props([
    'data', // AttendanceDTO or array
    'title' => null,
    'showDetails' => true,
])

@php
    $displayTitle = $title ?? __('components.reports.attendance_summary.title');
@endphp

@php
// Support both DTO and array
$totalSessions = is_object($data) ? $data->totalSessions : ($data['total_sessions'] ?? 0);
$attended = is_object($data) ? $data->attended : ($data['attended'] ?? 0);
$absent = is_object($data) ? $data->absent : ($data['absent'] ?? 0);
$late = is_object($data) ? $data->late : ($data['late'] ?? 0);
$attendanceRate = is_object($data) ? $data->attendanceRate : ($data['attendance_rate'] ?? 0);
$colorClass = is_object($data) && method_exists($data, 'getColorClass')
    ? $data->getColorClass()
    : (match(true) {
        $attendanceRate >= 80 => 'green',
        $attendanceRate >= 60 => 'yellow',
        default => 'red'
    });
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
        <i class="ri-calendar-check-line text-{{ $colorClass }}-600 ms-2 rtl:ms-2 ltr:me-2"></i>
        {{ $displayTitle }}
    </h2>

    <div class="flex flex-col lg:flex-row items-center gap-8">
        <!-- Circular Progress -->
        <div class="flex-shrink-0">
            <x-ui.circular-progress
                :value="$attendanceRate"
                :color="$colorClass"
                size="lg"
                :label="__('components.reports.attendance_summary.attendance_rate')"
            />
        </div>

        @if($showDetails)
        <!-- Breakdown Stats -->
        <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 w-full">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-900">{{ $totalSessions }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ __('components.reports.attendance_summary.total_sessions') }}</p>
            </div>

            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $attended }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ __('components.reports.attendance_summary.attended') }}</p>
            </div>

            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">{{ $late }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ __('components.reports.attendance_summary.late') }}</p>
            </div>

            <div class="text-center p-4 bg-red-50 rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ $absent }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ __('components.reports.attendance_summary.absent') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
