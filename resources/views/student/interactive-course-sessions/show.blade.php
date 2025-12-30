<x-layouts.student
    :title="($session->title ?? __('student.course_session.interactive_session_default') . ' ' . $session->session_number) . ' - ' . config('app.name', __('student.common.platform_default'))"
    :description="__('student.course_session.session_details') . ' - ' . ($session->course->title ?? __('student.course_session.interactive_course_default'))">

@php
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.course_session.interactive_courses_breadcrumb'), 'route' => route('interactive-courses.index', ['subdomain' => $subdomain]), 'icon' => 'ri-book-open-line'],
            ['label' => $session->course->title, 'route' => route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $session->course->id]), 'truncate' => true],
            ['label' => __('student.course_session.session_number') . ' ' . $session->session_number],
        ]"
        view-type="student"
    />

    <div class="space-y-4 md:space-y-6">
        <!-- Session Header -->
        <x-sessions.session-header :session="$session" view-type="student" />

        <!-- Enhanced LiveKit Meeting Interface -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <x-meetings.livekit-interface
                :session="$session"
                user-type="student"
            />
        </div>

        {{-- Session Content Section --}}
        @if($session->lesson_content)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">
                    <i class="ri-file-text-line text-primary ms-2"></i>
                    {{ __('student.course_session.session_content_title') }}
                </h3>

                <div class="prose prose-sm md:prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3 md:p-4">
                    {!! nl2br(e($session->lesson_content)) !!}
                </div>
            </div>
        @endif

        {{-- Session Recordings (for completed sessions with recordings) --}}
        @if($session instanceof \App\Contracts\RecordingCapable && $session->status === \App\Enums\SessionStatus::COMPLETED)
            <x-recordings.session-recordings
                :session="$session"
                view-type="student"
            />
        @endif

        {{-- Homework Display (for completed sessions) --}}
        @if($session->status === \App\Enums\SessionStatus::COMPLETED && $session->homework_description)
            <x-sessions.homework-display
                :session="$session"
                view-type="student"
                session-type="interactive" />
        @endif

        <!-- Student Feedback Section (for completed sessions) -->
        @if($session->status === \App\Enums\SessionStatus::COMPLETED)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">
                    <i class="ri-message-line text-primary ms-2"></i>
                    {{ __('student.course_session.student_feedback_title') }}
                </h3>

                @if($session->student_feedback)
                    <!-- Display Existing Feedback -->
                    <div class="bg-green-50 border border-green-200 rounded-xl p-3 md:p-4">
                        <div class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-line text-green-600 text-lg md:text-xl mt-1 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <h4 class="font-semibold text-green-900 mb-1 md:mb-2">{{ __('student.course_session.feedback_submitted_title') }}</h4>
                                <p class="text-sm md:text-base text-green-800">{{ $session->student_feedback }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Feedback Form -->
                    <form id="feedbackForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="feedback_text" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('student.course_session.feedback_notes_label') }}
                            </label>
                            <textarea
                                id="feedback_text"
                                name="feedback"
                                rows="4"
                                class="w-full border border-gray-300 rounded-xl px-3 py-3 focus:ring-primary focus:border-primary text-sm md:text-base"
                                placeholder="{{ __('student.course_session.feedback_placeholder') }}"
                                required></textarea>
                        </div>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto bg-primary text-white px-6 py-3 rounded-xl hover:bg-secondary transition-colors">
                            <i class="ri-send-plane-line ms-2"></i>
                            {{ __('student.course_session.submit_feedback') }}
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
// Auto-scroll to meeting if session is starting soon
document.addEventListener('DOMContentLoaded', function() {
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 5 && $session->scheduled_at->diffInMinutes(now()) >= -5)
        setTimeout(() => {
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1000);
    @endif
    // Note: "Session starting soon" notification is centralized in livekit-interface component
});

// Student Feedback Form Submission
document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>{{ __("student.course_session.submitting") }}';

    fetch('{{ route("student.interactive-sessions.feedback", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message and reload
            const successMsg = document.createElement('div');
            successMsg.className = 'bg-green-50 border border-green-200 rounded-lg p-4 mt-4';
            successMsg.innerHTML = `
                <div class="flex items-center gap-2 text-green-800">
                    <i class="ri-check-line text-green-600"></i>
                    <span>{{ __('student.course_session.feedback_success') }}</span>
                </div>
            `;
            this.appendChild(successMsg);

            setTimeout(() => window.location.reload(), 1500);
        } else {
            window.toast?.error(data.message || '{{ __("student.course_session.submission_error") }}');
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        window.toast?.error('{{ __("student.course_session.submission_error") }}');
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});
</script>
</x-slot>

</x-layouts.student>
