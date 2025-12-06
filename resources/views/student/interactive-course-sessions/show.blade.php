<x-layouts.student
    :title="($session->title ?? 'جلسة تفاعلية رقم ' . $session->session_number) . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الجلسة التفاعلية - ' . ($session->course->title ?? 'كورس تفاعلي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">لوحة التحكم</a></li>
            <li>/</li>
            <li><a href="{{ route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">كورساتي التفاعلية</a></li>
            <li>/</li>
            <li><a href="{{ route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'courseId' => $session->course->id]) }}" class="hover:text-primary">{{ $session->course->title }}</a></li>
            <li>/</li>
            <li class="text-gray-900">جلسة رقم {{ $session->session_number }}</li>
        </ol>
    </nav>

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

        {{-- Session Content Section --}}
        @if($session->lesson_content)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-file-text-line text-primary ml-2"></i>
                    محتوى الجلسة
                </h3>

                <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-4">
                    {!! nl2br(e($session->lesson_content)) !!}
                </div>
            </div>
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
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-message-line text-primary ml-2"></i>
                    تقييمك للجلسة
                </h3>

                @if($session->student_feedback)
                    <!-- Display Existing Feedback -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-line text-green-600 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-green-900 mb-2">تم إرسال التقييم</h4>
                                <p class="text-green-800">{{ $session->student_feedback }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Feedback Form -->
                    <form id="feedbackForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="feedback_text" class="block text-sm font-medium text-gray-700 mb-2">
                                ملاحظاتك على الجلسة
                            </label>
                            <textarea
                                id="feedback_text"
                                name="feedback"
                                rows="4"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="شاركنا رأيك في الجلسة..."
                                required></textarea>
                        </div>
                        <button
                            type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="ri-send-plane-line ml-2"></i>
                            إرسال التقييم
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
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ الإرسال...';

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
                    <span>تم إرسال تقييمك بنجاح</span>
                </div>
            `;
            this.appendChild(successMsg);

            setTimeout(() => window.location.reload(), 1500);
        } else {
            alert(data.message || 'حدث خطأ أثناء إرسال التقييم');
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إرسال التقييم');
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});
</script>
</x-slot>

</x-layouts.student>
