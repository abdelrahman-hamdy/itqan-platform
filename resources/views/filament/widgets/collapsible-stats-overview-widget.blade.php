@php
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
    $pollingInterval = $this->getPollingInterval();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    <div x-data="{
            open: (window.innerWidth >= 768),
            isMobile: (window.innerWidth < 768),
            handleResize() {
                this.isMobile = (window.innerWidth < 768);
                if (!this.isMobile) this.open = true;
            }
        }"
        x-on:resize.window="handleResize()"
    >
        {{-- Mobile toggle button (hidden on desktop) --}}
        @if ($hasHeading)
            <button type="button" x-on:click="open = !open" x-show="isMobile" x-cloak
                    class="flex items-center justify-between w-full px-4 py-3 mb-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</span>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        @endif

        {{-- Stats content --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
        >
            {{ $this->content }}
        </div>
    </div>
</x-filament-widgets::widget>
