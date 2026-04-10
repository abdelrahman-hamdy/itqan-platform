{{--
    Calendar Stats Cards Component
    Displays session statistics for the calendar view.
    Accepts an optional :stats prop (from CalendarService::getCalendarStats).
    Falls back to 0 when stats are not provided (backwards compatible).
--}}

@props([
    'stats' => null,
    'totalId' => 'stat-total',
    'scheduledId' => 'stat-scheduled',
    'completedId' => 'stat-completed',
    'cancelledId' => 'stat-cancelled',
])

@php
    $byStatus = $stats['by_status'] ?? [];
    $totalCount = $stats['total_events'] ?? 0;
    $scheduledCount = ($byStatus['scheduled'] ?? 0) + ($byStatus['ready'] ?? 0);
    $completedCount = $byStatus['completed'] ?? 0;
    $cancelledCount = $byStatus['cancelled'] ?? 0;
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-4 md:mb-6 lg:mb-8">
    {{-- Total Sessions --}}
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between gap-2 md:gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('student.calendar.stats.total_sessions') }}</p>
                <p id="{{ $totalId }}" class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $totalCount }}</p>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-calendar-line text-base md:text-lg lg:text-xl text-purple-600"></i>
            </div>
        </div>
    </div>

    {{-- Scheduled Sessions --}}
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between gap-2 md:gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('student.calendar.stats.scheduled_sessions') }}</p>
                <p id="{{ $scheduledId }}" class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $scheduledCount }}</p>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-calendar-check-line text-base md:text-lg lg:text-xl text-blue-600"></i>
            </div>
        </div>
    </div>

    {{-- Completed Sessions --}}
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between gap-2 md:gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('student.calendar.stats.completed_sessions') }}</p>
                <p id="{{ $completedId }}" class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $completedCount }}</p>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-checkbox-circle-line text-base md:text-lg lg:text-xl text-green-600"></i>
            </div>
        </div>
    </div>

    {{-- Cancelled Sessions --}}
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between gap-2 md:gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('student.calendar.stats.cancelled_sessions') }}</p>
                <p id="{{ $cancelledId }}" class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $cancelledCount }}</p>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-close-circle-line text-base md:text-lg lg:text-xl text-red-600"></i>
            </div>
        </div>
    </div>
</div>
