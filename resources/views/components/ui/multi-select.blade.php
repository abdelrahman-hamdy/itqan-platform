@props([
    'name',
    'options' => [],
    'selected' => [],
    'placeholder' => '',
    'label' => '',
])

@php
    $componentId = 'multi-select-' . $name . '-' . uniqid();
@endphp

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <div x-data="{
        open: false,
        search: '',
        selected: {{ json_encode(array_map('strval', $selected)) }},
        options: {{ json_encode(collect($options)->map(fn($o) => ['id' => (string) $o['id'], 'name' => $o['name']])->values()) }},
        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const term = this.search.trim().toLowerCase();
            return this.options.filter(o => o.name.toLowerCase().includes(term));
        },
        toggle(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) { this.selected.push(id); }
            else { this.selected.splice(idx, 1); }
        },
        isSelected(id) { return this.selected.includes(id); },
        selectAll() {
            const newIds = this.filteredOptions.map(o => o.id);
            this.selected = [...new Set([...this.selected, ...newIds])];
        },
        unselectAll() {
            if (this.search.trim()) {
                const visibleIds = new Set(this.filteredOptions.map(o => o.id));
                this.selected = this.selected.filter(id => !visibleIds.has(id));
            } else {
                this.selected = [];
            }
        },
        get label() {
            if (this.selected.length === 0) return '{{ $placeholder }}';
            if (this.selected.length === 1) {
                const found = this.options.find(o => o.id === this.selected[0]);
                return found ? found.name : '{{ $placeholder }}';
            }
            return this.selected.length + ' {{ __('supervisor.teacher_earnings.teachers_selected_suffix') }}';
        }
    }" class="relative" @click.away="open = false" @keydown.escape.window="open = false">
        {{-- Trigger Button --}}
        <button type="button" @click="open = !open"
            class="cursor-pointer min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-start flex items-center gap-2">
            <span x-text="label" class="text-gray-700 truncate flex-1"></span>
            <template x-if="selected.length > 0">
                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-indigo-500 rounded-full" x-text="selected.length"></span>
            </template>
        </button>
        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
            <i class="ri-arrow-down-s-line text-lg transition-transform" :class="{ 'rotate-180': open }"></i>
        </div>

        {{-- Dropdown Panel --}}
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

            {{-- Search --}}
            <div class="px-3 pt-3 pb-2">
                <div class="relative">
                    <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="search"
                        placeholder="{{ __('supervisor.teacher_earnings.search_teachers') }}"
                        class="w-full ps-9 pe-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        @click.stop>
                </div>
            </div>

            {{-- Select All / Unselect All --}}
            <div class="flex items-center gap-2 px-3 pb-2 border-b border-gray-100">
                <button type="button" @click.stop="selectAll()"
                    class="cursor-pointer text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors px-2 py-1 rounded hover:bg-indigo-50">
                    <i class="ri-checkbox-multiple-line me-0.5"></i>
                    {{ __('supervisor.teacher_earnings.select_all') }}
                </button>
                <span class="text-gray-300">|</span>
                <button type="button" @click.stop="unselectAll()"
                    class="cursor-pointer text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors px-2 py-1 rounded hover:bg-gray-100">
                    <i class="ri-checkbox-blank-line me-0.5"></i>
                    {{ __('supervisor.teacher_earnings.unselect_all') }}
                </button>
            </div>

            {{-- Options List --}}
            <div class="max-h-56 overflow-y-auto">
                <template x-for="option in filteredOptions" :key="option.id">
                    <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition-colors"
                        @click.stop>
                        <input type="checkbox"
                            :value="option.id"
                            :checked="isSelected(option.id)"
                            @change="toggle(option.id)"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700" x-text="option.name"></span>
                    </label>
                </template>
                <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">
                    {{ __('supervisor.teacher_earnings.no_matching_teachers') }}
                </div>
            </div>
        </div>

        {{-- Hidden Inputs for Form Submission --}}
        <template x-for="id in selected" :key="'hidden-' + id">
            <input type="hidden" :name="'{{ $name }}[]'" :value="id">
        </template>
    </div>
</div>
