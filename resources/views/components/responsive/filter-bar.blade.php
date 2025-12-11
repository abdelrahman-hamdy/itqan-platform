@props([
    'collapsible' => true,
    'defaultOpen' => false,
    'title' => 'تصفية النتائج',
    'icon' => 'ri-filter-3-line',
])

<div x-data="{ filtersOpen: {{ $defaultOpen ? 'true' : 'false' }} }" {{ $attributes }}>
    @if($collapsible)
        <!-- Mobile Toggle Button -->
        <button @click="filtersOpen = !filtersOpen"
                class="md:hidden w-full flex items-center justify-between px-4 py-3 min-h-[48px] bg-white border border-gray-200 rounded-xl shadow-sm mb-4 transition-colors hover:bg-gray-50">
            <span class="flex items-center gap-2 text-gray-700 font-medium">
                <i class="{{ $icon }} text-lg"></i>
                {{ $title }}
            </span>
            <i :class="filtersOpen ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-xl text-gray-500"></i>
        </button>
    @endif

    <!-- Filters Content -->
    <div :class="{ 'hidden md:block': !filtersOpen && {{ $collapsible ? 'true' : 'false' }} }"
         class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6 mb-6 transition-all">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {{ $slot }}
        </div>

        @isset($actions)
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
