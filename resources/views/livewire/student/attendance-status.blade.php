<div
    @if(in_array($status, ['in_meeting', 'preparation']))
        wire:poll.15s="updateAttendanceStatus"
    @elseif($status === 'completed' && !$showProgress)
        wire:poll.30s="updateAttendanceStatus"
    @endif
    class="attendance-status bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 shadow-sm relative"
    id="attendance-status"
>
    <!-- Header -->
    <div class="flex items-center gap-3 mb-3">
        <div class="attendance-indicator flex items-center gap-2">
            <span class="attendance-dot w-3 h-3 rounded-full {{ $dotColor }} transition-all duration-300"></span>
            <i class="attendance-icon ri-user-line text-lg text-gray-600"></i>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('components.attendance_box.title') }}</h3>
        </div>
    </div>

    <!-- Attendance Details -->
    <div class="attendance-details">
        <div class="attendance-text text-sm text-gray-700 font-medium mb-1">
            {{ $attendanceText }}
        </div>
        <div class="attendance-time text-xs text-gray-500">
            {{ $attendanceTime }}
        </div>
    </div>

    <!-- Progress Bar (shown after session ends) -->
    @if($showProgress)
        <div class="mt-3" id="attendance-progress">
            <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
                <span>{{ __('components.attendance_box.attendance_percentage') }}</span>
                <span class="attendance-percentage font-semibold">{{ $attendancePercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-300 {{ $attendancePercentage >= 80 ? 'bg-green-500' : ($attendancePercentage >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                    style="width: {{ $attendancePercentage }}%"
                    id="attendance-progress-bar"
                ></div>
            </div>
        </div>
    @endif

    <!-- Detailed Times (after session ends) -->
    @if($status === 'completed' && $firstJoin)
        <div class="mt-3 pt-3 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span class="text-gray-500">{{ __('components.attendance_box.enter_time') }}</span>
                    <span class="font-medium text-gray-700">{{ $firstJoin->format('h:i A') }}</span>
                </div>
                @if($lastLeave)
                    <div>
                        <span class="text-gray-500">{{ __('components.attendance_box.leave_time') }}</span>
                        <span class="font-medium text-gray-700">{{ $lastLeave->format('h:i A') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Loading Indicator -->
    <div wire:loading class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">{{ __('components.attendance_box.updating') }}</span>
        </div>
    </div>
</div>
