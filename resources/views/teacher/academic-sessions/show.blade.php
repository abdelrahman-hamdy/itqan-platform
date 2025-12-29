<x-layouts.teacher
    :title="($session->title ?? 'جلسة أكاديمية') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الجلسة الأكاديمية مع ' . ($session->student->name ?? 'الطالب')">

<div>
    <!-- Breadcrumb -->
    @php
        $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
        $breadcrumbItems = [
            ['label' => 'الدروس الخاصة', 'route' => route('teacher.academic.lessons.index', ['subdomain' => $subdomain])],
        ];
        if($session->academicSubscription) {
            $breadcrumbItems[] = ['label' => $session->academicSubscription->student->name ?? 'الطالب', 'route' => route('teacher.academic.lessons.show', ['subdomain' => $subdomain, 'subscription' => $session->academicSubscription->id]), 'truncate' => true];
        }
        $breadcrumbItems[] = ['label' => $session->title ?? 'جلسة أكاديمية', 'truncate' => true];
    @endphp
    <x-ui.breadcrumb :items="$breadcrumbItems" view-type="teacher" />

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

        <!-- Session Content Management (for teachers) -->
        @if($viewType === 'teacher')
            <!-- Session Content Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">
                    <i class="ri-file-text-line text-primary ml-2"></i>
                    محتوى الجلسة
                </h3>

                <form id="sessionContentForm" class="space-y-3 md:space-y-4">
                    @csrf
                    <div>
                        <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">
                            محتوى الدرس
                        </label>
                        <textarea
                            id="lesson_content"
                            name="lesson_content"
                            rows="4"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm md:text-base focus:ring-primary focus:border-primary"
                            placeholder="ما هي المواضيع التي تم تغطيتها في هذه الجلسة؟">{{ $session->lesson_content ?? '' }}</textarea>
                    </div>

                    <p class="text-xs md:text-sm text-gray-500">
                        <i class="ri-information-line ml-1"></i>
                        لإضافة ملاحظات على أداء الطالب، استخدم تقرير الجلسة المنفصل
                    </p>

                    <button
                        type="submit"
                        class="min-h-[44px] bg-primary text-white px-4 md:px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors text-sm md:text-base">
                        <i class="ri-save-line ml-2"></i>
                        حفظ محتوى الدرس
                    </button>
                </form>
            </div>

            <!-- Homework Management Component -->
            <x-sessions.academic-homework-management
                :session="$session"
                view-type="teacher"
                session-type="academic"
            />

            <!-- Student Section (Individual sessions only) -->
            @if($session->session_type === 'individual' && $session->student)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">الطالب</h3>

                    <x-sessions.student-item
                        :student="$session->student"
                        :session="$session"
                        :show-chat="true"
                        size="md"
                    />
                </div>
            @endif
        @endif

    </div>
</div>

<!-- Report Edit Modal -->
<x-modals.student-report-edit session-type="academic" />

<!-- Pre-rendered avatars for modal (using unified x-avatar component) -->
<div id="prerendered_avatars_container" class="hidden">
    @if($session->student)
        <div id="prerendered_avatar_{{ $session->student->id }}" class="hidden">
            <x-avatar :user="$session->student" size="sm" user-type="student" />
        </div>
    @endif
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>
// Get report data for modal
function getReportData(studentId) {
    @php
        $report = $session->studentReports ? $session->studentReports->where('student_id', $session->student_id)->first() : null;
    @endphp

    @if($report)
    return {
        id: {{ $report->id ?? 'null' }},
        attendance_status: '{{ $report->attendance_status ?? '' }}',
        manually_evaluated: {{ $report->manually_evaluated ? 'true' : 'false' }},
        attendance_percentage: {{ $report->attendance_percentage ?? 'null' }},
        actual_attendance_minutes: {{ $report->actual_attendance_minutes ?? 'null' }},
        // Academic-specific fields - Simplified to homework_degree only
        homework_degree: {{ $report->homework_degree ?? 'null' }},
        notes: `{{ addslashes($report->notes ?? '') }}`
    };
    @else
    return null;
    @endif
}

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

    fetch('{{ route("teacher.academic-sessions.evaluation", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => $session->id]) }}', {
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
            // Show success notification (toast style)
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            notification.innerHTML = '<i class="ri-check-line"></i><span>تم حفظ محتوى الدرس بنجاح</span>';
            document.body.appendChild(notification);

            setTimeout(() => notification.remove(), 3000);
        } else {
            window.toast?.error(data.message || 'حدث خطأ أثناء الحفظ');
        }

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    })
    .catch(error => {
        window.toast?.error('حدث خطأ أثناء حفظ محتوى الدرس');

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});

// Edit Student Report Function - UPDATED TO USE MODAL
function editStudentReport(studentId, reportId) {
    const reportData = getReportData(studentId);
    const studentName = '{{ $session->student->name ?? "الطالب" }}';

    // Student data for avatar display
    const studentData = {
        avatar: '{{ $session->student->avatar ?? "" }}',
        email: '{{ $session->student->email ?? "" }}',
        gender: '{{ $session->student->gender ?? "male" }}'
    };

    openReportModal(
        {{ $session->id }},
        studentId,
        studentName,
        reportData,
        'academic',
        studentData
    );
}

// Message Student Function
function messageStudent(studentId) {
    // Navigate to WireChat - opens chats page where teacher can search for and message the student
    // The user's name will be searchable in the chat interface
    window.open('/chats', '_blank');
}
</script>
</x-slot>

</x-layouts.teacher>
