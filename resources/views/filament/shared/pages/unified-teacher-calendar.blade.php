<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Session Statistics Section --}}
        <x-filament::grid default="1" md="2" lg="4" class="gap-4">
            @foreach ($this->getSessionStatistics() as $stat)
                <x-filament::grid.column>
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
                            <div class="flex-shrink-0 ml-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900">
                                    <x-dynamic-component
                                        :component="$stat['icon']"
                                        class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"
                                    />
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                </x-filament::grid.column>
            @endforeach
        </x-filament::grid>

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

    {{-- CSS for item selection --}}
    <style>
        .item-card {
            transition: all 0.3s ease !important;
            position: relative;
        }

        .item-card .fi-card {
            transition: all 0.3s ease !important;
        }

        .item-selected .fi-card {
            border-width: 2px !important;
            border-color: #60a5fa !important; /* blue-400 */
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important;
        }

        .item-card.item-selected .fi-section-content {
            border: solid 1px #60a5fa !important;
            border-radius: 10px;
            background-color: #3d485b24 !important;
        }

        /* Calendar indicators styling */
        .event-passed {
            text-decoration: line-through !important;
        }

        /* Calendar styling */
        .fc-event {
            border-radius: 6px;
            border-width: 1px;
            font-size: 12px;
            padding: 2px 6px;
        }

        .fc-event:hover {
            opacity: 0.8;
            cursor: pointer;
        }

        .fc-daygrid-event {
            margin-bottom: 2px;
        }

        .fc-event-title {
            font-weight: 500;
        }

        /* Arabic RTL support */
        .fc-direction-rtl {
            direction: rtl;
        }

        /* Custom button styling */
        .fc-customButton-button {
            background-color: #6366f1;
            border-color: #6366f1;
            color: white;
        }

        .fc-customButton-button:hover {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
    </style>

    {{-- JavaScript for item selection --}}
    <script>
        function makeItemSelected(itemId, itemType) {

            // Remove all selections first
            document.querySelectorAll('.item-card').forEach(card => {
                card.classList.remove('item-selected');
                const cardElement = card.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.border = '';
                    cardElement.style.backgroundColor = '';
                    cardElement.style.boxShadow = '';
                }
            });

            // Find and select the target item
            const targetCard = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
            if (targetCard) {
                targetCard.classList.add('item-selected');

                // Force styles as backup
                const cardElement = targetCard.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.setProperty('border', '2px solid #60a5fa', 'important');
                    cardElement.style.setProperty('background-color', '#eff6ff', 'important');
                    cardElement.style.setProperty('box-shadow', '0 0 0 3px rgba(96, 165, 250, 0.25)', 'important');
                }

                window.__unifiedCalendarSelection = { id: String(itemId), type: String(itemType) };
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Enhanced click handler for items
            document.addEventListener('click', function(e) {
                const itemCard = e.target.closest('.item-card');

                if (itemCard) {
                    const itemId = itemCard.getAttribute('data-item-id');
                    const itemType = itemCard.getAttribute('data-item-type');

                    if (itemId && itemType) {
                        makeItemSelected(itemId, itemType);
                    }
                }
            });

            // Listen for Livewire updates and reapply selection
            const reapply = () => {
                setTimeout(() => {
                    const selectedCards = document.querySelectorAll('.item-selected');
                    if (selectedCards.length > 0) {
                        selectedCards.forEach(card => {
                            const itemId = card.getAttribute('data-item-id');
                            const itemType = card.getAttribute('data-item-type');
                            if (itemId && itemType) makeItemSelected(itemId, itemType);
                        });
                    } else if (window.__unifiedCalendarSelection && window.__unifiedCalendarSelection.id) {
                        makeItemSelected(window.__unifiedCalendarSelection.id, window.__unifiedCalendarSelection.type);
                    }
                }, 50);
            };

            ['livewire:updated','livewire:load','livewire:message.processed','livewire:navigated'].forEach(evt => {
                document.addEventListener(evt, reapply);
            });
        });
    </script>
</x-filament-panels::page>
