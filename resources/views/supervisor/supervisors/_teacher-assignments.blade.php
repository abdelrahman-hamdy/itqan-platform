{{-- Unified teacher multi-select with search, type filters, and gender filters --}}
{{-- Expects $allTeachers (array from buildTeacherList with 'selected' key already set) --}}

<div x-data="{
    search: '',
    genderFilter: '',
    typeFilter: '',
    teachers: {{ json_encode($allTeachers) }},
    get filtered() {
        const q = this.search.trim().toLowerCase();
        return this.teachers.filter(t => {
            if (q && !t.name.toLowerCase().includes(q)) return false;
            if (this.genderFilter && t.gender !== this.genderFilter) return false;
            if (this.typeFilter && t.type !== this.typeFilter) return false;
            return true;
        });
    },
    toggle(id) {
        const item = this.teachers.find(t => t.id === id);
        if (item) item.selected = !item.selected;
    },
    get selectedCount() {
        return this.teachers.filter(t => t.selected).length;
    },
    selectAllFiltered() {
        this.filtered.forEach(t => { t.selected = true; });
    },
    deselectAllFiltered() {
        this.filtered.forEach(t => { t.selected = false; });
    },
    getSelectedByType(type) {
        return this.teachers.filter(t => t.selected && t.type === type);
    },
    toggleGender(g) { this.genderFilter = this.genderFilter === g ? '' : g; },
    toggleType(t) { this.typeFilter = this.typeFilter === t ? '' : t; },
}">
    {{-- Search --}}
    <div class="mb-3">
        <div class="relative">
            <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" x-model="search"
                placeholder="{{ __('supervisor.supervisors.search_teachers') }}"
                class="w-full ps-9 pe-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
    </div>

    {{-- Filter Pills --}}
    <div class="flex items-center gap-1.5 mb-3 flex-wrap">
        <button type="button" @click="toggleType('quran')"
            class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
            :class="typeFilter === 'quran' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
            <i class="ri-book-open-line me-0.5"></i> {{ __('supervisor.supervisors.filter_quran') }}
        </button>
        <button type="button" @click="toggleType('academic')"
            class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
            :class="typeFilter === 'academic' ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
            <i class="ri-graduation-cap-line me-0.5"></i> {{ __('supervisor.supervisors.filter_academic') }}
        </button>
        <span class="text-gray-300 mx-0.5">|</span>
        <button type="button" @click="toggleGender('male')"
            class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
            :class="genderFilter === 'male' ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
            <i class="ri-men-line me-0.5"></i> {{ __('supervisor.supervisors.filter_male') }}
        </button>
        <button type="button" @click="toggleGender('female')"
            class="cursor-pointer text-xs font-medium px-2.5 py-1 rounded-full border transition-colors"
            :class="genderFilter === 'female' ? 'bg-pink-100 text-pink-700 border-pink-300' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
            <i class="ri-women-line me-0.5"></i> {{ __('supervisor.supervisors.filter_female') }}
        </button>
    </div>

    {{-- Selected count + Select/Deselect --}}
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs text-gray-500">
            <span x-text="selectedCount"></span> {{ __('supervisor.supervisors.selected_count') }}
            <span class="text-gray-300 mx-1">|</span>
            <span x-text="filtered.length + ' / ' + teachers.length"></span> {{ __('supervisor.supervisors.teacher_count', ['count' => '']) }}
        </span>
        <div class="flex items-center gap-2">
            <button type="button" @click="selectAllFiltered()" class="cursor-pointer text-xs text-indigo-600 hover:text-indigo-800 transition-colors">
                {{ __('supervisor.supervisors.select_all') }}
            </button>
            <span class="text-gray-300">|</span>
            <button type="button" @click="deselectAllFiltered()" class="cursor-pointer text-xs text-gray-500 hover:text-gray-700 transition-colors">
                {{ __('supervisor.supervisors.deselect_all') }}
            </button>
        </div>
    </div>

    {{-- Teacher List --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto p-1 border border-gray-100 rounded-lg">
        <template x-for="t in filtered" :key="t.id">
            <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                   :class="t.selected ? (t.type === 'quran' ? 'bg-green-50 border-green-200' : 'bg-violet-50 border-violet-200') : ''">
                <input type="checkbox" :checked="t.selected" @change="toggle(t.id)"
                       class="h-4 w-4 rounded border-gray-300 flex-shrink-0"
                       :class="t.type === 'quran' ? 'text-green-600 focus:ring-green-500' : 'text-violet-600 focus:ring-violet-500'">
                <span class="text-sm text-gray-700 flex-1 truncate" x-text="t.name"></span>
                <span x-show="t.teacher_code" class="text-xs text-gray-400 font-mono flex-shrink-0" x-text="'(' + t.teacher_code + ')'"></span>
                <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                      :class="t.type === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700'"
                      x-text="t.type_label"></span>
            </label>
        </template>
        <div x-show="filtered.length === 0" class="col-span-full px-4 py-6 text-sm text-gray-400 text-center">
            <i class="ri-search-line text-2xl block mb-1"></i>
            {{ __('supervisor.supervisors.no_matching_teachers') }}
        </div>
    </div>

    {{-- Hidden inputs for form submission --}}
    <template x-for="t in getSelectedByType('quran')" :key="'q_' + t.id">
        <input type="hidden" name="quran_teacher_ids[]" :value="t.id">
    </template>
    <template x-for="t in getSelectedByType('academic')" :key="'a_' + t.id">
        <input type="hidden" name="academic_teacher_ids[]" :value="t.id">
    </template>
</div>
