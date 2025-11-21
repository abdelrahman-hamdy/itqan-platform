<x-layouts.student 
    :title="($session->title ?? 'جلسة أكاديمية') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الجلسة الأكاديمية مع ' . ($session->academicTeacher->full_name ?? 'المعلم الأكاديمي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الملف الشخصي</a></li>
            <li>/</li>
            <li><a href="{{ route('student.academic-teachers', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">المعلمون الأكاديميون</a></li>
            <li>/</li>
            @if($session->academicSubscription)
            <li><a href="{{ route('student.academic-subscriptions.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $session->academicSubscription->id]) }}" class="hover:text-primary">{{ $session->academicSubscription->subject_name ?? 'درس أكاديمي' }}</a></li>
            <li>/</li>
            @endif
            <li class="text-gray-900">{{ $session->title ?? 'جلسة أكاديمية' }}</li>
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

            <!-- Session Progress & Content -->
            @if($session->status === 'completed')
                <!-- Academic Homework Display -->
                <x-sessions.homework-display 
                    :session="$session" 
                    view-type="student" 
                    session-type="academic" />
                
                <!-- Session Content Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-file-text-line text-primary ml-2"></i>
                        ملخص الجلسة
                    </h3>
                    
                    @if($session->lesson_content)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">محتوى الدرس:</h4>
                            <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                                {{ $session->lesson_content }}
                            </div>
                        </div>
                    @endif

                    @if($session->learning_outcomes)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">نواتج التعلم:</h4>
                            <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                                {{ $session->learning_outcomes }}
                            </div>
                        </div>
                    @endif

                    @if($session->teacher_feedback)
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">ملاحظات المعلم:</h4>
                            <div class="text-gray-700 bg-blue-50 rounded-lg p-4">
                                {{ $session->teacher_feedback }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Student Feedback Section -->
            @if($session->status === 'completed')
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-message-line text-primary ml-2"></i>
                        تقييمك للجلسة
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
                                    شاركنا رأيك في الجلسة
                                </label>
                                <textarea 
                                    id="feedback" 
                                    name="feedback" 
                                    rows="4" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                    placeholder="كيف كانت الجلسة؟ ما الذي أعجبك؟ ما المقترحات للتحسين؟"></textarea>
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
                                    <li>• أحضر المواد الدراسية المطلوبة</li>
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

<!-- Scripts -->
<x-slot name="scripts">
<script>


// Feedback form submission
document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const feedback = document.getElementById('feedback').value.trim();
    if (!feedback) {
        alert('الرجاء كتابة تقييمك للجلسة');
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
            // Replace form with submitted feedback
            document.querySelector('#feedbackForm').parentElement.innerHTML = `
                <div class="mb-4">
                    <div class="text-gray-700 bg-gray-50 rounded-lg p-4">
                        ${feedback}
                    </div>
                </div>
                <div class="text-sm text-green-600">
                    <i class="ri-check-line ml-1"></i>
                    تم إرسال تقييمك بنجاح
                </div>
            `;
        } else {
            alert('حدث خطأ أثناء إرسال التقييم. الرجاء المحاولة مرة أخرى.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إرسال التقييم. الرجاء المحاولة مرة أخرى.');
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
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ التسليم...';

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
            
            let fileLink = '';
            if (file && file.size > 0 && data.data.file_path) {
                fileLink = `
                    <a href="/storage/${data.data.file_path}" 
                       target="_blank"
                       class="inline-flex items-center text-green-600 hover:text-green-800 text-sm">
                        <i class="ri-attachment-line ml-1"></i>
                        الملف المرفق
                    </a>
                `;
            }
            
            this.parentElement.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="ri-check-circle-line text-green-600 ml-2"></i>
                        <span class="font-medium text-green-800">تم تسليم الواجب</span>
                    </div>
                    <div class="text-sm text-green-700 mb-3">
                        ${submission}
                    </div>
                    ${fileLink}
                </div>
            `;
        } else {
            alert(data.message || 'حدث خطأ أثناء تسليم الواجب. الرجاء المحاولة مرة أخرى.');
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تسليم الواجب. الرجاء المحاولة مرة أخرى.');
        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('Academic session detail page loaded');
    
    // Auto-scroll to meeting if session is starting soon
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 5 && $session->scheduled_at->diffInMinutes(now()) >= -5)
        setTimeout(() => {
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1000);
    @endif
    
    // Show notification if session is starting soon
    @if($session->scheduled_at && $session->scheduled_at->isFuture() && $session->scheduled_at->diffInMinutes(now()) <= 10)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        @if(!$timeData['is_past'])
            showNotification('الجلسة ستبدأ خلال {{ $timeData['formatted'] }}', 'info', 8000);
        @endif
    @endif
});

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-sm z-50 transform translate-x-full transition-transform duration-300`;
    
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-70">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}
</script>
</x-slot>

</x-layouts.student>