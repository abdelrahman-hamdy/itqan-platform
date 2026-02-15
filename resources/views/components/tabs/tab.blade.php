@props([
    'id',
    'label',
    'icon' => null,
    'badge' => null,
    'badgeColor' => 'gray',
    'disabled' => false,
])

<button
    type="button"
    role="tab"
    x-on:click="switchTab('{{ $id }}')"
    :aria-selected="activeTab === '{{ $id }}'"
    :aria-controls="'panel-{{ $id }}'"
    :tabindex="activeTab === '{{ $id }}' ? 0 : -1"
    :class="{
        'border-primary text-primary': activeTab === '{{ $id }}',
        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== '{{ $id }}',
    }"
    {{ $disabled ? 'disabled' : '' }}
    class="tab-button border-b-2 text-sm py-4 px-1 font-medium transition-all duration-200 focus:outline-none border-transparent whitespace-nowrap"
    data-tab="{{ $id }}"
>
    <span class="flex items-center gap-2">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @endif

        <span>{{ $label }}</span>

        @if($badge !== null)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-800">
                {{ $badge }}
            </span>
        @endif
    </span>
</button>
