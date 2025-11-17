@extends('components.layouts.student')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@section('content')
<div>
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li><a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain')]) }}" class="hover:text-primary">ملفي الشخصي</a></li>
                <li>/</li>
                @if($session->circle_id && $session->circle)
                    <li><a href="{{ route('student.quran-circles', ['subdomain' => request()->route('subdomain')]) }}" class="hover:text-primary">حلقات القرآن</a></li>
                    <li>/</li>
                    <li><a href="{{ route('student.circles.show', ['subdomain' => request()->route('subdomain'), 'circleId' => $session->circle->id]) }}" class="hover:text-primary">{{ $session->circle->name ?? 'الحلقة' }}</a></li>
                @elseif($session->individual_circle_id && $session->individualCircle)
                    <li><a href="{{ route('student.quran-teachers', ['subdomain' => request()->route('subdomain')]) }}" class="hover:text-primary">معلمي القرآن</a></li>
                    <li>/</li>
                    <li><a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->individualCircle->id]) }}" class="hover:text-primary">{{ $session->individualCircle->subscription->package->name ?? 'الحلقة الفردية' }}</a></li>
                @else
                    <li><a href="{{ route('student.dashboard', ['subdomain' => request()->route('subdomain')]) }}" class="hover:text-primary">لوحة التحكم</a></li>
                @endif
                <li>/</li>
                <li class="text-gray-900">{{ $session->title ?? 'تفاصيل الجلسة' }}</li>
            </ol>
        </nav>

        <div class="space-y-6">
                <!-- Session Header -->
                <x-sessions.session-header :session="$session" view-type="student" />

                <!-- Trial Session Information (only for trial sessions) -->
                @if($session->session_type === 'trial' && $session->trialRequest)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-sm border border-green-200 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-gift text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-green-900 mb-2">
                                <i class="fas fa-star text-yellow-500 ml-1"></i>
                                جلسة تجريبية مجانية
                            </h3>
                            <p class="text-green-800 mb-3">
                                هذه جلسة تجريبية مجانية مدتها 30 دقيقة للتعرف على المعلم وتقييم مستواك في القرآن الكريم.
                            </p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-green-900">المستوى المُدخل:</span>
                                    <span class="text-green-700">{{ $session->trialRequest->level_label }}</span>
                                </div>
                                @if($session->trialRequest->learning_goals && count($session->trialRequest->learning_goals) > 0)
                                <div>
                                    <span class="font-medium text-green-900">الأهداف:</span>
                                    <span class="text-green-700">
                                        @php
                                            $goals = [
                                                'reading' => 'القراءة',
                                                'tajweed' => 'التجويد',
                                                'memorization' => 'الحفظ',
                                                'improvement' => 'التحسين'
                                            ];
                                            $goalLabels = collect($session->trialRequest->learning_goals)->map(fn($g) => $goals[$g] ?? $g);
                                        @endphp
                                        {{ $goalLabels->join('، ') }}
                                    </span>
                                </div>
                                @endif
                            </div>
                            @if($session->trialRequest->notes)
                            <div class="mt-3 p-3 bg-white/50 rounded-lg">
                                <span class="font-medium text-green-900 block mb-1">ملاحظاتك:</span>
                                <p class="text-green-700 text-sm">{{ $session->trialRequest->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Enhanced LiveKit Meeting Interface -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <x-meetings.livekit-interface
                        :session="$session"
                        user-type="student"
                    />
                </div>

                <!-- Homework Section -->
                @if($session->homework && $session->homework->count() > 0)
                <x-sessions.homework-display 
                    :session="$session" 
                    :homework="$session->homework" 
                    view-type="student" />
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
                                    <li>• أحضر المصحف أو افتح تطبيق القرآن الكريم</li>
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
</div>

@push('scripts')
<script>
// Student-specific functionality
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
@endpush

</div>
@endsection
