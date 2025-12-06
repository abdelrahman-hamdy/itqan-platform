@props([
    'id',
    'lazy' => false,
])

<div
    x-show="activeTab === '{{ $id }}'"
    x-cloak
    role="tabpanel"
    :id="'panel-{{ $id }}'"
    :aria-labelledby="'tab-{{ $id }}'"
    {{ $attributes->merge(['class' => 'tab-content p-8']) }}
    data-panel="{{ $id }}"
>
    @if($lazy)
        <template x-if="loadedTabs.includes('{{ $id }}')">
            <div>{{ $slot }}</div>
        </template>
    @else
        {{ $slot }}
    @endif
</div>
