{{-- Timezone and Current Time Info Box --}}
<div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" wire:poll.60s>
    <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10">
            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <div class="flex-1">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ $this->getTimezoneNotice() }}
            </div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                <span class="font-semibold">الوقت الحالي:</span> {{ $this->getCurrentTimeDisplay() }}
            </div>
        </div>
    </div>
</div>

{{-- Include the default FullCalendar view --}}
@include('saade-filament-fullcalendar::fullcalendar')
