@props(['history'])

<div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $history->count() }}</p>
        <p class="text-sm text-gray-500">{{ __('student.quiz.total_attempts') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ $history->where('passed', true)->count() }}</p>
        <p class="text-sm text-gray-500">{{ __('student.quiz.passed_quizzes') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ round($history->avg('score'), 1) }}%</p>
        <p class="text-sm text-gray-500">{{ __('student.quiz.average_score') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-purple-600">{{ $history->max('score') }}%</p>
        <p class="text-sm text-gray-500">{{ __('student.quiz.highest_score') }}</p>
    </div>
</div>
