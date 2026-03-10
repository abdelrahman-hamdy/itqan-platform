<x-layouts.teacher :title="__('teacher.quizzes.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

    {{-- Page Header --}}
    <div>
        <x-ui.breadcrumb :items="[['label' => __('teacher.quizzes.breadcrumb')]]" view-type="teacher" />

        <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.quizzes.page_title') }}</h1>
                <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.quizzes.page_description') }}</p>
            </div>
            <a href="{{ route('teacher.quizzes.create', ['subdomain' => $subdomain]) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                <i class="ri-add-line"></i>
                {{ __('teacher.quizzes.create_quiz') }}
            </a>
        </div>
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

    <x-quiz.quiz-list
        :quizzes="$quizzes"
        :total-quizzes="$totalQuizzes"
        :active-quizzes="$activeQuizzes"
        :total-assignments="$totalAssignments"
        :total-attempts="$totalAttempts"
        :filter-route="route('teacher.quizzes.index', ['subdomain' => $subdomain])"
        :subdomain="$subdomain"
        show-route="teacher.quizzes.show"
        :show-edit-button="true"
        edit-route="teacher.quizzes.edit"
        accent-color="blue"
    />
</x-layouts.teacher>
