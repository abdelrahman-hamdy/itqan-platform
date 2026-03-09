<x-layouts.teacher
    :title="($isEdit ? __('teacher.circle_form.edit_title') : __('teacher.circle_form.create_title')) . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.sidebar.group_circles'), 'route' => route('teacher.group-circles.index', ['subdomain' => $subdomain])],
            ['label' => $isEdit ? __('teacher.circle_form.edit_title') : __('teacher.circle_form.create_title')],
        ]"
        view-type="teacher"
    />

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start gap-2">
                <i class="ri-error-warning-line text-red-600 text-lg flex-shrink-0"></i>
                <p class="font-medium text-red-900 text-sm">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST"
          action="{{ $isEdit
              ? route('teacher.group-circles.update', ['subdomain' => $subdomain, 'circle' => $circle->id])
              : route('teacher.group-circles.store', ['subdomain' => $subdomain]) }}"
          x-data="groupCircleForm()">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Basic Info Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-information-line text-blue-600"></i>
                {{ __('teacher.circle_form.basic_info') }}
            </h2>

            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.circle_form.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $circle->name ?? '') }}"
                           required maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.circle_form.name_placeholder') }}">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.circle_form.description') }}
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.circle_form.description_placeholder') }}">{{ old('description', $circle->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Specialization & Level Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Specialization --}}
                    <div>
                        <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.specialization') }}
                        </label>
                        <select name="specialization" id="specialization"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.circle_form.choose_specialization') }}</option>
                            @foreach(\App\Models\QuranCircle::SPECIALIZATIONS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('specialization', $circle->specialization ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialization')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Memorization Level --}}
                    <div>
                        <label for="memorization_level" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.memorization_level') }}
                        </label>
                        <select name="memorization_level" id="memorization_level"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.circle_form.choose_level') }}</option>
                            @foreach(\App\Models\QuranCircle::MEMORIZATION_LEVELS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('memorization_level', $circle->memorization_level ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('memorization_level')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Age Group & Gender Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Age Group --}}
                    <div>
                        <label for="age_group" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.age_group') }}
                        </label>
                        <select name="age_group" id="age_group"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.circle_form.choose_age_group') }}</option>
                            @foreach(\App\Models\QuranCircle::AGE_GROUPS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('age_group', $circle->age_group ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('age_group')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Gender Type --}}
                    <div>
                        <label for="gender_type" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.gender_type') }}
                        </label>
                        <select name="gender_type" id="gender_type"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.circle_form.choose_gender') }}</option>
                            @foreach(\App\Models\QuranCircle::GENDER_TYPES as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('gender_type', $circle->gender_type ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('gender_type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-settings-3-line text-green-600"></i>
                {{ __('teacher.circle_form.configuration') }}
            </h2>

            <div class="space-y-4">
                {{-- Max Students & Monthly Fee Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Max Students --}}
                    <div>
                        <label for="max_students" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.max_students') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="max_students" id="max_students"
                               value="{{ old('max_students', $circle->max_students ?? 10) }}"
                               required min="2" max="50"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('max_students')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Monthly Fee --}}
                    <div>
                        <label for="monthly_fee" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.circle_form.monthly_fee') }}
                        </label>
                        <div class="relative">
                            <input type="number" name="monthly_fee" id="monthly_fee"
                                   value="{{ old('monthly_fee', $circle->monthly_fee ?? '') }}"
                                   min="0" step="0.01"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="0.00">
                            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-gray-400 pointer-events-none">
                                {{ __('teacher.circle_form.currency') }}
                            </span>
                        </div>
                        @error('monthly_fee')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Monthly Sessions Count --}}
                <div>
                    <label for="monthly_sessions_count" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.circle_form.monthly_sessions_count') }}
                    </label>
                    <input type="number" name="monthly_sessions_count" id="monthly_sessions_count"
                           value="{{ old('monthly_sessions_count', $circle->monthly_sessions_count ?? '') }}"
                           min="1" max="60"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.circle_form.monthly_sessions_placeholder') }}">
                    @error('monthly_sessions_count')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status Toggle --}}
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="status" value="0">
                        <input type="checkbox" name="status" value="1"
                               {{ old('status', $circle->status ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ __('teacher.circle_form.active_status') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Learning Objectives Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-trophy-line text-amber-600"></i>
                {{ __('teacher.circle_form.learning_objectives') }}
            </h2>

            <div class="space-y-3">
                <template x-for="(objective, index) in objectives" :key="index">
                    <div class="flex gap-2">
                        <input type="text" :name="'learning_objectives[' + index + ']'"
                               x-model="objectives[index]"
                               maxlength="500"
                               class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                               :placeholder="'{{ __('teacher.circle_form.objective_placeholder') }} ' + (index + 1)">
                        <button type="button" @click="removeObjective(index)"
                                x-show="objectives.length > 1"
                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </template>

                <button type="button" @click="addObjective()"
                        class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="ri-add-line"></i>
                    {{ __('teacher.circle_form.add_objective') }}
                </button>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('teacher.group-circles.index', ['subdomain' => $subdomain]) }}"
               class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ __('common.actions.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-save-line ms-1"></i>
                {{ $isEdit ? __('common.actions.save_changes') : __('teacher.circle_form.create_circle') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function groupCircleForm() {
        const existingObjectives = @json(old('learning_objectives', $circle->learning_objectives ?? []));
        return {
            objectives: existingObjectives.length > 0 ? existingObjectives : [''],

            addObjective() {
                this.objectives.push('');
            },

            removeObjective(index) {
                this.objectives.splice(index, 1);
            }
        };
    }
</script>
@endpush

</x-layouts.teacher>
