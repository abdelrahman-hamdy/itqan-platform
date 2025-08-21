@extends('components.layouts.student')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Session Information Card -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-start">
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

    <!-- Session Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الجلسة</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">المدة:</span>
                    <span class="font-medium">{{ $session->duration_minutes ?? 60 }} دقيقة</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">الحالة:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($session->status === 'scheduled') bg-blue-100 text-blue-800
                        @elseif($session->status === 'ongoing') bg-green-100 text-green-800
                        @elseif($session->status === 'completed') bg-gray-100 text-gray-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($session->status->value ?? $session->status) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">نوع الجلسة:</span>
                    <span class="font-medium">
                        @if($session->circle_id)
                            جماعية
                        @elseif($session->individual_circle_id)
                            فردية
                        @elseif($session->quran_subscription_id)
                            اشتراك
                        @else
                            غير محدد
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Teacher Info -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات المعلم</h3>
            <div class="space-y-3">
                @if($session->quranTeacher)
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                            {{ substr($session->quranTeacher->first_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $session->quranTeacher->full_name }}</p>
                            <p class="text-sm text-gray-500">معلم القرآن الكريم</p>
                            @if($session->quranTeacher->email)
                                <p class="text-sm text-gray-500">{{ $session->quranTeacher->email }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">لا توجد معلومات المعلم</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Meeting Section -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">الاجتماع المرئي</h3>
        
        @if($session->meeting_room_name)
            <!-- Meeting exists -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-video text-green-600 text-xl mr-3"></i>
                    <div>
                        <p class="text-green-800 font-medium">الاجتماع متاح</p>
                        <p class="text-green-600 text-sm">يمكنك الانضمام للجلسة الآن</p>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4 space-x-reverse">
                <a href="{{ route('meetings.join-iframe', $session->id) }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-video mr-2"></i>
                    انضم للجلسة
                </a>
                
                <button onclick="copyMeetingLink('{{ route('meetings.join-iframe', $session->id) }}')" 
                        class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-link mr-2"></i>
                    نسخ رابط الجلسة
                </button>
            </div>
        @else
            <!-- No meeting yet -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-600 text-xl mr-3"></i>
                    <div>
                        <p class="text-yellow-800 font-medium">في انتظار بدء الجلسة</p>
                        <p class="text-yellow-600 text-sm">يرجى انتظار المعلم لبدء الجلسة</p>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4 space-x-reverse">
                <button onclick="checkMeetingStatus()" 
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-refresh mr-2"></i>
                    التحقق من حالة الجلسة
                </button>
            </div>
        @endif
    </div>

    <!-- Session Notes -->
    @if($session->status === 'completed' && $session->notes)
        <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ملاحظات الجلسة</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700">{{ $session->notes }}</p>
            </div>
        </div>
    @endif

    <!-- Navigation -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
        <div class="flex space-x-4 space-x-reverse">
            <a href="{{ route('student.profile') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-right mr-2"></i>
                العودة للملف الشخصي
            </a>
            
            @if($session->circle_id)
                <a href="{{ route('student.circles.show', $session->circle_id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-users mr-2"></i>
                    عرض الحلقة
                </a>
            @endif
        </div>
    </div>
</div>

<script>
function copyMeetingLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        showNotification('تم نسخ رابط الجلسة', 'success');
    }, function(err) {
        console.error('Could not copy meeting link: ', err);
        showNotification('فشل في نسخ الرابط', 'error');
    });
}

function checkMeetingStatus() {
    showNotification('جاري التحقق من حالة الجلسة...', 'info');
    
    // Reload page to check for meeting updates
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white z-50 transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
</script>
@endsection
