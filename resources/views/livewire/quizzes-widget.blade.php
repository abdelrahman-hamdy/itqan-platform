<div>
    @if($quizzes->isEmpty())
        <x-ui.empty-state
            icon="ri-file-list-3-line"
            title="لا توجد اختبارات متاحة"
            description="لم يتم تعيين أي اختبارات حالياً"
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
