<x-layouts.teacher
    :title="($isEdit ? __('teacher.lesson_form.edit_title') : __('teacher.lesson_form.create_title')) . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.sidebar.private_lessons'), 'route' => route('teacher.academic.lessons.index', ['subdomain' => $subdomain])],
            ['label' => $isEdit ? __('teacher.lesson_form.edit_title') : __('teacher.lesson_form.create_title')],
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
              ? route('teacher.academic.lessons.update', ['subdomain' => $subdomain, 'lesson' => $lesson->id])
              : route('teacher.academic.lessons.store', ['subdomain' => $subdomain]) }}"
          x-data="academicLessonForm()">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Basic Info Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-information-line text-blue-600"></i>
                {{ __('teacher.lesson_form.basic_info') }}
            </h2>

            <div class="space-y-4">
                {{-- Lesson Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.lesson_form.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $lesson->name ?? '') }}"
                           required maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.lesson_form.name_placeholder') }}">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.lesson_form.description') }}
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.lesson_form.description_placeholder') }}">{{ old('description', $lesson->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Student Selector --}}
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.lesson_form.student') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="student_id" id="student_id" required
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('teacher.lesson_form.choose_student') }}</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                {{ old('student_id', $lesson->student_id ?? '') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }} ({{ $student->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Academic Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-book-2-line text-green-600"></i>
                {{ __('teacher.lesson_form.academic_details') }}
            </h2>

            <div class="space-y-4">
                {{-- Subject & Grade Level Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Subject --}}
                    <div>
                        <label for="academic_subject_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.lesson_form.subject') }}
                        </label>
                        <select name="academic_subject_id" id="academic_subject_id"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.lesson_form.choose_subject') }}</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}"
                                    {{ old('academic_subject_id', $lesson->academic_subject_id ?? '') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_subject_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Grade Level --}}
                    <div>
                        <label for="academic_grade_level_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.lesson_form.grade_level') }}
                        </label>
                        <select name="academic_grade_level_id" id="academic_grade_level_id"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('teacher.lesson_form.choose_grade_level') }}</option>
                            @foreach($gradeLevels as $level)
                                <option value="{{ $level->id }}"
                                    {{ old('academic_grade_level_id', $lesson->academic_grade_level_id ?? '') == $level->id ? 'selected' : '' }}>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_grade_level_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Total Sessions & Duration Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Total Sessions --}}
                    <div>
                        <label for="total_sessions" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.lesson_form.total_sessions') }}
                        </label>
                        <input type="number" name="total_sessions" id="total_sessions"
                               value="{{ old('total_sessions', $lesson->total_sessions ?? '') }}"
                               min="1" max="200"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                               placeholder="{{ __('teacher.lesson_form.total_sessions_placeholder') }}">
                        @error('total_sessions')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Default Duration --}}
                    <div>
                        <label for="default_duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.lesson_form.default_duration') }}
                        </label>
                        <select name="default_duration_minutes" id="default_duration_minutes"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @foreach([30, 45, 60, 90, 120, 150, 180] as $mins)
                                <option value="{{ $mins }}"
                                    {{ old('default_duration_minutes', $lesson->default_duration_minutes ?? 60) == $mins ? 'selected' : '' }}>
                                    {{ $mins }} {{ __('teacher.lesson_form.minutes') }}
                                </option>
                            @endforeach
                        </select>
                        @error('default_duration_minutes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Learning Objectives Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-trophy-line text-amber-600"></i>
                {{ __('teacher.lesson_form.learning_objectives') }}
            </h2>

            <div class="space-y-3">
                <template x-for="(objective, index) in objectives" :key="index">
                    <div class="flex gap-2">
                        <input type="text" :name="'learning_objectives[' + index + ']'"
                               x-model="objectives[index]"
                               maxlength="500"
                               class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                               :placeholder="'{{ __('teacher.lesson_form.objective_placeholder') }} ' + (index + 1)">
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
                    {{ __('teacher.lesson_form.add_objective') }}
                </button>
            </div>
        </div>

        {{-- Notes Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-sticky-note-line text-violet-600"></i>
                {{ __('teacher.lesson_form.notes_section') }}
            </h2>

            <div>
                <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('teacher.lesson_form.notes') }}
                </label>
                <textarea name="admin_notes" id="admin_notes" rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                          placeholder="{{ __('teacher.lesson_form.notes_placeholder') }}">{{ old('admin_notes', $lesson->admin_notes ?? '') }}</textarea>
                @error('admin_notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('teacher.academic.lessons.index', ['subdomain' => $subdomain]) }}"
               class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ __('common.actions.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-save-line ms-1"></i>
                {{ $isEdit ? __('common.actions.save_changes') : __('teacher.lesson_form.create_lesson') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function academicLessonForm() {
        const existingObjectives = @json(old('learning_objectives', $lesson->learning_objectives ?? []));
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
