@extends('components.layouts.teacher')

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

        <!-- Student Info -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الطالب</h3>
            <div class="space-y-3">
                @if($session->student)
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ substr($session->student->first_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $session->student->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $session->student->email }}</p>
                        </div>
                    </div>
                @elseif($session->circle && $session->circle->students->count() > 0)
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600">عدد الطلاب: {{ $session->circle->students->count() }}</p>
                        @foreach($session->circle->students->take(3) as $student)
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm">
                                    {{ substr($student->first_name, 0, 1) }}
                                </div>
                                <span class="text-sm">{{ $student->full_name }}</span>
                            </div>
                        @endforeach
                        @if($session->circle->students->count() > 3)
                            <p class="text-sm text-gray-500">و {{ $session->circle->students->count() - 3 }} آخرين...</p>
                        @endif
                    </div>
                @else
                    <p class="text-gray-500">لا توجد معلومات طالب</p>
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
                        <p class="text-green-800 font-medium">الاجتماع جاهز</p>
                        <p class="text-green-600 text-sm">غرفة الاجتماع: {{ $session->meeting_room_name }}</p>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4 space-x-reverse">
                <a href="{{ route('meetings.join-iframe', $session->id) }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-video mr-2"></i>
                    انضم للاجتماع
                </a>
                
                <button onclick="copyMeetingLink('{{ route('meetings.join-iframe', $session->id) }}')" 
                        class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-link mr-2"></i>
                    نسخ رابط الاجتماع
                </button>
            </div>
        @else
            <!-- No meeting yet -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3"></i>
                    <div>
                        <p class="text-yellow-800 font-medium">لم يتم إنشاء الاجتماع بعد</p>
                        <p class="text-yellow-600 text-sm">انقر على "إنشاء اجتماع" لبدء الجلسة</p>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('meetings.join-iframe', $session->id) }}" method="GET" class="inline-block">
                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-video mr-2"></i>
                    إنشاء وانضم للاجتماع
                </button>
            </form>
        @endif
    </div>

    <!-- Session Actions -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">إجراءات الجلسة</h3>
        <div class="flex space-x-4 space-x-reverse">
            @if($session->status !== 'completed')
                <form action="{{ route('teacher.sessions.complete', $session->id) }}" method="POST" class="inline-block">
                    @csrf
                    @method('PUT')
                    <button type="submit" 
                            onclick="return confirm('هل أنت متأكد من إنهاء الجلسة؟')"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>
                        إنهاء الجلسة
                    </button>
                </form>
                
                <form action="{{ route('teacher.sessions.cancel', $session->id) }}" method="POST" class="inline-block">
                    @csrf
                    @method('PUT')
                    <button type="submit" 
                            onclick="return confirm('هل أنت متأكد من إلغاء الجلسة؟')"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        إلغاء الجلسة
                    </button>
                </form>
            @endif
            
            <a href="{{ route('teacher.calendar') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-right mr-2"></i>
                العودة للتقويم
            </a>
        </div>
    </div>
</div>

<script>
function copyMeetingLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        showNotification('تم نسخ رابط الاجتماع', 'success');
    }, function(err) {
        console.error('Could not copy meeting link: ', err);
        showNotification('فشل في نسخ الرابط', 'error');
    });
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
