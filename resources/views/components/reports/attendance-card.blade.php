@props([
    'attendance', // Attendance statistics array
    'title' => 'إحصائيات الحضور',
    'showDetails' => true
])

@php
    $totalSessions = $attendance['total_sessions'] ?? 0;
    $attended = $attendance['attended'] ?? 0;
    $absent = $attendance['absent'] ?? 0;
    $late = $attendance['late'] ?? 0;
    $attendanceRate = $attendance['attendance_rate'] ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    <!-- Attendance Rate Circle -->
    <div class="flex items-center justify-center mb-6">
        <div class="relative inline-flex items-center justify-center">
            <!-- SVG Circle Progress -->
            <svg class="w-32 h-32" viewBox="0 0 120 120">
                <!-- Background circle -->
                <circle
                    cx="60"
                    cy="60"
                    r="54"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-width="8"
                ></circle>
                <!-- Progress circle -->
                <circle
                    cx="60"
                    cy="60"
                    r="54"
                    fill="none"
                    stroke="{{ $attendanceRate >= 80 ? '#10b981' : ($attendanceRate >= 60 ? '#f59e0b' : '#ef4444') }}"
                    stroke-width="8"
                    stroke-dasharray="{{ 2 * 3.14159 * 54 }}"
                    stroke-dashoffset="{{ 2 * 3.14159 * 54 * (1 - $attendanceRate / 100) }}"
                    stroke-linecap="round"
                    transform="rotate(-90 60 60)"
                    class="transition-all duration-500"
                ></circle>
            </svg>
            <!-- Center text -->
            <div class="absolute inset-0 flex items-center justify-center flex-col">
                <span class="text-3xl font-bold {{ $attendanceRate >= 80 ? 'text-green-600' : ($attendanceRate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $attendanceRate }}%
                </span>
                <span class="text-xs text-gray-600 mt-1">نسبة الحضور</span>
            </div>
        </div>
    </div>

    @if($showDetails)
        <!-- Statistics Grid -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <!-- Attended -->
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $attended }}</div>
                <div class="text-xs text-green-700 font-medium">حضر</div>
            </div>

            <!-- Absent -->
            <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
                <div class="text-2xl font-bold text-red-600">{{ $absent }}</div>
                <div class="text-xs text-red-700 font-medium">غاب</div>
            </div>

            <!-- Late -->
            <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="text-2xl font-bold text-yellow-600">{{ $late }}</div>
                <div class="text-xs text-yellow-700 font-medium">متأخر</div>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-calendar-line text-blue-600 ml-2"></i>
                <span class="text-sm text-blue-700">إجمالي الجلسات</span>
            </div>
            <span class="text-sm font-bold text-blue-900">{{ $totalSessions }}</span>
        </div>
    @endif
</div>
