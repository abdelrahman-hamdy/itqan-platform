@props([
    'subscription',
    'viewType' => 'teacher'
])

@php
    $isTeacher = $viewType === 'teacher';
    $student = $subscription->student;
@endphp

<!-- Academic Homework Management Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ri-book-2-line text-orange-600 ms-2"></i>
            {{ __('components.academic.homework_management.title') }}
        </h3>
        @if($isTeacher)
        <button onclick="createNewHomework({{ $subscription->id }})"
                class="inline-flex items-center px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="ri-add-line ms-1"></i>
            {{ __('components.academic.homework_management.new_homework') }}
        </button>
        @endif
    </div>

    <!-- Homework Stats -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-orange-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-800">{{ __('components.academic.homework_management.active_homework') }}</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $subscription->active_homework_count ?? 0 }}</p>
                </div>
                <i class="ri-book-open-line text-orange-400 text-2xl"></i>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-800">{{ __('components.academic.homework_management.completion_rate') }}</p>
                    <p class="text-2xl font-bold text-green-600">{{ $subscription->homework_completion_rate ?? 90 }}%</p>
                </div>
                <i class="ri-check-double-line text-green-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Recent Homework -->
    <div class="space-y-3">
        <h4 class="text-sm font-semibold text-gray-700 flex items-center">
            <i class="ri-history-line text-gray-500 ms-1"></i>
            {{ __('components.academic.homework_management.recent_homework') }}
        </h4>

        <!-- Sample Homework Items -->
        @php
        $sampleHomework = [
            [
                'id' => 1,
                'title' => __('components.academic.homework_management.sample_titles.solve_exercises'),
                'status' => 'pending',
                'due_date' => '2024-01-15',
                'grade' => null
            ],
            [
                'id' => 2,
                'title' => __('components.academic.homework_management.sample_titles.read_lesson'),
                'status' => 'completed',
                'due_date' => '2024-01-12',
                'grade' => 85
            ],
            [
                'id' => 3,
                'title' => __('components.academic.homework_management.sample_titles.write_report'),
                'status' => 'overdue',
                'due_date' => '2024-01-10',
                'grade' => null
            ]
        ];
        @endphp

        @forelse($sampleHomework as $homework)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h5 class="text-sm font-medium text-gray-900">{{ $homework['title'] }}</h5>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $homework['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                           ($homework['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ $homework['status'] === 'completed' ? __('components.academic.homework_management.status_completed') :
                           ($homework['status'] === 'pending' ? __('components.academic.homework_management.status_pending') : __('components.academic.homework_management.status_overdue')) }}
                    </span>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse mt-1">
                    <span class="text-xs text-gray-500">
                        <i class="ri-calendar-line ms-1"></i>
                        {{ $homework['due_date'] }}
                    </span>
                    @if($homework['grade'])
                    <span class="text-xs text-gray-600">
                        <i class="ri-star-line ms-1"></i>
                        {{ $homework['grade'] }}/100
                    </span>
                    @endif
                </div>
            </div>
            @if($isTeacher)
            <div class="flex items-center space-x-2 space-x-reverse me-3">
                <button onclick="editHomework({{ $homework['id'] }})" 
                        class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors">
                    <i class="ri-edit-line text-sm"></i>
                </button>
                @if($homework['status'] === 'completed' && !$homework['grade'])
                <button onclick="gradeHomework({{ $homework['id'] }})" 
                        class="p-1.5 text-gray-400 hover:text-green-600 transition-colors">
                    <i class="ri-star-line text-sm"></i>
                </button>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-8">
            <i class="ri-book-2-line text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500 text-sm">{{ __('components.academic.homework_management.no_homework_yet') }}</p>
            @if($isTeacher)
            <button onclick="createNewHomework({{ $subscription->id }})"
                    class="mt-3 inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="ri-add-line ms-1"></i>
                {{ __('components.academic.homework_management.create_new') }}
            </button>
            @endif
        </div>
        @endforelse
    </div>

    <!-- View All Homework Link -->
    @if(count($sampleHomework) > 0)
    <div class="mt-4 pt-4 border-t border-gray-200">
        <a href="#" class="inline-flex items-center text-sm text-orange-600 hover:text-orange-700 font-medium">
            {{ __('components.academic.homework_management.view_all') }}
            <i class="ri-arrow-left-s-line me-1"></i>
        </a>
    </div>
    @endif
</div>

<script>
@if($isTeacher)
function createNewHomework(subscriptionId) {
    // This will open a modal or navigate to homework creation page
    showModal({
        title: '{{ __('components.academic.homework_management.modal.create_title') }}',
        content: '{{ __('components.academic.homework_management.modal.create_coming_soon') }}',
        type: 'info'
    });
}

function editHomework(homeworkId) {
    showModal({
        title: '{{ __('components.academic.homework_management.modal.edit_title') }}',
        content: '{{ __('components.academic.homework_management.modal.edit_coming_soon') }}',
        type: 'info'
    });
}

function gradeHomework(homeworkId) {
    showModal({
        title: '{{ __('components.academic.homework_management.modal.grade_title') }}',
        content: '{{ __('components.academic.homework_management.modal.grade_coming_soon') }}',
        type: 'info'
    });
}

// Utility function for showing modals
function showModal(options) {
    window.toast?.info(options.content);
}
@endif
</script>
