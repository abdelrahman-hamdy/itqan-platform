<x-layouts.teacher
    :title="__('teacher.session_form.create_academic_title') . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.sessions.academic.my_sessions_title'), 'route' => route('teacher.academic-sessions.index', ['subdomain' => $subdomain])],
            ['label' => __('teacher.session_form.create_academic_title')],
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
          action="{{ route('teacher.academic-sessions.store', ['subdomain' => $subdomain]) }}"
          enctype="multipart/form-data"
          x-data="academicSessionForm()">
        @csrf

        {{-- Student & Lesson Selection --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-user-line text-blue-600"></i>
                {{ __('teacher.session_form.student_selection') }}
            </h2>

            <div class="space-y-4">
                {{-- Linked Lesson (Optional) --}}
                <div>
                    <label for="academic_individual_lesson_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.linked_lesson') }}
                    </label>
                    <select name="academic_individual_lesson_id" id="academic_individual_lesson_id"
                            x-model="selectedLesson"
                            @change="onLessonChange()"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('teacher.session_form.no_linked_lesson') }}</option>
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}"
                                    data-student-id="{{ $lesson->student_id }}"
                                    data-duration="{{ $lesson->default_duration_minutes }}"
                                {{ old('academic_individual_lesson_id') == $lesson->id ? 'selected' : '' }}>
                                {{ $lesson->name }} - {{ $lesson->student->name ?? __('teacher.session_form.unknown_student') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">{{ __('teacher.session_form.linked_lesson_hint') }}</p>
                    @error('academic_individual_lesson_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Student Selector --}}
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.student') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="student_id" id="student_id" required
                            x-model="selectedStudent"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('teacher.session_form.choose_student') }}</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                {{ old('student_id') == $student->id ? 'selected' : '' }}>
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

        {{-- Session Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-calendar-check-line text-green-600"></i>
                {{ __('teacher.session_form.session_details') }}
            </h2>

            <div class="space-y-4">
                {{-- Scheduled At --}}
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.scheduled_at') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                           value="{{ old('scheduled_at') }}"
                           required
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <p class="text-xs text-gray-500 mt-1">{{ __('teacher.session_form.timezone_note', ['timezone' => \App\Services\AcademyContextService::getTimezone()]) }}</p>
                    @error('scheduled_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Duration --}}
                <div>
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.duration_minutes') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="duration_minutes" id="duration_minutes" required
                            x-model="durationMinutes"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach([30, 45, 60, 90, 120, 150, 180] as $mins)
                            <option value="{{ $mins }}"
                                {{ old('duration_minutes', 60) == $mins ? 'selected' : '' }}>
                                {{ $mins }} {{ __('teacher.session_form.minutes') }}
                            </option>
                        @endforeach
                    </select>
                    @error('duration_minutes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.title') }}
                    </label>
                    <input type="text" name="title" id="title"
                           value="{{ old('title') }}"
                           maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.session_form.title_placeholder') }}">
                    @error('title')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.description') }}
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.session_form.description_placeholder') }}">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Content --}}
                <div>
                    <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.lesson_content') }}
                    </label>
                    <textarea name="lesson_content" id="lesson_content" rows="4"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.session_form.lesson_content_placeholder') }}">{{ old('lesson_content') }}</textarea>
                    @error('lesson_content')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Homework Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-book-open-line text-violet-600"></i>
                {{ __('teacher.session_form.homework') }}
            </h2>

            <div class="space-y-4">
                {{-- Homework Toggle --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="homework_assigned" value="0">
                    <input type="checkbox" name="homework_assigned" value="1"
                           x-model="homeworkAssigned"
                           {{ old('homework_assigned') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">{{ __('teacher.session_form.assign_homework') }}</span>
                </label>

                {{-- Homework Description --}}
                <div x-show="homeworkAssigned" x-cloak>
                    <label for="homework_description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.homework_description') }}
                    </label>
                    <textarea name="homework_description" id="homework_description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.session_form.homework_description_placeholder') }}">{{ old('homework_description') }}</textarea>
                    @error('homework_description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Homework File --}}
                <div x-show="homeworkAssigned" x-cloak>
                    <label for="homework_file" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.homework_file') }}
                    </label>
                    <input type="file" name="homework_file" id="homework_file"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="w-full text-sm text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">{{ __('teacher.session_form.homework_file_hint') }}</p>
                    @error('homework_file')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('teacher.academic-sessions.index', ['subdomain' => $subdomain]) }}"
               class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ __('common.actions.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-save-line ms-1"></i>
                {{ __('teacher.session_form.create_session') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function academicSessionForm() {
        return {
            selectedLesson: '{{ old('academic_individual_lesson_id', '') }}',
            selectedStudent: '{{ old('student_id', '') }}',
            durationMinutes: '{{ old('duration_minutes', 60) }}',
            homeworkAssigned: {{ old('homework_assigned') ? 'true' : 'false' }},

            onLessonChange() {
                const select = document.getElementById('academic_individual_lesson_id');
                const option = select.options[select.selectedIndex];
                if (option && option.dataset.studentId) {
                    this.selectedStudent = option.dataset.studentId;
                }
                if (option && option.dataset.duration) {
                    this.durationMinutes = option.dataset.duration;
                }
            }
        };
    }
</script>
@endpush

</x-layouts.teacher>
