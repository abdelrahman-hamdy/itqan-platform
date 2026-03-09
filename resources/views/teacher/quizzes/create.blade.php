<x-layouts.teacher :title="__('teacher.quizzes.create_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.quizzes.breadcrumb'), 'route' => route('teacher.quizzes.index', ['subdomain' => $subdomain])],
            ['label' => __('teacher.quizzes.create_title')],
        ]"
        view-type="teacher"
    />

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-red-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                <p class="font-medium text-red-900 text-sm md:text-base">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST"
          action="{{ route('teacher.quizzes.store', ['subdomain' => $subdomain]) }}"
          x-data="quizForm()"
          x-on:submit="beforeSubmit($event)">
        @csrf

        {{-- Quiz Info Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i class="ri-information-line text-blue-600 ms-2"></i>
                {{ __('teacher.quizzes.quiz_info') }}
            </h2>

            <div class="space-y-4">
                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.quizzes.field_title') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}"
                           required maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.quizzes.field_title_placeholder') }}">
                    @error('title')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.quizzes.field_description') }}
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.quizzes.field_description_placeholder') }}">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Duration & Passing Score Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Duration --}}
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_duration') }}
                        </label>
                        <div class="relative">
                            <input type="number" name="duration_minutes" id="duration_minutes"
                                   value="{{ old('duration_minutes') }}" min="1" max="180"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="{{ __('teacher.quizzes.field_duration_placeholder') }}">
                            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-gray-400 pointer-events-none">
                                {{ __('teacher.quizzes.minutes') }}
                            </span>
                        </div>
                        @error('duration_minutes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Passing Score --}}
                    <div>
                        <label for="passing_score" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_passing_score') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" name="passing_score" id="passing_score"
                                   value="{{ old('passing_score', 60) }}" required min="10" max="90"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-gray-400 pointer-events-none">%</span>
                        </div>
                        @error('passing_score')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Toggles Row --}}
                <div class="flex flex-wrap gap-6">
                    {{-- Is Active --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ __('teacher.quizzes.field_is_active') }}</span>
                    </label>

                    {{-- Randomize Questions --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="randomize_questions" value="0">
                        <input type="checkbox" name="randomize_questions" value="1"
                               {{ old('randomize_questions', false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ __('teacher.quizzes.field_randomize') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Questions Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base md:text-lg font-bold text-gray-900 flex items-center">
                    <i class="ri-list-ordered text-blue-600 ms-2"></i>
                    {{ __('teacher.quizzes.questions_section') }}
                    <span class="text-sm font-normal text-gray-500 ms-2" x-text="'(' + questions.length + ')'"></span>
                </h2>
                <button type="button" @click="addQuestion()"
                        class="min-h-[44px] inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="ri-add-line"></i>
                    {{ __('teacher.quizzes.add_question') }}
                </button>
            </div>

            {{-- Questions List --}}
            <template x-for="(question, qIndex) in questions" :key="qIndex">
                <div class="border border-gray-200 rounded-lg p-4 mb-4 last:mb-0">
                    {{-- Question Header --}}
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800">
                            <span x-text="'{{ __('teacher.quizzes.question') }} ' + (qIndex + 1)"></span>
                        </h3>
                        <button type="button" @click="removeQuestion(qIndex)"
                                class="min-h-[44px] inline-flex items-center justify-center px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                                :title="'{{ __('teacher.quizzes.remove_question') }}'">
                            <i class="ri-delete-bin-line text-lg"></i>
                        </button>
                    </div>

                    {{-- Question Text --}}
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1" x-bind:for="'question_text_' + qIndex">
                            {{ __('teacher.quizzes.field_question_text') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea :name="'questions[' + qIndex + '][question_text]'"
                                  :id="'question_text_' + qIndex"
                                  x-model="question.text"
                                  required rows="2"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                  :placeholder="'{{ __('teacher.quizzes.field_question_text_placeholder') }}'"></textarea>
                    </div>

                    {{-- Options --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('teacher.quizzes.options') }} <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">{{ __('teacher.quizzes.select_correct_hint') }}</p>

                        <template x-for="(option, oIndex) in question.options" :key="oIndex">
                            <div class="flex items-center gap-2 mb-2">
                                {{-- Radio for correct answer --}}
                                <input type="radio"
                                       :name="'questions[' + qIndex + '][correct_option_radio]'"
                                       :value="oIndex"
                                       x-model.number="question.correctOption"
                                       class="text-green-600 focus:ring-green-500 flex-shrink-0">
                                {{-- Option text --}}
                                <input type="text"
                                       :name="'questions[' + qIndex + '][options][]'"
                                       x-model="question.options[oIndex]"
                                       required
                                       class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                       :placeholder="'{{ __('teacher.quizzes.option_placeholder') }} ' + (oIndex + 1)">
                                {{-- Remove option button --}}
                                <button type="button" @click="removeOption(qIndex, oIndex)"
                                        x-show="question.options.length > 2"
                                        class="min-h-[44px] inline-flex items-center justify-center px-2 py-1 text-red-400 hover:text-red-600 transition-colors flex-shrink-0">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            </div>
                        </template>

                        {{-- Add option button --}}
                        <button type="button" @click="addOption(qIndex)"
                                x-show="question.options.length < 6"
                                class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm mt-1 transition-colors">
                            <i class="ri-add-line"></i>
                            {{ __('teacher.quizzes.add_option') }}
                        </button>
                    </div>

                    {{-- Hidden field for correct option index --}}
                    <input type="hidden"
                           :name="'questions[' + qIndex + '][correct_option]'"
                           :value="question.correctOption">
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="questions.length === 0" class="text-center py-8 md:py-12">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="ri-question-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('teacher.quizzes.no_questions_yet') }}</p>
                <button type="button" @click="addQuestion()"
                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                    <i class="ri-add-line"></i>
                    {{ __('teacher.quizzes.add_first_question') }}
                </button>
            </div>
        </div>

        {{-- Submit Actions --}}
        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <a href="{{ route('teacher.quizzes.index', ['subdomain' => $subdomain]) }}"
               class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="min-h-[44px] inline-flex items-center justify-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                <i class="ri-save-line"></i>
                {{ __('teacher.quizzes.save_quiz') }}
            </button>
        </div>
    </form>
</div>

<x-slot:scripts>
<script>
function quizForm() {
    return {
        questions: [
            { text: '', options: ['', '', '', ''], correctOption: 0 }
        ],

        addQuestion() {
            this.questions.push({ text: '', options: ['', '', '', ''], correctOption: 0 });
        },

        removeQuestion(index) {
            if (this.questions.length > 0) {
                this.questions.splice(index, 1);
            }
        },

        addOption(qIndex) {
            if (this.questions[qIndex].options.length < 6) {
                this.questions[qIndex].options.push('');
            }
        },

        removeOption(qIndex, oIndex) {
            if (this.questions[qIndex].options.length > 2) {
                this.questions[qIndex].options.splice(oIndex, 1);
                // Reset correct option if out of bounds
                if (this.questions[qIndex].correctOption >= this.questions[qIndex].options.length) {
                    this.questions[qIndex].correctOption = 0;
                }
            }
        },

        beforeSubmit(event) {
            if (this.questions.length === 0) {
                event.preventDefault();
                alert(@js(__('teacher.quizzes.validation_min_questions')));
                return false;
            }
            // Validate each question has text and at least 2 options filled
            for (let i = 0; i < this.questions.length; i++) {
                if (!this.questions[i].text.trim()) {
                    event.preventDefault();
                    alert(@js(__('teacher.quizzes.validation_question_text_required')) + ' ' + (i + 1));
                    return false;
                }
                const filledOptions = this.questions[i].options.filter(o => o.trim() !== '');
                if (filledOptions.length < 2) {
                    event.preventDefault();
                    alert(@js(__('teacher.quizzes.validation_min_options')) + ' ' + (i + 1));
                    return false;
                }
            }
            return true;
        }
    }
}
</script>
</x-slot:scripts>
</x-layouts.teacher>
