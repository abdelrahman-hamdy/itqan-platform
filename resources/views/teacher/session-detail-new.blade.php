@extends('components.layouts.teacher')

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
        user-type="quran_teacher"
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
                
                @if($session->meeting_room_name)
                <div class="flex justify-between">
                    <span class="text-gray-600">اسم الغرفة:</span>
                    <span class="font-medium text-gray-900 font-mono text-sm">{{ $session->meeting_room_name }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Participants -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">المشاركون</h3>
            <div class="space-y-3">
                @if($session->teacher)
                <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                        {{ substr($session->teacher->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $session->teacher->name }}</p>
                        <p class="text-sm text-blue-600">المعلم</p>
                    </div>
                </div>
                @endif
                
                @if($session->students && $session->students->count() > 0)
                    @foreach($session->students->take(5) as $student)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $student->name }}</p>
                            <p class="text-sm text-gray-600">طالب</p>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($session->students->count() > 5)
                    <p class="text-sm text-gray-500 text-center">و {{ $session->students->count() - 5 }} طالب آخر</p>
                    @endif
                @else
                    <p class="text-gray-500 text-center">لا يوجد طلاب مسجلين</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Teacher Notes Section -->
    @if(auth()->user()->user_type === 'quran_teacher')
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ملاحظات المعلم</h3>
        <form id="notesForm" class="space-y-4">
            @csrf
            <div>
                <label for="teacher_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    ملاحظات الجلسة
                </label>
                <textarea 
                    id="teacher_notes" 
                    name="teacher_notes" 
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="أضف ملاحظاتك حول الجلسة..."
                >{{ $session->teacher_notes ?? '' }}</textarea>
            </div>
            
            <div>
                <label for="student_progress" class="block text-sm font-medium text-gray-700 mb-2">
                    تقدم الطالب
                </label>
                <textarea 
                    id="student_progress" 
                    name="student_progress" 
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="سجل تقدم الطالب في هذه الجلسة..."
                >{{ $session->student_progress ?? '' }}</textarea>
            </div>
            
            <div>
                <label for="homework_assigned" class="block text-sm font-medium text-gray-700 mb-2">
                    الواجبات المنزلية
                </label>
                <textarea 
                    id="homework_assigned" 
                    name="homework_assigned" 
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="حدد الواجبات المنزلية للطالب..."
                >{{ $session->homework_assigned ?? '' }}</textarea>
            </div>
            
            <div class="flex gap-3">
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors duration-200"
                >
                    حفظ الملاحظات
                </button>
                
                @if($session->status !== 'completed')
                <button 
                    type="button" 
                    id="completeSessionBtn"
                    class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-colors duration-200"
                >
                    إنهاء الجلسة
                </button>
                @endif
            </div>
        </form>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Teacher notes functionality
document.addEventListener('DOMContentLoaded', function() {
    const notesForm = document.getElementById('notesForm');
    const completeSessionBtn = document.getElementById('completeSessionBtn');
    
    if (notesForm) {
        notesForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(notesForm);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch(`{{ route('teacher.sessions.update-notes', ['subdomain' => request()->route('subdomain'), 'sessionId' => $session->id]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data._token
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('تم حفظ الملاحظات بنجاح', 'success');
                } else {
                    showNotification('فشل في حفظ الملاحظات', 'error');
                }
            } catch (error) {
                console.error('Error saving notes:', error);
                showNotification('حدث خطأ أثناء حفظ الملاحظات', 'error');
            }
        });
    }
    
    if (completeSessionBtn) {
        completeSessionBtn.addEventListener('click', async function() {
            if (!confirm('هل أنت متأكد من إنهاء هذه الجلسة؟')) {
                return;
            }
            
            try {
                const response = await fetch(`{{ route('teacher.sessions.complete', ['subdomain' => request()->route('subdomain'), 'sessionId' => $session->id]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('تم إنهاء الجلسة بنجاح', 'success');
                    completeSessionBtn.style.display = 'none';
                    
                    // Refresh page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification('فشل في إنهاء الجلسة', 'error');
                }
            } catch (error) {
                console.error('Error completing session:', error);
                showNotification('حدث خطأ أثناء إنهاء الجلسة', 'error');
            }
        });
    }
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
