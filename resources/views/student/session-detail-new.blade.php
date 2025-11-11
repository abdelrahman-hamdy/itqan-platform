@extends('components.layouts.student')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Session Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $session->title ?? 'جلسة القرآن الكريم' }}</h1>
                <p class="text-gray-600">{{ $session->description ?? 'جلسة تعليم القرآن الكريم' }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">تاريخ الجلسة</p>
                <p class="text-lg font-semibold text-gray-900">{{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i') : 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    <!-- Enhanced LiveKit Meeting Interface -->
    <x-meetings.livekit-interface 
        :session="$session" 
        user-type="student"
    />

    <!-- Session Details -->
    <div class="space-y-6">
        <!-- Session Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الجلسة</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">الحالة:</span>
                    <span class="font-medium text-gray-900">
                        <x-sessions.status-display 
                            :session="$session" 
                            variant="text" 
                            size="md" 
                            :show-icon="false" 
                            :show-label="true" />
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">المدة:</span>
                    <span class="font-medium text-gray-900">{{ $session->duration_minutes ?? 60 }} دقيقة</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">نوع الجلسة:</span>
                    <span class="font-medium text-gray-900">
                        @if($session->quran_circle_id)
                            مجموعة
                        @elseif($session->individual_circle_id)
                            فردية
                        @elseif($session->quran_subscription_id)
                            اشتراك
                        @else
                            غير محدد
                        @endif
                    </span>
                </div>
                
                @if($session->teacher_notes)
                <div class="flex flex-col">
                    <span class="text-gray-600 mb-2">ملاحظات المعلم:</span>
                    <div class="bg-gray-50 p-3 rounded-lg text-sm text-gray-800">
                        {{ $session->teacher_notes }}
                    </div>
                </div>
                @endif
                
                @if($session->homework_assigned)
                <div class="flex flex-col">
                    <span class="text-gray-600 mb-2">الواجبات المنزلية:</span>
                    <div class="bg-yellow-50 p-3 rounded-lg text-sm text-yellow-800 border-r-4 border-yellow-400">
                        {{ $session->homework_assigned }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Teacher Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات المعلم</h3>
            
            @if($session->teacher)
            <div class="flex items-center gap-4 p-4 bg-green-50 rounded-lg">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                    {{ substr($session->teacher->name, 0, 1) }}
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 text-lg">{{ $session->teacher->name }}</h4>
                    <p class="text-sm text-green-600 font-medium">معلم القرآن الكريم</p>
                    
                    @if($session->teacher->quranTeacherProfile)
                    <div class="mt-2 space-y-1">
                        @if($session->teacher->quranTeacherProfile->specialization)
                        <p class="text-xs text-gray-600">
                            <span class="font-medium">التخصص:</span>
                            {{ $session->teacher->quranTeacherProfile->specialization }}
                        </p>
                        @endif
                        
                        @if($session->teacher->quranTeacherProfile->experience_years)
                        <p class="text-xs text-gray-600">
                            <span class="font-medium">سنوات الخبرة:</span>
                            {{ $session->teacher->quranTeacherProfile->experience_years }} سنة
                        </p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @else
            <p class="text-gray-500 text-center py-8">لم يتم تحديد معلم لهذه الجلسة</p>
            @endif

            <!-- Other Students in Group Session -->
            @if($session->session_type === 'circle' && $session->circle && $session->circle->students->count() > 1)
            <div class="mt-6">
                <h4 class="font-medium text-gray-900 mb-3">الطلاب الآخرين في المجموعة</h4>
                <div class="space-y-2">
                    @foreach($session->circle->students as $student)
                        @if($student->id !== auth()->id())
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $student->name }}</span>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Session Progress (for completed sessions) -->
    @if($session->status === 'completed' && $session->student_progress)
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">تقييم الأداء</h3>
        <div class="bg-blue-50 p-4 rounded-lg border-r-4 border-blue-400">
            <p class="text-blue-800">{{ $session->student_progress }}</p>
        </div>
    </div>
    @endif

    <!-- Session Instructions (for upcoming sessions) -->
    @if($session->status === 'scheduled')
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
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

    <!-- Quick Actions for Students -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">الإجراءات السريعة</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('student.calendar', ['subdomain' => request()->route('subdomain')]) }}" class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                    <i class="fas fa-calendar"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">الجلسات القادمة</p>
                    <p class="text-sm text-gray-600">عرض جميع الجلسات</p>
                </div>
            </a>
            
            @if($session->homework && $session->homework->count() > 0)
                @if($session->homework->count() == 1)
                    {{-- Single homework - direct link --}}
                    <a href="{{ route('student.homework.show', ['subdomain' => request()->route('subdomain'), 'homework' => $session->homework->first()->id]) }}" class="flex items-center gap-3 p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors duration-200">
                        <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center text-white">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">الواجب</p>
                            <p class="text-sm text-gray-600">مراجعة الواجب</p>
                        </div>
                    </a>
                @else
                    {{-- Multiple homework - general homework page --}}
                    <a href="{{ route('student.homework.index', ['subdomain' => request()->route('subdomain')]) }}" class="flex items-center gap-3 p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors duration-200">
                        <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center text-white relative">
                            <i class="fas fa-tasks"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">{{ $session->homework->count() }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">الواجبات ({{ $session->homework->count() }})</p>
                            <p class="text-sm text-gray-600">مراجعة جميع الواجبات</p>
                        </div>
                    </a>
                @endif
            @elseif($session->homework_assigned)
                {{-- Fallback: homework assigned but no homework records --}}
                <a href="{{ route('student.homework.index', ['subdomain' => request()->route('subdomain')]) }}" class="flex items-center gap-3 p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors duration-200">
                    <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">الواجبات</p>
                        <p class="text-sm text-gray-600">مراجعة الواجبات</p>
                    </div>
                </a>
            @endif
            
            <a href="{{ route('student.progress', ['subdomain' => request()->route('subdomain')]) }}" class="flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors duration-200">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">التقدم</p>
                    <p class="text-sm text-gray-600">متابعة الإنجازات</p>
                </div>
            </a>
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
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 10 && $session->scheduled_at->diffInMinutes(now()) >= 0)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        showNotification('الجلسة ستبدأ خلال {{ $timeData['formatted'] }}', 'info', 8000);
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

@endsection
