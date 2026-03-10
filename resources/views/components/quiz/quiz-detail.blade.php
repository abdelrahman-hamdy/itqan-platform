@props([
    'quiz',
    'subdomain',
    'showEditButton' => false,
    'editRoute' => null,
    'showAssignmentManagement' => false,
    'accentColor' => 'blue',
    'assignmentTypeLabels' => [],
])

@php
    $colorMap = [
        'blue' => [
            'btn_primary' => 'bg-blue-600 hover:bg-blue-700',
        ],
        'indigo' => [
            'btn_primary' => 'bg-indigo-600 hover:bg-indigo-700',
        ],
    ];
    $c = $colorMap[$accentColor] ?? $colorMap['blue'];
@endphp

{{-- Quiz Info Card --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $quiz->is_active ? __('teacher.quizzes.active') : __('teacher.quizzes.inactive') }}
                </span>
            </div>
            @if($quiz->description)
                <p class="text-sm text-gray-600 mb-3">{{ $quiz->description }}</p>
            @endif
        </div>
        @if($showEditButton && $editRoute)
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route($editRoute, ['subdomain' => $subdomain, 'quiz' => $quiz->id]) }}"
                   class="min-h-[44px] inline-flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    <i class="ri-edit-line"></i>
                    {{ __('common.edit') }}
                </a>
            </div>
        @endif
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="text-center p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-center mb-1">
                <i class="ri-question-line text-blue-600"></i>
            </div>
            <p class="text-lg md:text-xl font-bold text-blue-900">{{ $quiz->questions->count() }}</p>
            <p class="text-xs text-blue-700">{{ __('teacher.quizzes.questions') }}</p>
        </div>
        <div class="text-center p-3 bg-green-50 rounded-lg">
            <div class="flex items-center justify-center mb-1">
                <i class="ri-percent-line text-green-600"></i>
            </div>
            <p class="text-lg md:text-xl font-bold text-green-900">{{ $quiz->passing_score }}%</p>
            <p class="text-xs text-green-700">{{ __('teacher.quizzes.passing_score') }}</p>
        </div>
        <div class="text-center p-3 bg-purple-50 rounded-lg">
            <div class="flex items-center justify-center mb-1">
                <i class="ri-time-line text-purple-600"></i>
            </div>
            <p class="text-lg md:text-xl font-bold text-purple-900">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' ' . __('teacher.quizzes.min_short') : __('teacher.quizzes.unlimited') }}</p>
            <p class="text-xs text-purple-700">{{ __('teacher.quizzes.duration_label') }}</p>
        </div>
        <div class="text-center p-3 bg-amber-50 rounded-lg">
            <div class="flex items-center justify-center mb-1">
                <i class="ri-shuffle-line text-amber-600"></i>
            </div>
            <p class="text-lg md:text-xl font-bold text-amber-900">{{ $quiz->randomize_questions ? __('teacher.quizzes.yes') : __('teacher.quizzes.no') }}</p>
            <p class="text-xs text-amber-700">{{ __('teacher.quizzes.randomize_label') }}</p>
        </div>
    </div>
</div>

{{-- Questions Section --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6" x-data="{ openQuestion: null }">
    <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center">
        <i class="ri-list-ordered text-blue-600 ms-2"></i>
        {{ __('teacher.quizzes.questions_section') }}
        <span class="text-sm font-normal text-gray-500 ms-2">({{ $quiz->questions->count() }})</span>
    </h2>

    @forelse($quiz->questions as $index => $question)
        <div class="border border-gray-200 rounded-lg mb-3 last:mb-0 overflow-hidden">
            {{-- Question Header (Accordion Toggle) --}}
            <button type="button"
                    @click="openQuestion = openQuestion === {{ $index }} ? null : {{ $index }}"
                    class="w-full flex items-center justify-between p-3 md:p-4 text-start hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold">
                        {{ $index + 1 }}
                    </span>
                    <span class="text-sm md:text-base font-medium text-gray-900 truncate">{{ $question->question_text }}</span>
                </div>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200 flex-shrink-0 ms-2"
                   :class="openQuestion === {{ $index }} ? 'rotate-180' : ''"></i>
            </button>

            {{-- Question Body (Accordion Content) --}}
            <div x-show="openQuestion === {{ $index }}"
                 x-collapse
                 class="border-t border-gray-200 p-3 md:p-4 bg-gray-50">
                <p class="text-sm text-gray-800 font-medium mb-3">{{ $question->question_text }}</p>
                <div class="space-y-2">
                    @foreach($question->options as $oIndex => $option)
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $oIndex === $question->correct_option ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200' }}">
                            @if($oIndex === $question->correct_option)
                                <i class="ri-checkbox-circle-fill text-green-500 flex-shrink-0"></i>
                            @else
                                <i class="ri-checkbox-blank-circle-line text-gray-300 flex-shrink-0"></i>
                            @endif
                            <span class="{{ $oIndex === $question->correct_option ? 'text-green-800 font-medium' : 'text-gray-700' }}">{{ $option }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-question-line text-xl text-gray-400"></i>
            </div>
            <p class="text-sm text-gray-500">{{ __('teacher.quizzes.no_questions_yet') }}</p>
        </div>
    @endforelse
</div>

{{-- Assignments Section --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6"
     @if($showAssignmentManagement) x-data="assignmentForm()" @endif>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base md:text-lg font-bold text-gray-900 flex items-center">
            <i class="ri-links-line text-purple-600 ms-2"></i>
            {{ __('teacher.quizzes.assignments_section') }}
            <span class="text-sm font-normal text-gray-500 ms-2">({{ $quiz->assignments->count() }})</span>
        </h2>
        @if($showAssignmentManagement)
            <button type="button" @click="showForm = !showForm"
                    class="min-h-[44px] inline-flex items-center gap-2 px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                <i class="ri-add-line" x-show="!showForm"></i>
                <i class="ri-close-line" x-show="showForm"></i>
                <span x-text="showForm ? '{{ __('common.cancel') }}' : '{{ __('teacher.quizzes.assign_quiz') }}'"></span>
            </button>
        @endif
    </div>

    @if($showAssignmentManagement)
        {{-- Assignment Form (Toggle) --}}
        <div x-show="showForm" x-collapse class="mb-4">
            <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                <h3 class="text-sm font-semibold text-purple-900 mb-3">{{ __('teacher.quizzes.new_assignment') }}</h3>

                <div class="space-y-3">
                    {{-- Type Select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_assignable_type') }} <span class="text-red-500">*</span>
                        </label>
                        <select x-model="assignableType" @change="loadOptions()"
                                class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <option value="">{{ __('teacher.quizzes.select_type') }}</option>
                            @if(auth()->user()->isQuranTeacher())
                                <option value="quran_circle">{{ __('teacher.quizzes.type_quran_circle') }}</option>
                                <option value="quran_individual_circle">{{ __('teacher.quizzes.type_quran_individual') }}</option>
                            @endif
                            @if(auth()->user()->isAcademicTeacher())
                                <option value="academic_lesson">{{ __('teacher.quizzes.type_academic_lesson') }}</option>
                            @endif
                            <option value="interactive_course">{{ __('teacher.quizzes.type_interactive_course') }}</option>
                        </select>
                    </div>

                    {{-- Entity Select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_assignable_entity') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="assignableId" :disabled="!assignableType || loading"
                                    class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm disabled:bg-gray-100 disabled:text-gray-400">
                                <option value="">{{ __('teacher.quizzes.select_entity') }}</option>
                                <template x-for="opt in assignableOptions" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.name"></option>
                                </template>
                            </select>
                            <div x-show="loading" class="absolute inset-y-0 end-8 flex items-center">
                                <i class="ri-loader-4-line animate-spin text-purple-500"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Max Attempts & Visibility Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('teacher.quizzes.field_max_attempts') }}
                            </label>
                            <input type="number" x-model.number="maxAttempts" min="1" max="10"
                                   class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer pb-2">
                                <input type="checkbox" x-model="isVisible"
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-700">{{ __('teacher.quizzes.field_is_visible') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- Date Range Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('teacher.quizzes.field_available_from') }}
                            </label>
                            <input type="datetime-local" x-model="availableFrom"
                                   class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('teacher.quizzes.field_available_until') }}
                            </label>
                            <input type="datetime-local" x-model="availableUntil"
                                   class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                        </div>
                    </div>

                    {{-- Error Message --}}
                    <div x-show="errorMessage" x-text="errorMessage"
                         class="text-sm text-red-600 bg-red-50 p-2 rounded-lg"></div>

                    {{-- Submit --}}
                    <div class="flex justify-end">
                        <button type="button" @click="submitAssignment()"
                                :disabled="submitting || !assignableType || !assignableId"
                                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ri-check-line" x-show="!submitting"></i>
                            <i class="ri-loader-4-line animate-spin" x-show="submitting"></i>
                            {{ __('teacher.quizzes.assign_quiz') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Existing Assignments List --}}
    @forelse($quiz->assignments as $assignment)
        @php
            $typeEnum = $assignment->getAssignableTypeEnum();
            $typeKey = $assignmentTypeLabels[$assignment->assignable_type] ?? 'unknown';
            $entityName = $assignment->assignable?->name ?? $assignment->assignable?->title ?? __('common.unknown');
            $attemptsCount = $assignment->attempts_count ?? $assignment->attempts->count();
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 border border-gray-200 rounded-lg mb-2 last:mb-0 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-3 flex-1 min-w-0 mb-2 sm:mb-0">
                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-links-line text-purple-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $entityName }}</p>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 mt-0.5">
                        <span>{{ $typeEnum?->label() ?? __('common.unknown') }}</span>
                        <span class="text-gray-300">|</span>
                        <span>{{ $attemptsCount }} {{ __('teacher.quizzes.attempts') }}</span>
                        @if($assignment->max_attempts)
                            <span class="text-gray-300">|</span>
                            <span>{{ __('teacher.quizzes.max_label') }}: {{ $assignment->max_attempts }}</span>
                        @endif
                        @if($assignment->available_until)
                            <span class="text-gray-300">|</span>
                            <span>
                                <i class="ri-time-line"></i>
                                {{ $assignment->available_until->format('Y-m-d') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($assignment->is_visible)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        {{ __('teacher.quizzes.visible') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                        {{ __('teacher.quizzes.hidden') }}
                    </span>
                @endif
                @if($showAssignmentManagement)
                    <button type="button"
                            @click="revokeAssignment('{{ $assignment->id }}')"
                            class="min-h-[44px] inline-flex items-center justify-center px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                            title="{{ __('teacher.quizzes.revoke_assignment') }}">
                        <i class="ri-delete-bin-line text-lg"></i>
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-8">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-links-line text-xl text-gray-400"></i>
            </div>
            <p class="text-sm text-gray-500 mb-1">{{ __('teacher.quizzes.no_assignments') }}</p>
            @if($showAssignmentManagement)
                <p class="text-xs text-gray-400">{{ __('teacher.quizzes.no_assignments_hint') }}</p>
            @endif
        </div>
    @endforelse
</div>
