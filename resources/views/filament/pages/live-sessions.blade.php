<x-filament-panels::page>
    <x-filament::tabs>
        @foreach ($this->getTabs() as $tabKey => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $tabKey"
                wire:click="$set('activeTab', '{{ $tabKey }}')"
                :icon="$tab->getIcon()"
                :badge="$tab->getBadge()"
                :badge-color="$tab->getBadgeColor()"
            >
                {{ $tab->getLabel() }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
