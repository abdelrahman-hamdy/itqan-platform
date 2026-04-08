{{-- Single-select with search box and optional filter pills --}}
@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => '',
    'label' => '',
    'showGenderFilter' => false,
    'showTypeFilter' => false,
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <div x-data="{
        open: false,
        search: '',
        selected: {{ json_encode((string) ($selected ?? '')) }},
        genderFilter: '',
        typeFilter: '',
        options: {{ json_encode(collect($options)->map(fn($o) => [
            'id' => (string) $o['id'],
            'name' => $o['name'],
            'gender' => $o['gender'] ?? '',
            'type' => $o['type'] ?? '',
            'type_label' => $o['type_label'] ?? '',
        ])->values()) }},
        get filteredOptions() {
            return this.options.filter(o => {
                if (this.search.trim() && !o.name.toLowerCase().includes(this.search.trim().toLowerCase())) return false;
                if (this.genderFilter && o.gender !== this.genderFilter) return false;
                if (this.typeFilter && o.type !== this.typeFilter) return false;
                return true;
            });
        },
        select(id) {
            this.selected = id;
            this.open = false;
            this.navigate();
        },
        clear() {
            this.selected = '';
            this.navigate();
        },
        get label() {
            if (!this.selected) return {{ json_encode($placeholder) }};
            const found = this.options.find(o => o.id === this.selected);
            return found ? found.name : {{ json_encode($placeholder) }};
        },
        get selectedTypeLabel() {
            if (!this.selected) return '';
            const found = this.options.find(o => o.id === this.selected);
            return found?.type_label || '';
        },
        navigate() {
            const url = new URL(window.location.href);
            if (this.selected) {
                url.searchParams.set({{ json_encode($name) }}, this.selected);
            } else {
                url.searchParams.delete({{ json_encode($name) }});
            }
            window.location.href = url.toString();
        },
        toggleGender(g) { this.genderFilter = this.genderFilter === g ? '' : g; },
        toggleType(t) { this.typeFilter = this.typeFilter === t ? '' : t; }
    }" class="relative" @click.away="open = false" @keydown.escape.window="open = false">

        {{-- Trigger Button --}}
        <button type="button" @click="open = !open"
            class="cursor-pointer min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-start flex items-center gap-2">
            <i class="ri-user-line text-gray-400 flex-shrink-0"></i>
            <span x-text="label" class="truncate flex-1" :class="selected ? 'text-gray-900 font-medium' : 'text-gray-500'"></span>
            <template x-if="selectedTypeLabel">
                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 flex-shrink-0" x-text="selectedTypeLabel"></span>
            </template>
            <template x-if="selected">
                <button type="button" @click.stop="clear()"
                    class="cursor-pointer p-0.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors flex-shrink-0">
                    <i class="ri-close-line text-base"></i>
                </button>
            </template>
        </button>
        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500"
             x-show="!selected">
            <i class="ri-arrow-down-s-line text-lg transition-transform" :class="{ 'rotate-180': open }"></i>
        </div>

        {{-- Dropdown Panel --}}
        <div x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

            {{-- Search --}}
            <div class="px-3 pt-3 pb-2">
                <div class="relative">
                    <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="search" x-ref="searchInput"
                        placeholder="{{ __('supervisor.calendar.search_teachers') }}"
                        class="w-full ps-9 pe-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        @click.stop>
                </div>
            </div>

            {{-- Filter Pills --}}
            @if($showGenderFilter || $showTypeFilter)
            <div class="flex items-center gap-1.5 px-3 pb-2 flex-wrap">
                @if($showTypeFilter)
                <button type="button" @click.stop="toggleType('quran')"
                    class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
                    :class="typeFilter === 'quran' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                    <i class="ri-book-open-line me-0.5"></i> {{ __('supervisor.calendar.filter_quran') }}
                </button>
                <button type="button" @click.stop="toggleType('academic')"
                    class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
                    :class="typeFilter === 'academic' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                    <i class="ri-graduation-cap-line me-0.5"></i> {{ __('supervisor.calendar.filter_academic') }}
                </button>
                @endif
                @if($showGenderFilter)
                <span class="text-gray-300 mx-0.5">|</span>
                <button type="button" @click.stop="toggleGender('male')"
                    class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
                    :class="genderFilter === 'male' ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                    <i class="ri-men-line me-0.5"></i> {{ __('supervisor.calendar.filter_male') }}
                </button>
                <button type="button" @click.stop="toggleGender('female')"
                    class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
                    :class="genderFilter === 'female' ? 'bg-pink-100 text-pink-700 border-pink-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                    <i class="ri-women-line me-0.5"></i> {{ __('supervisor.calendar.filter_female') }}
                </button>
                @endif
            </div>
            @endif

            {{-- Options List --}}
            <div class="max-h-56 overflow-y-auto border-t border-gray-100">
                <template x-for="option in filteredOptions" :key="option.id">
                    <button type="button" @click="select(option.id)"
                        class="cursor-pointer w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-start"
                        :class="option.id === selected ? 'bg-indigo-50' : ''">
                        <i class="ri-user-line text-gray-400 flex-shrink-0"
                           :class="option.id === selected ? 'text-indigo-500' : 'text-gray-400'"></i>
                        <span class="text-sm flex-1" :class="option.id === selected ? 'text-indigo-700 font-medium' : 'text-gray-700'" x-text="option.name"></span>
                        <span class="text-xs text-gray-400" x-text="option.type_label"></span>
                        <i class="ri-check-line text-indigo-500" x-show="option.id === selected"></i>
                    </button>
                </template>
                <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">
                    {{ __('supervisor.calendar.no_matching_teachers') }}
                </div>
            </div>
        </div>
    </div>
</div>
