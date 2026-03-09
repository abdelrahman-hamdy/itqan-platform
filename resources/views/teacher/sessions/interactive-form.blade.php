<x-layouts.teacher
    :title="($isEdit ? __('teacher.session_form.edit_interactive_title') : __('teacher.session_form.create_interactive_title')) . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.sidebar.interactive_courses'), 'route' => route('teacher.interactive-courses.index', ['subdomain' => $subdomain])],
            ['label' => $isEdit ? __('teacher.session_form.edit_interactive_title') : __('teacher.session_form.create_interactive_title')],
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
              ? route('teacher.interactive-sessions.update', ['subdomain' => $subdomain, 'session' => $session->id])
              : route('teacher.interactive-sessions.store', ['subdomain' => $subdomain]) }}"
          enctype="multipart/form-data"
          x-data="interactiveSessionForm()">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Course Selection Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-presentation-line text-blue-600"></i>
                {{ __('teacher.session_form.course_selection') }}
            </h2>

            <div class="space-y-4">
                {{-- Course Selector --}}
                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.course') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="course_id" id="course_id" required
                            x-model="selectedCourse"
                            @change="onCourseChange()"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                            {{ $isEdit ? 'disabled' : '' }}>
                        <option value="">{{ __('teacher.session_form.choose_course') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                    data-duration="{{ $course->session_duration_minutes }}"
                                    data-total="{{ $course->total_sessions }}"
                                {{ old('course_id', $isEdit ? $session->course_id : '') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    @if($isEdit)
                        <input type="hidden" name="course_id" value="{{ $session->course_id }}">
                    @endif
                    @error('course_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Session Number --}}
                <div>
                    <label for="session_number" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.session_number') }}
                    </label>
                    <input type="number" name="session_number" id="session_number"
                           value="{{ old('session_number', $isEdit ? $session->session_number : '') }}"
                           min="1"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.session_form.session_number_placeholder') }}">
                    <p class="text-xs text-gray-500 mt-1">{{ __('teacher.session_form.session_number_hint') }}</p>
                    @error('session_number')
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
                           value="{{ old('scheduled_at', $isEdit && $session->scheduled_at ? \App\Services\AcademyContextService::toAcademyTimezone($session->scheduled_at)->format('Y-m-d\TH:i') : '') }}"
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
                                {{ old('duration_minutes', $isEdit ? $session->duration_minutes : 60) == $mins ? 'selected' : '' }}>
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
                           value="{{ old('title', $isEdit ? $session->title : '') }}"
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
                              placeholder="{{ __('teacher.session_form.description_placeholder') }}">{{ old('description', $isEdit ? $session->description : '') }}</textarea>
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
                              placeholder="{{ __('teacher.session_form.lesson_content_placeholder') }}">{{ old('lesson_content', $isEdit ? $session->lesson_content : '') }}</textarea>
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
                           {{ old('homework_assigned', $isEdit ? $session->homework_assigned : false) ? 'checked' : '' }}
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
                              placeholder="{{ __('teacher.session_form.homework_description_placeholder') }}">{{ old('homework_description', $isEdit ? $session->homework_description : '') }}</textarea>
                    @error('homework_description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Homework File --}}
                <div x-show="homeworkAssigned" x-cloak>
                    <label for="homework_file" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.homework_file') }}
                    </label>
                    @if($isEdit && $session->homework_file)
                        <p class="text-xs text-gray-600 mb-2">
                            <i class="ri-file-line"></i>
                            {{ __('teacher.session_form.current_file') }}: {{ basename($session->homework_file) }}
                        </p>
                    @endif
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
            <a href="{{ route('teacher.interactive-courses.index', ['subdomain' => $subdomain]) }}"
               class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ __('common.actions.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-save-line ms-1"></i>
                {{ $isEdit ? __('common.actions.save_changes') : __('teacher.session_form.create_session') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function interactiveSessionForm() {
        return {
            selectedCourse: '{{ old('course_id', $isEdit ? $session->course_id : '') }}',
            durationMinutes: '{{ old('duration_minutes', $isEdit ? $session->duration_minutes : 60) }}',
            homeworkAssigned: {{ old('homework_assigned', $isEdit && $session->homework_assigned ? 'true' : 'false') }},

            onCourseChange() {
                const select = document.getElementById('course_id');
                const option = select.options[select.selectedIndex];
                if (option && option.dataset.duration) {
                    this.durationMinutes = option.dataset.duration;
                }
            }
        };
    }
</script>
@endpush

</x-layouts.teacher>
