<div>
    @if($quizzes->isEmpty())
        <div class="bg-gray-50 rounded-xl py-12 text-center">
            <div class="max-w-md mx-auto px-4">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-file-list-3-line text-3xl text-blue-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('student.quiz.no_quizzes_title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('student.quiz.no_quizzes_description') }}</p>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($quizzes as $quizData)
                <x-quiz.item-row :quizData="$quizData" />
            @endforeach
        </div>
    @endif
</div>
