@props([
    'id',
    'lazy' => false,
    'padding' => 'p-8',
])

<div
    x-show="activeTab === '{{ $id }}'"
    x-cloak
    role="tabpanel"
    :id="'panel-{{ $id }}'"
    :aria-labelledby="'tab-{{ $id }}'"
    {{ $attributes->merge(['class' => "tab-content {$padding}"]) }}
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
