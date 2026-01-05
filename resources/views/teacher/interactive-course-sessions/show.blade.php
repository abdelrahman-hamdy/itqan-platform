<x-layouts.teacher
    :title="($session->title ?? __('teacher.sessions.interactive.interactive_session') . ' ' . $session->session_number) . ' - ' . config('app.name', 'منصة إتقان')"
    :description="__('teacher.sessions.interactive.session_details') . ($session->course->title ?? __('teacher.sessions.interactive.interactive_course'))">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.sessions.interactive.breadcrumb'), 'route' => route('teacher.interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $session->course->title, 'route' => route('teacher.interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $session->course->id]), 'truncate' => true],
            ['label' => __('teacher.sessions.interactive.session_number') . ' ' . $session->session_number],
        ]"
        view-type="teacher"
    />

    <div class="space-y-4 md:space-y-6">
        <!-- Session Header -->
        <x-sessions.session-header :session="$session" view-type="teacher" />

        <!-- Enhanced LiveKit Meeting Interface -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <x-meetings.livekit-interface
                :session="$session"
                user-type="teacher"
            />
        </div>

        <!-- Session Content Management -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">
                <i class="ri-file-text-line text-primary ms-2"></i>
                {{ __('teacher.sessions.common.session_content') }}
            </h3>

            <form id="sessionContentForm" class="space-y-3 md:space-y-4">
                @csrf
                <div>
                    <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">
                        {{ __('teacher.sessions.academic.lesson_content_label') }}
                    </label>
                    <textarea
                        id="lesson_content"
                        name="lesson_content"
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm md:text-base focus:ring-primary focus:border-primary"
                        placeholder="{{ __('teacher.sessions.academic.lesson_content_placeholder') }}">{{ $session->lesson_content ?? '' }}</textarea>
                </div>

                <p class="text-xs md:text-sm text-gray-500">
                    <i class="ri-information-line ms-1"></i>
                    {{ __('teacher.sessions.interactive.report_note_students') }}
                </p>

                <button
                    type="submit"
                    class="min-h-[44px] bg-primary text-white px-4 md:px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors text-sm md:text-base">
                    <i class="ri-save-line ms-2"></i>
                    {{ __('teacher.sessions.academic.save_content') }}
                </button>
            </form>
        </div>

            <!-- Session Recordings Section -->
            @if($session instanceof \App\Contracts\RecordingCapable)
                <x-recordings.session-recordings
                    :session="$session"
                    view-type="teacher"
                />
            @endif

            <!-- Homework Management Component -->
            <x-sessions.academic-homework-management
                :session="$session"
                view-type="teacher"
                session-type="interactive"
            />

            <!-- Student List & Reports (for group sessions) -->
            @php
                $enrolledStudents = $session->course->enrolledStudents;
                $studentCount = is_countable($enrolledStudents) ? count($enrolledStudents) : $enrolledStudents->count();
            @endphp

            @if($studentCount > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">
                    <i class="ri-group-line text-primary ms-2"></i>
                    {{ __('teacher.sessions.interactive.students_list') }} ({{ __('teacher.sessions.interactive.students_count', ['count' => $studentCount]) }})
                </h3>

                <div class="grid grid-cols-1 gap-3 md:gap-4">
                    @foreach($enrolledStudents as $studentData)
                        @php
                            // Handle enrollment structure - enrolledStudents returns InteractiveCourseEnrollment
                            // which has a student relationship that returns StudentProfile
                            // StudentProfile has a user relationship that returns the actual User with name
                            $student = $studentData->student?->user ?? $studentData->student ?? $studentData;
                        @endphp

                        <x-sessions.student-item
                            :student="$student"
                            :session="$session"
                            :show-chat="true"
                            size="sm"
                        />
                    @endforeach
                </div>
            </div>
            @endif
    </div>
</div>

<!-- Report Edit Modal -->
<x-modals.student-report-edit session-type="interactive" />

<!-- Pre-rendered avatars for modal (using unified x-avatar component) -->
<div id="prerendered_avatars_container" class="hidden">
    @php
        $enrolledStudentsForAvatars = $session->course->enrolledStudents;
    @endphp
    @foreach($enrolledStudentsForAvatars as $studentData)
        @php
            $studentUser = $studentData->student?->user ?? $studentData->student ?? $studentData;
        @endphp
        <div id="prerendered_avatar_{{ $studentUser->id }}" class="hidden">
            <x-avatar :user="$studentUser" size="sm" user-type="student" />
        </div>
    @endforeach
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>
// Session Content Form Submission
document.getElementById('sessionContentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>{{ __('teacher.sessions.common.saving') }}';

    fetch('{{ route("teacher.interactive-sessions.content", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification using unified toast
            if (window.toast) {
                window.toast.success('{{ __('teacher.sessions.common.save_success') }}');
            }
        } else {
            window.toast?.error(data.message || '{{ __('teacher.messages.save_error') }}');
        }

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    })
    .catch(error => {
        window.toast?.error('{{ __('teacher.sessions.common.save_error') }}');

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});

// Get report data for modal
function getReportData(studentId) {
    @php
        // For interactive sessions, find the report for this specific student
        $enrolledStudents = $session->course->enrolledStudents;
    @endphp

    const reports = {
        @foreach($enrolledStudents as $studentData)
            @php
                // Access the actual User through student->user relationship
                $student = $studentData->student?->user ?? $studentData->student ?? $studentData;
                $report = $session->studentReports ? $session->studentReports->where('student_id', $student->id)->first() : null;
            @endphp
            {{ $student->id }}: @if($report)
            {
                id: {{ $report->id ?? 'null' }},
                attendance_status: '{{ $report->attendance_status ?? '' }}',
                manually_evaluated: {{ $report->manually_evaluated ? 'true' : 'false' }},
                attendance_percentage: {{ $report->attendance_percentage ?? 'null' }},
                actual_attendance_minutes: {{ $report->actual_attendance_minutes ?? 'null' }},
                // Simplified to homework_degree only (unified with Academic)
                homework_degree: {{ $report->homework_degree ?? 'null' }},
                notes: `{{ addslashes($report->notes ?? '') }}`
            }
            @else
            null
            @endif,
        @endforeach
    };

    return reports[studentId] || null;
}

// Get student info by ID (includes name, avatar, email)
const studentsInfo = {
    @foreach($enrolledStudents as $studentData)
        @php
            // Access the actual User through student->user relationship
            $student = $studentData->student?->user ?? $studentData->student ?? $studentData;
        @endphp
        {{ $student->id }}: {
            name: '{{ $student->name ?? __('teacher.common.student') }}',
            avatar: '{{ $student->avatar ?? "" }}',
            email: '{{ $student->email ?? "" }}',
            gender: '{{ $student->gender ?? "male" }}'
        },
    @endforeach
};

function getStudentName(studentId) {
    return studentsInfo[studentId]?.name || '{{ __('teacher.common.student') }}';
}

function getStudentData(studentId) {
    return studentsInfo[studentId] || null;
}

// Edit Student Report Function - UPDATED TO USE MODAL
function editStudentReport(studentId, reportId) {
    const reportData = getReportData(studentId);
    const studentName = getStudentName(studentId);
    const studentData = getStudentData(studentId);

    openReportModal(
        {{ $session->id }},
        studentId,
        studentName,
        reportData,
        'interactive',
        studentData
    );
}

// Message Student Function - Uses supervised group chat
function messageStudent(studentId) {
    const teacherId = {{ auth()->id() }};
    const entityType = 'interactive_course';
    const entityId = {{ $session->interactive_course_id ?? 0 }};

    if (!entityId) {
        window.toast?.error('{{ __("chat.chat_unavailable") }}');
        return;
    }

    @if(!auth()->user()->hasSupervisor())
    window.toast?.error('{{ __("chat.teacher_no_supervisor") }}');
    return;
    @endif

    window.location.href = `/chat/start-supervised/${teacherId}/${studentId}/${entityType}/${entityId}`;
}
</script>
</x-slot>

</x-layouts.teacher>
