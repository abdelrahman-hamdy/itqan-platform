<div>
    @if($quizzes->isEmpty())
        <x-ui.empty-state
            icon="ri-file-list-3-line"
            :title="__('student.quizzes.no_quizzes')"
            :description="__('common.empty_states.no_quizzes_assigned')"
            color="blue"
            variant="inline"
        />
    @else
        <div class="space-y-4">
            @foreach($quizzes as $quizData)
                <x-quiz.item-row :quizData="$quizData" />
            @endforeach
        </div>
    @endif
</div>
