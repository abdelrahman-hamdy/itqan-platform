{{--
    Calendar Stats Cards Component
    Displays session statistics for the calendar view
--}}

@props([
    'totalId' => 'stat-total',
    'scheduledId' => 'stat-scheduled',
    'completedId' => 'stat-completed',
    'cancelledId' => 'stat-cancelled'
])

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6 md:mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs md:text-sm text-gray-600 mb-1">{{ __('student.calendar.stats.total_sessions') }}</p>
                <p id="{{ $totalId }}" class="text-xl md:text-2xl font-bold text-purple-600">0</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-calendar-line text-xl md:text-2xl text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs md:text-sm text-gray-600 mb-1">{{ __('student.calendar.stats.scheduled_sessions') }}</p>
                <p id="{{ $scheduledId }}" class="text-xl md:text-2xl font-bold text-blue-600">0</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-calendar-check-line text-xl md:text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs md:text-sm text-gray-600 mb-1">{{ __('student.calendar.stats.completed_sessions') }}</p>
                <p id="{{ $completedId }}" class="text-xl md:text-2xl font-bold text-green-600">0</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-checkbox-circle-line text-xl md:text-2xl text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs md:text-sm text-gray-600 mb-1">{{ __('student.calendar.stats.cancelled_sessions') }}</p>
                <p id="{{ $cancelledId }}" class="text-xl md:text-2xl font-bold text-red-600">0</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-close-circle-line text-xl md:text-2xl text-red-600"></i>
            </div>
        </div>
    </div>
</div>
