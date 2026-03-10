<x-layouts.supervisor>
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $assignmentTypeLabels = [
        \App\Enums\QuizAssignableType::QURAN_CIRCLE->value => 'quran_circle',
        \App\Enums\QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->value => 'quran_individual',
        \App\Enums\QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON->value => 'academic_lesson',
        \App\Enums\QuizAssignableType::INTERACTIVE_COURSE->value => 'interactive_course',
        \App\Enums\QuizAssignableType::RECORDED_COURSE->value => 'recorded_course',
    ];
@endphp

<div class="max-w-4xl mx-auto">
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

    <x-quiz.quiz-detail
        :quiz="$quiz"
        :subdomain="$subdomain"
        :show-edit-button="false"
        :show-assignment-management="false"
        accent-color="indigo"
        :assignment-type-labels="$assignmentTypeLabels"
    />
</div>
</x-layouts.supervisor>
