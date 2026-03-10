<x-layouts.supervisor>
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb :items="[['label' => __('supervisor.quizzes.page_title')]]" view-type="supervisor" />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.quizzes.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.quizzes.page_subtitle') }}</p>
    </div>

    <x-quiz.quiz-list
        :quizzes="$quizzes"
        :total-quizzes="$totalQuizzes"
        :active-quizzes="$activeQuizzes"
        :total-assignments="$totalAssignments"
        :total-attempts="$totalAttempts"
        :filter-route="route('manage.quizzes.index', ['subdomain' => $subdomain])"
        :subdomain="$subdomain"
        show-route="manage.quizzes.show"
        :show-edit-button="false"
        accent-color="indigo"
        :teachers="$teachers"
        :selected-teacher-id="request('teacher_id')"
    />
</div>
</x-layouts.supervisor>
