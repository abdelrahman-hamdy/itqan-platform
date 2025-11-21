<x-layouts.teacher
    :title="($session->title ?? 'جلسة أكاديمية') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الجلسة الأكاديمية مع ' . ($session->student->name ?? 'الطالب')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الصفحة الرئيسية</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.academic-sessions.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الجلسات الأكاديمية</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $session->title ?? 'جلسة أكاديمية' }}</li>
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
        @if($viewType === 'teacher')
            <!-- Session Evaluation Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-file-text-line text-primary ml-2"></i>
                    تقييم الجلسة
                </h3>

                <form id="sessionEvaluationForm" class="space-y-4">
                    @csrf
                    <div>
                        <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-2">
                            محتوى الدرس
                        </label>
                        <textarea
                            id="lesson_content"
                            name="lesson_content"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                            placeholder="ما هي المواضيع التي تم تغطيتها؟">{{ $session->lesson_content ?? '' }}</textarea>
                    </div>

                    <div>
                        <label for="teacher_feedback" class="block text-sm font-medium text-gray-700 mb-2">
                            ملاحظات على أداء الطالب
                        </label>
                        <textarea
                            id="teacher_feedback"
                            name="teacher_feedback"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                            placeholder="ملاحظاتك على الطالب...">{{ $session->teacher_feedback ?? '' }}</textarea>
                    </div>

                    <button
                        type="submit"
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                        <i class="ri-save-line ml-2"></i>
                        حفظ التقييم
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">الطالب</h3>

                    <x-sessions.student-item
                        :student="$session->student"
                        :session="$session"
                        :show-chat="true"
                        size="md"
                    />
                </div>
            @endif
        @endif

        <!-- Session Instructions (for upcoming sessions) -->
        @if($session->status === 'scheduled')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تعليمات الجلسة</h3>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center mt-1">
                            <i class="fas fa-info text-white text-xs"></i>
                        </div>
                        <div class="text-blue-800">
                            <p class="font-medium mb-2">نصائح للاستعداد للجلسة:</p>
                            <ul class="space-y-1 text-sm">
                                <li>• تأكد من جودة اتصال الإنترنت</li>
                                <li>• اختبر الكاميرا والميكروفون قبل بدء الجلسة</li>
                                <li>• حضّر المواد التعليمية المطلوبة</li>
                                <li>• اختر مكاناً هادئاً للجلسة</li>
                                <li>• كن مستعداً قبل الموعد بـ 5 دقائق</li>
                            </ul>
                        </div>
                    </div>
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
        homework_completion_degree: {{ $report->homework_completion_degree ?? 'null' }},
        notes: `{{ addslashes($report->notes ?? '') }}`
    };
    @else
    return null;
    @endif
}

// Session Evaluation Form Submission
document.getElementById('sessionEvaluationForm')?.addEventListener('submit', function(e) {
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
                    <span>تم حفظ التقييم بنجاح</span>
                </div>
            `;
            this.appendChild(successMsg);

            setTimeout(() => successMsg.remove(), 3000);
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ التقييم');
        }

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ التقييم');

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});

// Edit Student Report Function - UPDATED TO USE MODAL
function editStudentReport(studentId, reportId) {
    const reportData = getReportData(studentId);
    const studentName = '{{ $session->student->name ?? "الطالب" }}';

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
