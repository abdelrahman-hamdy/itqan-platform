<x-layouts.student
    :title="($session->title ?? __('student.session_detail.academic_session_default')) . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('student.session_detail.academic_description_prefix') . ' ' . ($session->academicTeacher->full_name ?? __('student.session_detail.academic_teacher_default'))">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $breadcrumbItems = [
        ['label' => __('student.session_detail.academic_teachers_breadcrumb'), 'route' => route('academic-teachers.index', ['subdomain' => $subdomain]), 'icon' => 'ri-user-star-line'],
    ];
    if($session->academicSubscription) {
        $breadcrumbItems[] = ['label' => $session->academicSubscription->subject_name ?? __('student.session_detail.academic_lesson_default'), 'route' => route('student.academic-subscriptions.show', ['subdomain' => $subdomain, 'subscriptionId' => $session->academicSubscription->id]), 'truncate' => true];
    }
    $breadcrumbItems[] = ['label' => $session->title ?? __('student.session_detail.academic_session_default'), 'truncate' => true];
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb :items="$breadcrumbItems" view-type="student" />

    <div class="space-y-6">
        <!-- Session Header -->
        <x-sessions.session-header :session="$session" view-type="student" />

        <!-- Enhanced LiveKit Meeting Interface -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <x-meetings.livekit-interface 
                :session="$session" 
                user-type="student"
            />
        </div>

            <!-- Session Progress & Content -->
            @if($session->status === \App\Enums\SessionStatus::COMPLETED)
                <!-- Academic Homework Display -->
                <x-sessions.homework-display 
                    :session="$session" 
                    view-type="student" 
                    session-type="academic" />
                
                <!-- Session Content Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-file-text-line text-primary ms-2"></i>
                        {{ __('student.session_detail.session_summary') }}
                    </h3>

                    @if($session->lesson_content)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">{{ __('student.session_detail.lesson_content') }}</h4>
                            <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                                {{ $session->lesson_content }}
                            </div>
                        </div>
                    @endif

                    @if($session->learning_outcomes)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">{{ __('student.session_detail.learning_outcomes') }}</h4>
                            <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                                {{ $session->learning_outcomes }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Student Feedback Section -->
            @if($session->status === \App\Enums\SessionStatus::COMPLETED)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-message-line text-primary ms-2"></i>
                        {{ __('student.session_detail.your_rating') }}
                    </h3>

                    @if($session->student_feedback)
                        <div class="mb-4">
                            <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                                {{ $session->student_feedback }}
                            </div>
                        </div>
                    @else
                        <form id="feedbackForm" class="space-y-4">
                            @csrf
                            <div>
                                <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('student.session_detail.share_feedback') }}
                                </label>
                                <textarea
                                    id="feedback"
                                    name="feedback"
                                    rows="4"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                    placeholder="{{ __('student.session_detail.feedback_placeholder') }}"></textarea>
                            </div>
                            <button
                                type="submit"
                                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                                <i class="ri-send-plane-line ms-2"></i>
                                {{ __('student.session_detail.submit_rating') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif

    </div>
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>


// Feedback form submission
document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const feedback = document.getElementById('feedback').value.trim();
    if (!feedback) {
        window.toast?.warning('{{ __('student.session_detail.rating_required') }}');
        return;
    }

    // Submit feedback via AJAX
    fetch('{{ route("student.academic-sessions.feedback", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ feedback: feedback })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Replace form with submitted feedback using safe DOM creation (no innerHTML with user data)
            const container = document.querySelector('#feedbackForm').parentElement;
            const feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'mb-4';
            const innerDiv = document.createElement('div');
            innerDiv.className = 'text-gray-700 bg-gray-50 rounded-lg p-4';
            innerDiv.textContent = feedback;
            feedbackDiv.appendChild(innerDiv);
            const successDiv = document.createElement('div');
            successDiv.className = 'text-sm text-green-600';
            const icon = document.createElement('i');
            icon.className = 'ri-check-line ms-1';
            const successText = document.createTextNode(' {{ __('student.session_detail.rating_success') }}');
            successDiv.appendChild(icon);
            successDiv.appendChild(successText);
            container.replaceChildren(feedbackDiv, successDiv);
        } else {
            window.toast?.error('{{ __('student.session_detail.rating_error') }}');
        }
    })
    .catch(error => {
        window.toast?.error('{{ __('student.session_detail.rating_error') }}');
    });
});



// Academic Homework submission functionality
document.getElementById('homeworkSubmissionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>{{ __('student.session_detail.submitting') }}';

    // Submit homework via AJAX
    fetch('{{ route("student.academic-sessions.submit-homework", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Replace form with success message and submitted content
            const submission = formData.get('homework_submission');
            const file = formData.get('homework_file');

            // Build success UI using safe DOM creation (no innerHTML with user data)
            const wrapper = document.createElement('div');
            wrapper.className = 'bg-green-50 border border-green-200 rounded-lg p-4';

            const headerRow = document.createElement('div');
            headerRow.className = 'flex items-center mb-2';
            const checkIcon = document.createElement('i');
            checkIcon.className = 'ri-check-circle-line text-green-600 ms-2';
            const headerSpan = document.createElement('span');
            headerSpan.className = 'font-medium text-green-800';
            headerSpan.textContent = '{{ __('student.session_detail.homework_submitted') }}';
            headerRow.appendChild(checkIcon);
            headerRow.appendChild(headerSpan);

            const submissionDiv = document.createElement('div');
            submissionDiv.className = 'text-sm text-green-700 mb-3';
            submissionDiv.textContent = submission;

            wrapper.appendChild(headerRow);
            wrapper.appendChild(submissionDiv);

            if (file && file.size > 0 && data.data.file_path) {
                const fileAnchor = document.createElement('a');
                fileAnchor.href = '/storage/' + data.data.file_path;
                fileAnchor.target = '_blank';
                fileAnchor.className = 'inline-flex items-center text-green-600 hover:text-green-800 text-sm';
                const attachIcon = document.createElement('i');
                attachIcon.className = 'ri-attachment-line ms-1';
                fileAnchor.appendChild(attachIcon);
                fileAnchor.appendChild(document.createTextNode(' {{ __('student.session_detail.attached_file') }}'));
                wrapper.appendChild(fileAnchor);
            }

            this.parentElement.replaceChildren(wrapper);
        } else {
            window.toast?.error(data.message || '{{ __('student.session_detail.homework_submit_error') }}');
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        window.toast?.error('{{ __('student.session_detail.homework_submit_error') }}');
        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});

document.addEventListener('DOMContentLoaded', function() {

    // Auto-scroll to meeting if session is starting soon
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 5 && $session->scheduled_at->diffInMinutes(now()) >= -5)
        setTimeout(() => {
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1000);
    @endif

    // Note: "Session starting soon" notification is now centralized in livekit-interface component
});
</script>
</x-slot>

</x-layouts.student>