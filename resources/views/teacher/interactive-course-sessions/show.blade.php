<x-layouts.teacher
    :title="($session->title ?? 'جلسة تفاعلية رقم ' . $session->session_number) . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الجلسة التفاعلية - ' . ($session->course->title ?? 'كورس تفاعلي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الصفحة الرئيسية</a></li>
            <li>/</li>
            <li><a href="{{ route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $session->course->id]) }}" class="hover:text-primary">{{ $session->course->title }}</a></li>
            <li>/</li>
            <li class="text-gray-900">جلسة رقم {{ $session->session_number }}</li>
        </ol>
    </nav>

    <div class="space-y-6">
        <!-- Session Header -->
        <x-sessions.session-header :session="$session" view-type="teacher" />

        <!-- Enhanced LiveKit Meeting Interface -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <x-meetings.livekit-interface
                :session="$session"
                user-type="teacher"
            />
        </div>

        <!-- Session Content & Evaluation (for teachers) -->
        <!-- Always show management sections for teachers regardless of session status -->

            <!-- Session Content Management -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-file-text-line text-primary ml-2"></i>
                    محتوى وتقييم الجلسة
                </h3>

                <form id="sessionContentForm" class="space-y-4">
                    @csrf
                    <div>
                        <label for="learning_outcomes" class="block text-sm font-medium text-gray-700 mb-2">
                            نواتج التعلم
                        </label>
                        <textarea
                            id="learning_outcomes"
                            name="learning_outcomes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                            placeholder="ما هي نواتج التعلم التي تم تحقيقها؟">{{ $session->learning_outcomes ?? '' }}</textarea>
                    </div>

                    <div>
                        <label for="teacher_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            ملاحظات على الجلسة
                        </label>
                        <textarea
                            id="teacher_notes"
                            name="teacher_notes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                            placeholder="ملاحظاتك على سير الجلسة...">{{ $session->teacher_notes ?? '' }}</textarea>
                    </div>

                    <button
                        type="submit"
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                        <i class="ri-save-line ml-2"></i>
                        حفظ المحتوى
                    </button>
                </form>
            </div>

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
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-group-line text-primary ml-2"></i>
                    قائمة الطلاب ({{ $studentCount }} طالب)
                </h3>

                <div class="grid grid-cols-1 gap-4">
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
<x-modals.student-report-edit session-type="academic" />

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
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ الحفظ...';

    fetch('{{ route("teacher.interactive-sessions.content", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'bg-green-50 border border-green-200 rounded-lg p-4 mt-4';
            successMsg.innerHTML = `
                <div class="flex items-center gap-2 text-green-800">
                    <i class="ri-check-line text-green-600"></i>
                    <span>تم حفظ المحتوى بنجاح</span>
                </div>
            `;
            this.appendChild(successMsg);

            setTimeout(() => successMsg.remove(), 3000);
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ المحتوى');
        }

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ المحتوى');

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
                homework_completion_degree: {{ $report->homework_completion_degree ?? 'null' }},
                notes: `{{ addslashes($report->notes ?? '') }}`
            }
            @else
            null
            @endif,
        @endforeach
    };

    return reports[studentId] || null;
}

// Get student name by ID
function getStudentName(studentId) {
    const students = {
        @foreach($enrolledStudents as $studentData)
            @php
                // Access the actual User through student->user relationship
                $student = $studentData->student?->user ?? $studentData->student ?? $studentData;
            @endphp
            {{ $student->id }}: '{{ $student->name ?? "طالب" }}',
        @endforeach
    };

    return students[studentId] || 'الطالب';
}

// Edit Student Report Function - UPDATED TO USE MODAL
function editStudentReport(studentId, reportId) {
    const reportData = getReportData(studentId);
    const studentName = getStudentName(studentId);

    openReportModal(
        {{ $session->id }},
        studentId,
        studentName,
        reportData,
        'academic'
    );
}

// Message Student Function
function messageStudent(studentId) {
    // Open chat with student - this would integrate with your chat system
    console.log('Opening chat with student:', studentId);
    alert('ميزة المراسلة قيد التطوير');
    // TODO: Integrate with WireChat or your chat system
}
</script>
</x-slot>

</x-layouts.teacher>
