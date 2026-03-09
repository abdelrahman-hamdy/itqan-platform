<div>
    {{-- Flash Messages --}}
    @if($successMessage)
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 flex items-start gap-2">
            <i class="ri-checkbox-circle-fill text-green-500 mt-0.5 flex-shrink-0"></i>
            <span class="text-sm text-green-800">{{ $successMessage }}</span>
        </div>
    @endif
    @if($errorMessage)
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 flex items-start gap-2">
            <i class="ri-error-warning-fill text-red-500 mt-0.5 flex-shrink-0"></i>
            <span class="text-sm text-red-800">{{ $errorMessage }}</span>
        </div>
    @endif

    {{-- Header with Assign Button --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
            <i class="ri-file-list-3-line text-blue-600"></i>
            {{ __('teacher.quizzes.assigned_quizzes_label') }}
            <span class="text-xs font-normal text-gray-500">({{ $assignments->count() }})</span>
        </h3>
        @if($availableQuizzes->count() > 0 || $showForm)
            <button wire:click="toggleForm" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors cursor-pointer
                           {{ $showForm ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-purple-600 text-white hover:bg-purple-700' }}">
                @if($showForm)
                    <i class="ri-close-line"></i> {{ __('common.cancel') }}
                @else
                    <i class="ri-add-line"></i> {{ __('teacher.quizzes.assign_quiz') }}
                @endif
            </button>
        @endif
    </div>

    {{-- Assignment Form --}}
    @if($showForm)
        <div class="p-4 bg-purple-50 rounded-lg border border-purple-200 mb-4">
            <h4 class="text-sm font-semibold text-purple-900 mb-3">{{ __('teacher.quizzes.new_assignment') }}</h4>

            <div class="space-y-3">
                {{-- Quiz Select --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.quizzes.select_quiz_label') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="selectedQuizId"
                            class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                        <option value="">{{ __('teacher.quizzes.select_quiz_placeholder') }}</option>
                        @foreach($availableQuizzes as $quiz)
                            <option value="{{ $quiz->id }}">
                                {{ $quiz->title }}
                                ({{ $quiz->duration_minutes ? $quiz->duration_minutes . ' ' . __('teacher.calendar.minutes_short') : __('teacher.quizzes.unlimited') }}
                                / {{ $quiz->passing_score }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Max Attempts & Visibility --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_max_attempts') }}
                        </label>
                        <input type="number" wire:model="maxAttempts" min="1" max="10"
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" wire:model="isVisible"
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700">{{ __('teacher.quizzes.field_is_visible') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_available_from') }}
                        </label>
                        <input type="datetime-local" wire:model="availableFrom"
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('teacher.quizzes.field_available_until') }}
                        </label>
                        <input type="datetime-local" wire:model="availableUntil"
                               class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm">
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end">
                    <button wire:click="assignQuiz" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="assignQuiz"><i class="ri-check-line"></i></span>
                        <span wire:loading wire:target="assignQuiz"><i class="ri-loader-4-line animate-spin"></i></span>
                        {{ __('teacher.quizzes.assign_quiz') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Assignments List --}}
    @if($assignments->isEmpty() && !$showForm)
        <div class="bg-gray-50 rounded-xl py-10 text-center">
            <div class="max-w-md mx-auto px-4">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="ri-file-list-3-line text-2xl text-blue-400"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('teacher.quizzes.no_assignments_title') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('teacher.quizzes.no_assignments_desc') }}</p>
                @if($availableQuizzes->count() > 0)
                    <button wire:click="toggleForm" type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors cursor-pointer">
                        <i class="ri-add-line"></i>
                        {{ __('teacher.quizzes.assign_quiz') }}
                    </button>
                @else
                    <a href="{{ route('teacher.quizzes.create', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="ri-add-line"></i>
                        {{ __('teacher.quizzes.create_quiz') }}
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach($assignments as $assignment)
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('teacher.quizzes.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'quiz' => $assignment->quiz->id]) }}"
                               class="text-sm font-semibold text-gray-900 hover:text-blue-600 transition-colors">
                                {{ $assignment->quiz->title }}
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $assignment->quiz->questions_count ?? $assignment->quiz->questions()->count() }} {{ __('teacher.quizzes.questions_count_label') }}
                                &middot;
                                {{ $assignment->quiz->duration_minutes ?? '-' }} {{ __('teacher.calendar.minutes_short') }}
                                &middot;
                                {{ __('teacher.quizzes.passing_score_label') }} {{ $assignment->quiz->passing_score }}%
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if($assignment->is_available)
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium bg-green-100 text-green-700 rounded-full">
                                    {{ __('teacher.quizzes.widget_status_active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-600 rounded-full">
                                    {{ __('teacher.quizzes.widget_status_inactive') }}
                                </span>
                            @endif
                            @if(!$assignment->is_visible)
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium bg-yellow-100 text-yellow-700 rounded-full">
                                    {{ __('teacher.quizzes.status_hidden') }}
                                </span>
                            @endif
                            @if($assignment->can_revoke)
                                <button wire:click="revokeAssignment('{{ $assignment->id }}')"
                                        wire:confirm="{{ __('teacher.quizzes.confirm_revoke') }}"
                                        class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors cursor-pointer"
                                        title="{{ __('teacher.quizzes.revoke_assignment') }}">
                                    <i class="ri-delete-bin-line text-base"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Stats row --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                        <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                            <p class="text-base font-bold text-gray-900">{{ $assignment->completed_attempts }}</p>
                            <p class="text-[10px] text-gray-500">{{ __('teacher.quizzes.stat_completed') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                            <p class="text-base font-bold text-green-600">{{ $assignment->passed_attempts }}</p>
                            <p class="text-[10px] text-gray-500">{{ __('teacher.quizzes.stat_passed') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                            <p class="text-base font-bold text-blue-600">{{ $assignment->avg_score !== null ? $assignment->avg_score . '%' : '-' }}</p>
                            <p class="text-[10px] text-gray-500">{{ __('teacher.quizzes.stat_avg_score') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-2 py-1.5">
                            <p class="text-base font-bold text-gray-900">{{ $assignment->max_attempts }}</p>
                            <p class="text-[10px] text-gray-500">{{ __('teacher.quizzes.stat_max_attempts') }}</p>
                        </div>
                    </div>

                    {{-- Date info --}}
                    @if($assignment->available_from || $assignment->available_until)
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            @if($assignment->available_from)
                                <span><i class="ri-calendar-line me-0.5"></i> {{ __('teacher.quizzes.from_date') }}: {{ toAcademyTimezone($assignment->available_from)->format('Y/m/d') }}</span>
                            @endif
                            @if($assignment->available_until)
                                <span><i class="ri-calendar-check-line me-0.5"></i> {{ __('teacher.quizzes.until_date') }}: {{ toAcademyTimezone($assignment->available_until)->format('Y/m/d') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Link to full quizzes page --}}
        <div class="text-center pt-3">
            <a href="{{ route('teacher.quizzes.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                {{ __('teacher.quizzes.manage_all_quizzes') }} <i class="ri-arrow-left-s-line"></i>
            </a>
        </div>
    @endif
</div>
