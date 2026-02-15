<div
    x-data="tabsComponent(@js([
        'id' => $id,
        'defaultTab' => $defaultTab,
        'persistState' => $persistState,
        'urlSync' => $urlSync,
        'lazy' => $lazy,
        'animated' => $animated,
    ]))"
    {{ $attributes->merge(['class' => 'tabs-wrapper']) }}
    data-tabs-id="{{ $id }}"
>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 {{ $sticky ? 'sticky top-' . $stickyOffset . ' z-10' : '' }}">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 px-6">
            <nav
                role="tablist"
                aria-label="{{ __('components.tabs.aria_label') }}"
                class="-mb-px flex gap-8 overflow-x-auto scrollbar-hide {{ $fullWidth ? 'justify-between' : '' }}"
            >
                {{ $tabs ?? '' }}
            </nav>
        </div>

        <!-- Tab Panels -->
        <div class="tab-panels">
            {{ $panels ?? $slot }}
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
// Alpine.js tabs component will be loaded from resources/js/components/tabs.js
</script>
@endpush
@endonce
