@php
    $circle = $circle ?? null;
    $isEdit = $circle !== null;

    $val = function (string $key, $default = null) use ($circle) {
        return old($key, $circle?->{$key} ?? $default);
    };

    $checked = function (string $key, bool $default = false) use ($circle) {
        $existing = $circle?->{$key};
        if (is_object($existing) && property_exists($existing, 'value')) {
            $existing = $existing->value;
        }
        $current = old($key, $existing ?? $default);
        return (bool) $current;
    };

    $enrollmentChecked = old(
        'enrollment_status',
        $circle?->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN ? '1' : '0'
    );

    $selectedDays = old('schedule_days', $circle?->schedule_days ?? []);
    if (! is_array($selectedDays)) {
        $selectedDays = [];
    }

    $objectives = old('learning_objectives', $circle?->learning_objectives ?? []);
    if (! is_array($objectives)) {
        $objectives = [];
    }
@endphp

<div class="space-y-6">
    {{-- Section 1: Basic Info --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-green-100 text-green-700 flex items-center justify-center">
                <i class="ri-information-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.basic_info') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" maxlength="150" required
                       value="{{ $val('name') }}"
                       placeholder="{{ __('supervisor.group_circles.name_placeholder') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            @if($isEdit)
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ __('supervisor.group_circles.circle_code') }}
                    </label>
                    <input type="text" disabled value="{{ $circle->circle_code }}"
                           class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-600">
                    <p class="text-xs text-gray-400 mt-1">{{ __('supervisor.group_circles.circle_code_help') }}</p>
                </div>
            @endif

            <div class="{{ $isEdit ? '' : 'md:col-span-2' }}">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.description') }}
                </label>
                <textarea name="description" rows="3" maxlength="500"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">{{ $val('description') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">{{ __('supervisor.group_circles.description_help') }}</p>
                @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 2: Classification --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                <i class="ri-bookmark-3-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.classification_section') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.age_group') }} <span class="text-red-500">*</span>
                </label>
                <select name="age_group" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">{{ __('supervisor.group_circles.select_placeholder') }}</option>
                    @foreach($ageGroupOptions as $value => $label)
                        <option value="{{ $value }}" @selected($val('age_group') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('age_group') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.gender_type') }} <span class="text-red-500">*</span>
                </label>
                <select name="gender_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">{{ __('supervisor.group_circles.select_placeholder') }}</option>
                    @foreach($genderTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($val('gender_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender_type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.specialization_label') }} <span class="text-red-500">*</span>
                </label>
                <select name="specialization" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    @foreach($specializationOptions as $value => $label)
                        <option value="{{ $value }}" @selected($val('specialization', 'memorization') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('specialization') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.memorization_level') }} <span class="text-red-500">*</span>
                </label>
                <select name="memorization_level" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    @foreach($memorizationLevelOptions as $value => $label)
                        <option value="{{ $value }}" @selected($val('memorization_level', 'beginner') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('memorization_level') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 3: Teacher & Capacity --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                <i class="ri-user-star-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.teacher_capacity_section') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.quran_teacher') }} <span class="text-red-500">*</span>
                </label>
                <x-ui.searchable-select
                    name="quran_teacher_id"
                    :options="$teachers"
                    :selected="$val('quran_teacher_id')"
                    :placeholder="__('supervisor.group_circles.select_teacher')"
                    :search-placeholder="__('supervisor.group_circles.teacher_search_placeholder')"
                    :empty-message="__('supervisor.group_circles.no_matching_teachers')"
                    :form-mode="true"
                    :show-gender-filter="true"
                    :required="true"
                />
                @error('quran_teacher_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.max_students') }} <span class="text-red-500">*</span>
                </label>
                <input type="number" name="max_students" min="1" max="20" required
                       value="{{ $val('max_students', 8) }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                @error('max_students') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.monthly_sessions_count') }} <span class="text-red-500">*</span>
                </label>
                <select name="monthly_sessions_count" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    @foreach($monthlySessionsOptions as $value => $label)
                        <option value="{{ $value }}" @selected((int) $val('monthly_sessions_count', 8) === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('monthly_sessions_count') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 4: Pricing --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                <i class="ri-money-dollar-circle-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.pricing_section') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.monthly_fee') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="monthly_fee" min="0" step="1" required
                           value="{{ $val('monthly_fee', 0) }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500 ps-14">
                    <span class="absolute inset-y-0 start-0 flex items-center px-3 text-xs font-medium text-gray-500 bg-gray-50 border-e border-gray-300 rounded-s-lg">
                        {{ getCurrencyCode() }}
                    </span>
                </div>
                @error('monthly_fee') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 5: Schedule --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                <i class="ri-calendar-2-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.schedule_section') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.schedule_days') }}
                </label>
                <x-ui.multi-select
                    name="schedule_days"
                    :options="$weekDayOptions"
                    :selected="$selectedDays"
                    :placeholder="__('supervisor.group_circles.select_placeholder')"
                />
                @error('schedule_days') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.schedule_time') }}
                </label>
                <select name="schedule_time" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">{{ __('supervisor.group_circles.select_placeholder') }}</option>
                    @foreach($scheduleTimeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($val('schedule_time') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('schedule_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 6: Learning Objectives --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                <i class="ri-flag-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.objectives_section') }}</h3>
        </header>

        <div x-data="{
            tags: {{ json_encode(array_values($objectives)) }},
            input: '',
            add() {
                const v = this.input.trim();
                if (v.length > 0 && !this.tags.includes(v)) {
                    this.tags.push(v);
                }
                this.input = '';
            },
            remove(i) { this.tags.splice(i, 1); }
        }">
            <div class="flex gap-2">
                <input type="text" x-model="input" maxlength="150"
                       @keydown.enter.prevent="add()"
                       placeholder="{{ __('supervisor.group_circles.learning_objectives_placeholder') }}"
                       class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                <button type="button" @click="add()"
                        class="inline-flex items-center gap-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg">
                    <i class="ri-add-line"></i>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ __('supervisor.group_circles.learning_objectives_help') }}</p>

            <div class="flex flex-wrap gap-2 mt-3" x-show="tags.length > 0">
                <template x-for="(tag, i) in tags" :key="tag + i">
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-teal-50 text-teal-700 text-xs rounded-full">
                        <span x-text="tag"></span>
                        <button type="button" @click="remove(i)" class="text-teal-500 hover:text-red-500">
                            <i class="ri-close-line"></i>
                        </button>
                        <input type="hidden" name="learning_objectives[]" :value="tag">
                    </span>
                </template>
            </div>
        </div>
    </section>

    {{-- Section 7: Status & Administrative --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
        <header class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                <i class="ri-shield-user-line"></i>
            </span>
            <h3 class="text-sm font-bold text-gray-900">{{ __('supervisor.group_circles.admin_section') }}</h3>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Toggles --}}
            <div class="md:col-span-2 space-y-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1"
                           {{ $checked('status', true) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-medium">{{ __('supervisor.group_circles.circle_status') }}</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="enrollment_status" value="0">
                    <input type="checkbox" name="enrollment_status" value="1"
                           {{ (string) $enrollmentChecked === '1' ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-medium">{{ __('supervisor.group_circles.enrollment_status') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('supervisor.group_circles.enrollment_status_help') }}</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="recording_enabled" value="0">
                    <input type="checkbox" name="recording_enabled" value="1"
                           {{ $checked('recording_enabled') ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-medium">{{ __('supervisor.group_circles.recording_enabled') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('supervisor.group_circles.recording_enabled_help') }}</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="allow_sponsored_requests" value="0">
                    <input type="checkbox" name="allow_sponsored_requests" value="1"
                           {{ $checked('allow_sponsored_requests') ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-medium">{{ __('supervisor.group_circles.allow_sponsored_requests') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('supervisor.group_circles.allow_sponsored_requests_help') }}</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="is_enrolled_only" value="0">
                    <input type="checkbox" name="is_enrolled_only" value="1"
                           {{ $checked('is_enrolled_only') ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-medium">{{ __('supervisor.group_circles.is_enrolled_only') }}</span>
                        <span class="block text-xs text-gray-500">{{ __('supervisor.group_circles.is_enrolled_only_help') }}</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.admin_notes') }}
                </label>
                <textarea name="admin_notes" rows="3" maxlength="1000"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">{{ $val('admin_notes') }}</textarea>
                @error('admin_notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('supervisor.group_circles.supervisor_notes') }}
                </label>
                <textarea name="supervisor_notes" rows="3" maxlength="2000"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">{{ $val('supervisor_notes') }}</textarea>
                @error('supervisor_notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Section 8: Progress (edit-only, read-only) --}}
    @if($isEdit)
        <section class="bg-gray-50 rounded-xl border border-gray-200 p-5 md:p-6">
            <header class="flex items-center gap-2 mb-3">
                <span class="w-8 h-8 rounded-lg bg-gray-200 text-gray-600 flex items-center justify-center">
                    <i class="ri-line-chart-line"></i>
                </span>
                <h3 class="text-sm font-bold text-gray-700">{{ __('supervisor.group_circles.progress_section') }}</h3>
            </header>
            <p class="text-xs text-gray-500 mb-3">{{ __('supervisor.group_circles.progress_section_help') }}</p>

            <div class="grid grid-cols-3 gap-3">
                <x-ui.stat-card
                    color="blue"
                    icon="ri-book-open-line"
                    :label="__('supervisor.group_circles.total_memorized_pages')"
                    :value="(int) ($circle->total_memorized_pages ?? 0)"
                />
                <x-ui.stat-card
                    color="green"
                    icon="ri-refresh-line"
                    :label="__('supervisor.group_circles.total_reviewed_pages')"
                    :value="(int) ($circle->total_reviewed_pages ?? 0)"
                />
                <x-ui.stat-card
                    color="purple"
                    icon="ri-bookmark-line"
                    :label="__('supervisor.group_circles.total_reviewed_surahs')"
                    :value="(int) ($circle->total_reviewed_surahs ?? 0)"
                />
            </div>
        </section>
    @endif
</div>
