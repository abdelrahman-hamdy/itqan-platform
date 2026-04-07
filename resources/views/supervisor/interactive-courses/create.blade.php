<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.interactive_courses.breadcrumb'), 'route' => route('manage.interactive-courses.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.interactive_courses.create_course')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.interactive_courses.create_course') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.interactive_courses.page_subtitle') }}</p>
    </div>

    <div class="max-w-3xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <form method="POST" action="{{ route('manage.interactive-courses.store', ['subdomain' => $subdomain]) }}">
                @csrf
                <div class="space-y-4">

                    {{-- Section 1: Basic Info --}}
                    <div>
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.basic_info') }}</h4>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.title') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.description') }} <span class="text-red-500">*</span></label>
                                <textarea name="description" rows="3" maxlength="2000"
                                          class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('description') }}</textarea>
                                @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Specialization --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.specialization') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.subject') }} <span class="text-red-500">*</span></label>
                                <select name="subject_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">--</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.grade_level') }} <span class="text-red-500">*</span></label>
                                <select name="grade_level_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">--</option>
                                    @foreach($gradeLevels as $level)
                                        <option value="{{ $level->id }}" {{ old('grade_level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                                    @endforeach
                                </select>
                                @error('grade_level_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.teacher') }} <span class="text-red-500">*</span></label>
                                <select name="assigned_teacher_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">--</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t['profile_id'] }}" {{ old('assigned_teacher_id') == $t['profile_id'] ? 'selected' : '' }}>{{ $t['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_teacher_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Course Settings --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.course_settings') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.total_sessions') }} <span class="text-red-500">*</span></label>
                                <input type="number" name="total_sessions" value="{{ old('total_sessions', 16) }}" min="1" max="200"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('total_sessions') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.sessions_per_week') }} <span class="text-red-500">*</span></label>
                                <input type="number" name="sessions_per_week" value="{{ old('sessions_per_week', 2) }}" min="1" max="7"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('sessions_per_week') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.session_duration') }} <span class="text-red-500">*</span></label>
                                <select name="session_duration_minutes" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    @foreach(\App\Enums\SessionDuration::options() as $value => $label)
                                        <option value="{{ $value }}" {{ old('session_duration_minutes', 60) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('session_duration_minutes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.max_students') }} <span class="text-red-500">*</span></label>
                                <input type="number" name="max_students" value="{{ old('max_students', 20) }}" min="1" max="50"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('max_students') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.difficulty_level') }}</label>
                                <select name="difficulty_level" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">--</option>
                                    @foreach(\App\Enums\DifficultyLevel::options() as $value => $label)
                                        <option value="{{ $value }}" {{ old('difficulty_level') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Financial Settings --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.financial_settings') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.student_price') }} <span class="text-red-500">*</span></label>
                                <input type="number" name="student_price" value="{{ old('student_price', 500) }}" min="0" step="0.01"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('student_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.sale_price') }}</label>
                                <input type="number" name="sale_price" value="{{ old('sale_price') }}" min="0" step="0.01"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="{{ __('supervisor.interactive_courses.sale_price_placeholder') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.teacher_payment') }}</label>
                                <input type="number" name="teacher_payment" value="{{ old('teacher_payment', 2000) }}" min="0" step="0.01"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.payment_type') }}</label>
                                <select name="payment_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="fixed_amount" {{ old('payment_type', 'fixed_amount') === 'fixed_amount' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_fixed') }}</option>
                                    <option value="per_student" {{ old('payment_type') === 'per_student' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_per_student') }}</option>
                                    <option value="per_session" {{ old('payment_type') === 'per_session' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_per_session') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Section 5: Dates & Schedule --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.dates_schedule') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.start_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                @error('start_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.enrollment_deadline') }}</label>
                                <input type="date" name="enrollment_deadline" value="{{ old('enrollment_deadline') }}"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('enrollment_deadline') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 6: Settings --}}
                    <div class="border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.recording_settings') }}</h4>
                        <div class="space-y-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1"
                                       {{ old('is_published') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.is_published') }}</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="recording_enabled" value="0">
                                <input type="checkbox" name="recording_enabled" value="1"
                                       {{ old('recording_enabled', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.recording_enabled') }}</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="show_recording_to_teacher" value="0">
                                <input type="checkbox" name="show_recording_to_teacher" value="1"
                                       {{ old('show_recording_to_teacher') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.show_to_teacher') }}</span>
                            </label>
                            <br>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="show_recording_to_student" value="0">
                                <input type="checkbox" name="show_recording_to_student" value="1"
                                       {{ old('show_recording_to_student') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.show_to_student') }}</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                        {{ __('supervisor.interactive_courses.create_course') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layouts.supervisor>
