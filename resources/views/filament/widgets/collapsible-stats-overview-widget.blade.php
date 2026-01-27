@php
    $columns = $this->getColumns();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview grid gap-y-4">
    <div x-data="{
            open: (window.innerWidth >= 768),
            isMobile: (window.innerWidth < 768),
            handleResize() {
                this.isMobile = (window.innerWidth < 768);
                if (!this.isMobile) this.open = true;
            }
        }"
        x-on:resize.window="handleResize()"
        class="grid gap-y-4"
    >
        {{-- Desktop heading (original Filament style, hidden on mobile) --}}
        @if ($hasHeading || $hasDescription)
            <div class="fi-wi-stats-overview-header grid gap-y-1" x-show="!isMobile">
                @if ($hasHeading)
                    <h3 class="fi-wi-stats-overview-header-heading col-span-full text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ $heading }}
                    </h3>
                @endif

                @if ($hasDescription)
                    <p class="fi-wi-stats-overview-header-description overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Mobile toggle button (hidden on desktop) --}}
        @if ($hasHeading)
            <button type="button" x-on:click="open = !open" x-show="isMobile" x-cloak
                    class="flex items-center justify-between w-full px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</span>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        @endif

        {{-- Stats grid --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
        >
            <div
                @if ($pollingInterval = $this->getPollingInterval())
                    wire:poll.{{ $pollingInterval }}
                @endif
                @class([
                    'fi-wi-stats-overview-stats-ctn grid gap-6',
                    'md:grid-cols-1' => $columns === 1,
                    'md:grid-cols-2' => $columns === 2,
                    'md:grid-cols-3' => $columns === 3,
                    'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
                ])
            >
                @foreach ($this->getCachedStats() as $stat)
                    {{ $stat }}
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
