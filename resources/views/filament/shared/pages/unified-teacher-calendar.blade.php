<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Session Statistics Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($this->getSessionStatistics() as $stat)
                <div>
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $stat['title'] }}
                                </h3>
                                <div class="mt-1">
                                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stat['value'] }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900">
                                    <x-dynamic-component
                                        :component="$stat['icon']"
                                        class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"
                                    />
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            @endforeach
        </div>

        {{-- Timezone Information --}}
        <div class="rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950 dark:to-primary-900 px-6 py-7 shadow-md border-2 border-primary-200 dark:border-primary-800" wire:poll.60s>
            <div class="flex items-center justify-center gap-6 text-center">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-globe-alt class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                    <span class="text-lg font-bold text-primary-900 dark:text-primary-100">{{ $this->getTimezoneNotice() }}</span>
                </div>
                <div class="h-8 w-px bg-primary-300 dark:bg-primary-700"></div>
                <div class="flex items-center gap-3">
                    <x-heroicon-o-clock class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                    <span class="text-lg font-bold text-primary-900 dark:text-primary-100">{{ $this->getCurrentTimeDisplay() }}</span>
                </div>
            </div>
        </div>

        {{-- Session Management Section --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ $this->getSectionHeading() }}
            </x-slot>

            <x-slot name="description">
                {{ $this->getSectionDescription() }}
            </x-slot>

            {{-- Dynamic Tabs from Strategy --}}
            <x-filament::tabs :label="$this->getTabsLabel()">
                @foreach($this->getTabConfiguration() as $tabKey => $tabConfig)
                    <x-filament::tabs.item
                        :active="$activeTab === $tabKey"
                        wire:click="setActiveTab('{{ $tabKey }}')"
                        :icon="$tabConfig['icon']"
                    >
                        {{ $tabConfig['label'] }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            {{-- Dynamic Tab Content --}}
            <div class="mt-6">
                @include('filament.shared.partials.schedulable-items-grid', [
                    'items' => $this->schedulableItems,
                    'selectedItemId' => $selectedItemId,
                    'selectedItemType' => $selectedItemType,
                ])

                {{-- Schedule Action Button --}}
                @if($selectedItemId)
                    <div class="mt-6 flex justify-center">
                        {{ $this->scheduleAction }}
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>

    {{-- Shared Calendar Styles --}}
    @include('filament.shared.partials.calendar-styles')

    {{-- Shared Item Selection JavaScript --}}
    @include('filament.shared.partials.calendar-item-selection')
</x-filament-panels::page>
