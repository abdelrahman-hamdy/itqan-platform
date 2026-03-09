<div>
    @if($assignments->isEmpty())
        <div class="bg-gray-50 rounded-xl py-12 text-center">
            <div class="max-w-md mx-auto px-4">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-file-list-3-line text-3xl text-blue-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('teacher.quizzes.no_assignments_title') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('teacher.quizzes.no_assignments_desc') }}</p>
                <a href="{{ route('teacher.quizzes.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="ri-add-line"></i>
                    {{ __('teacher.quizzes.go_to_quizzes') }}
                </a>
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
                                {{ $assignment->quiz->duration_minutes }} {{ __('teacher.calendar.minutes_short') }}
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

            {{-- Link to full quizzes page --}}
            <div class="text-center pt-2">
                <a href="{{ route('teacher.quizzes.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
                   class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                    {{ __('teacher.quizzes.manage_all_quizzes') }} <i class="ri-arrow-left-s-line"></i>
                </a>
            </div>
        </div>
    @endif
</div>
