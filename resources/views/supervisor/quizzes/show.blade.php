<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" :type="$teacher->isQuranTeacher() ? 'quran' : 'academic'" />
    @endif

    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.quizzes.breadcrumb'), 'route' => route('manage.quizzes.index', ['subdomain' => $subdomain])],
            ['label' => $quiz->title, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Quiz Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-start justify-between mb-3">
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">{{ $quiz->title }}</h2>
                    <span class="text-xs px-2.5 py-1 rounded-full flex-shrink-0 {{ $quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $quiz->is_active ? __('teacher.circles_list.group.status_active') : __('teacher.circles_list.group.status_closed') }}
                    </span>
                </div>
                @if($quiz->description)
                    <p class="text-sm text-gray-600 mb-4">{{ $quiz->description }}</p>
                @endif
                <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><i class="ri-file-list-3-line"></i> {{ __('supervisor.quizzes.questions_count', ['count' => $quiz->questions_count]) }}</span>
                    <span class="flex items-center gap-1"><i class="ri-assignment-line"></i> {{ __('supervisor.quizzes.assignments_count', ['count' => $quiz->assignments_count]) }}</span>
                    @if($quiz->passing_score)
                        <span class="flex items-center gap-1"><i class="ri-percent-line"></i> {{ $quiz->passing_score }}%</span>
                    @endif
                    @if($quiz->duration_minutes)
                        <span class="flex items-center gap-1"><i class="ri-time-line"></i> {{ $quiz->duration_minutes }} {{ __('supervisor.observation.duration_minutes') }}</span>
                    @endif
                </div>
            </div>

            <!-- Questions -->
            @if($quiz->questions->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">{{ __('supervisor.quizzes.questions_count', ['count' => $quiz->questions->count()]) }}</h3>
                    <div class="space-y-4">
                        @foreach($quiz->questions as $index => $question)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <span class="text-xs font-bold text-gray-400 mt-0.5 flex-shrink-0">{{ $index + 1 }}.</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $question->question_text }}</p>
                                        @if($question->points)
                                            <span class="text-xs text-gray-500 mt-1 inline-block">({{ $question->points }} pts)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Assignments -->
            @if($quiz->assignments->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">{{ __('supervisor.quizzes.assignments_count', ['count' => $quiz->assignments->count()]) }}</h3>
                    <div class="space-y-3">
                        @foreach($quiz->assignments as $assignment)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $assignment->assignable?->name ?? $assignment->assignable?->title ?? '' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $assignment->attempts->count() }} {{ __('supervisor.quizzes.total_attempts') }}</p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full {{ $assignment->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $assignment->is_active ? __('teacher.circles_list.group.status_active') : __('teacher.circles_list.group.status_closed') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.common.teacher_info') }}</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2"><i class="ri-user-line text-gray-400"></i> {{ $quiz->creator?->name ?? '' }}</div>
                    <div class="flex items-center gap-2"><i class="ri-calendar-line text-gray-400"></i> {{ $quiz->created_at->format('Y/m/d') }}</div>
                </div>
            </div>

            <a href="{{ route('manage.quizzes.index', ['subdomain' => $subdomain]) }}"
               class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <i class="ri-arrow-right-line"></i>
                {{ __('supervisor.common.back_to_list') }}
            </a>
        </div>
    </div>
</div>

</x-layouts.supervisor>
