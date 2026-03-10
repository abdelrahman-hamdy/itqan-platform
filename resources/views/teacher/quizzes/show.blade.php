<x-layouts.teacher :title="$quiz->title . ' - ' . __('teacher.quizzes.breadcrumb') . ' - ' . config('app.name')">
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
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.quizzes.breadcrumb'), 'route' => route('teacher.quizzes.index', ['subdomain' => $subdomain])],
            ['label' => $quiz->title, 'truncate' => true],
        ]"
        view-type="teacher"
    />

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

    <x-quiz.quiz-detail
        :quiz="$quiz"
        :subdomain="$subdomain"
        :show-edit-button="true"
        edit-route="teacher.quizzes.edit"
        :show-assignment-management="true"
        accent-color="blue"
        :assignment-type-labels="$assignmentTypeLabels"
    />
</div>

<x-slot:scripts>
<script>
function assignmentForm() {
    return {
        showForm: false,
        assignableType: '',
        assignableId: '',
        assignableOptions: [],
        maxAttempts: 1,
        isVisible: true,
        availableFrom: '',
        availableUntil: '',
        loading: false,
        submitting: false,
        errorMessage: '',

        async loadOptions() {
            if (!this.assignableType) {
                this.assignableOptions = [];
                this.assignableId = '';
                return;
            }

            this.loading = true;
            this.assignableId = '';
            this.assignableOptions = [];
            this.errorMessage = '';

            try {
                const response = await fetch(
                    @js(route('teacher.quizzes.assignable-options', ['subdomain' => $subdomain])) + '?type=' + encodeURIComponent(this.assignableType),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }
                );
                if (response.ok) {
                    const data = await response.json();
                    this.assignableOptions = data.options || [];
                } else {
                    this.errorMessage = @js(__('teacher.quizzes.error_loading_options'));
                }
            } catch (e) {
                this.errorMessage = @js(__('teacher.quizzes.error_loading_options'));
                this.assignableOptions = [];
            }

            this.loading = false;
        },

        async submitAssignment() {
            if (!this.assignableType || !this.assignableId) return;

            this.submitting = true;
            this.errorMessage = '';

            try {
                const response = await fetch(
                    @js(route('teacher.quizzes.assign', ['subdomain' => $subdomain, 'quiz' => $quiz->id])),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            assignable_type: this.assignableType,
                            assignable_id: this.assignableId,
                            max_attempts: this.maxAttempts,
                            is_visible: this.isVisible,
                            available_from: this.availableFrom || null,
                            available_until: this.availableUntil || null,
                        })
                    }
                );

                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    this.errorMessage = data.message || @js(__('teacher.quizzes.error_assigning'));
                }
            } catch (e) {
                this.errorMessage = @js(__('teacher.quizzes.error_assigning'));
            }

            this.submitting = false;
        },

        revokeBaseUrl: @js(route('teacher.quizzes.revoke-assignment', ['subdomain' => $subdomain, 'assignment' => '__ASSIGNMENT_ID__'])),

        revokeAssignment(assignmentId) {
            confirmAction({
                title: @js(__('teacher.quizzes.revoke_assignment')),
                message: @js(__('teacher.quizzes.confirm_revoke')),
                confirmText: @js(__('teacher.quizzes.revoke_assignment')),
                isDangerous: true,
                icon: 'ri-delete-bin-line',
                onConfirm: async () => {
                    try {
                        const response = await fetch(
                            this.revokeBaseUrl.replace('__ASSIGNMENT_ID__', assignmentId),
                            {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': @js(csrf_token()),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                }
                            }
                        );

                        if (response.ok) {
                            window.location.reload();
                        } else {
                            const data = await response.json();
                            this.errorMessage = data.message || @js(__('teacher.quizzes.error_revoking'));
                        }
                    } catch (e) {
                        this.errorMessage = @js(__('teacher.quizzes.error_revoking'));
                    }
                }
            });
        }
    }
}
</script>
</x-slot:scripts>
</x-layouts.teacher>
