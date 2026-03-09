<x-layouts.teacher :title="__('teacher.quizzes.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $filterOptions = [
        '' => __('teacher.quizzes.all_quizzes'),
        '1' => __('teacher.quizzes.active_only'),
        '0' => __('teacher.quizzes.inactive_only'),
    ];

    $stats = [
        [
            'icon' => 'ri-questionnaire-line',
            'bgColor' => 'bg-blue-100',
            'iconColor' => 'text-blue-600',
            'value' => $totalQuizzes ?? 0,
            'label' => __('teacher.quizzes.total_quizzes'),
        ],
        [
            'icon' => 'ri-checkbox-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $activeQuizzes ?? 0,
            'label' => __('teacher.quizzes.active_quizzes'),
        ],
        [
            'icon' => 'ri-links-line',
            'bgColor' => 'bg-purple-100',
            'iconColor' => 'text-purple-600',
            'value' => $totalAssignments ?? 0,
            'label' => __('teacher.quizzes.total_assignments'),
        ],
        [
            'icon' => 'ri-file-list-3-line',
            'bgColor' => 'bg-amber-100',
            'iconColor' => 'text-amber-600',
            'value' => $totalAttempts ?? 0,
            'label' => __('teacher.quizzes.total_attempts'),
        ],
    ];
@endphp

    {{-- Create Quiz Action --}}
    <div class="mb-4 md:mb-6 flex justify-end">
        <a href="{{ route('teacher.quizzes.create', ['subdomain' => $subdomain]) }}"
           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            <i class="ri-add-line"></i>
            {{ __('teacher.quizzes.create_quiz') }}
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start">
                <i class="ri-checkbox-circle-line text-green-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                <p class="font-medium text-green-900 text-sm md:text-base">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-red-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                <p class="font-medium text-red-900 text-sm md:text-base">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <x-teacher.entity-list-page
        :title="__('teacher.quizzes.page_title')"
        :subtitle="__('teacher.quizzes.page_description')"
        :items="$quizzes"
        :stats="$stats"
        :filter-options="$filterOptions"
        filter-param="is_active"
        :breadcrumbs="[['label' => __('teacher.quizzes.breadcrumb')]]"
        theme-color="blue"
        :list-title="__('teacher.quizzes.list_title')"
        empty-icon="ri-questionnaire-line"
        :empty-title="__('teacher.quizzes.empty_title')"
        :empty-description="__('teacher.quizzes.empty_description')"
        :empty-filter-description="__('teacher.quizzes.empty_filter_description')"
        :clear-filter-route="route('teacher.quizzes.index', ['subdomain' => $subdomain])"
        :clear-filter-text="__('teacher.quizzes.view_all')"
    >
        @foreach($quizzes as $quiz)
            @php
                $metadata = [
                    ['icon' => 'ri-question-line', 'iconColor' => 'text-blue-500', 'text' => $quiz->questions_count . ' ' . __('teacher.quizzes.questions')],
                    ['icon' => 'ri-links-line', 'iconColor' => 'text-purple-500', 'text' => ($quiz->assignments_count ?? $quiz->assignments->count()) . ' ' . __('teacher.quizzes.assignments')],
                    ['icon' => 'ri-percent-line', 'iconColor' => 'text-green-500', 'text' => __('teacher.quizzes.passing_score') . ': ' . $quiz->passing_score . '%'],
                ];
                if ($quiz->duration_minutes) {
                    $metadata[] = ['icon' => 'ri-time-line', 'iconColor' => 'text-amber-500', 'text' => $quiz->duration_minutes . ' ' . __('teacher.quizzes.minutes')];
                }

                $actions = [
                    [
                        'href' => route('teacher.quizzes.show', ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                        'icon' => 'ri-eye-line',
                        'label' => __('common.view'),
                        'shortLabel' => __('common.view'),
                        'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                    ],
                    [
                        'href' => route('teacher.quizzes.edit', ['subdomain' => $subdomain, 'quiz' => $quiz->id]),
                        'icon' => 'ri-edit-line',
                        'label' => __('common.edit'),
                        'shortLabel' => __('common.edit'),
                        'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                    ],
                ];
            @endphp

            <x-teacher.entity-list-item
                :title="$quiz->title"
                :status-badge="$quiz->is_active ? __('teacher.quizzes.active') : __('teacher.quizzes.inactive')"
                :status-class="$quiz->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                :metadata="$metadata"
                :description="$quiz->description"
                :actions="$actions"
                icon="ri-questionnaire-line"
                icon-bg-class="bg-gradient-to-br from-blue-500 to-blue-600"
            />
        @endforeach
    </x-teacher.entity-list-page>
</x-layouts.teacher>
