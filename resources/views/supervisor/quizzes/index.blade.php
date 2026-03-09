<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('manage.dashboard', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.quizzes.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.quizzes.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.quizzes.page_subtitle') }}</p>
    </div>

    <x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-questionnaire-line text-indigo-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $totalQuizzes }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.quizzes.total_quizzes') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-checkbox-circle-line text-green-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $activeQuizzes }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.quizzes.active_quizzes') }}</p>
            </div>
        </div>
    </div>

    <!-- Quiz List -->
    @if($quizzes->isNotEmpty())
        <div class="space-y-3">
            @foreach($quizzes as $quiz)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5 hover:shadow-md transition-shadow">
                    <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-questionnaire-line text-indigo-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-bold text-gray-900 truncate">{{ $quiz->title }}</h3>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">
                                        {{ __('supervisor.quizzes.created_by', ['name' => $quiz->creator?->name ?? '']) }}
                                    </span>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="text-xs text-gray-500">
                                        {{ __('supervisor.quizzes.questions_count', ['count' => $quiz->questions_count]) }}
                                    </span>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="text-xs text-gray-500">
                                        {{ __('supervisor.quizzes.assignments_count', ['count' => $quiz->assignments_count]) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs px-2.5 py-1 rounded-full {{ $quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $quiz->is_active ? __('teacher.circles_list.group.status_active') : __('teacher.circles_list.group.status_closed') }}
                            </span>
                            <a href="{{ route('manage.quizzes.show', ['subdomain' => $subdomain, 'quiz' => $quiz->id]) }}"
                               class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                <i class="ri-eye-line"></i>
                                {{ __('supervisor.common.view') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $quizzes->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-questionnaire-line text-2xl text-indigo-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">{{ __('supervisor.common.no_data') }}</h3>
            <p class="text-sm text-gray-500">{{ __('supervisor.quizzes.page_subtitle') }}</p>
        </div>
    @endif
</div>

</x-layouts.supervisor>
