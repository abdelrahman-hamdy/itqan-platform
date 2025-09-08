@props([
    'subscription',
    'viewType' => 'student'
])

@php
    $isTeacher = $viewType === 'teacher';
    $student = $subscription->student;
@endphp

<!-- Academic Subscription Quick Actions -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ri-flashlight-line text-purple-600 ml-2"></i>
            إجراءات سريعة
        </h3>
    </div>

    <div class="space-y-3">
        @if($isTeacher)
            <!-- Teacher Actions -->
            
            <!-- Schedule New Session -->
            <button onclick="scheduleNewSession({{ $subscription->id }})" 
                    class="w-full flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-calendar-event-line text-blue-600 ml-3"></i>
                    <span class="text-sm font-medium text-blue-800">جدولة جلسة جديدة</span>
                </div>
                <i class="ri-arrow-left-s-line text-blue-600 group-hover:text-blue-700"></i>
            </button>

            <!-- Create Homework -->
            <button onclick="createHomework({{ $subscription->id }})" 
                    class="w-full flex items-center justify-between p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-book-2-line text-orange-600 ml-3"></i>
                    <span class="text-sm font-medium text-orange-800">إنشاء واجب منزلي</span>
                </div>
                <i class="ri-arrow-left-s-line text-orange-600 group-hover:text-orange-700"></i>
            </button>

            <!-- Student Progress Report -->
            <a href="{{ route('teacher.academic.lessons.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'lesson' => $subscription->id]) }}?tab=progress" 
               class="w-full flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-line-chart-line text-green-600 ml-3"></i>
                    <span class="text-sm font-medium text-green-800">تقرير التقدم</span>
                </div>
                <i class="ri-arrow-left-s-line text-green-600 group-hover:text-green-700"></i>
            </a>

            <!-- Contact Student -->
            <a href="{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}?user={{ $student->id }}" 
               class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-chat-1-line text-gray-600 ml-3"></i>
                    <span class="text-sm font-medium text-gray-800">راسل الطالب</span>
                </div>
                <i class="ri-arrow-left-s-line text-gray-600 group-hover:text-gray-700"></i>
            </a>

            <!-- Subscription Settings -->
            <button onclick="editSubscriptionSettings({{ $subscription->id }})" 
                    class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-settings-3-line text-gray-600 ml-3"></i>
                    <span class="text-sm font-medium text-gray-800">إعدادات الاشتراك</span>
                </div>
                <i class="ri-arrow-left-s-line text-gray-600 group-hover:text-gray-700"></i>
            </button>

        @else
            <!-- Student Actions -->
            
            <!-- View Homework -->
            <a href="{{ route('student.homework.index', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}?subscription={{ $subscription->id }}" 
               class="w-full flex items-center justify-between p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-book-2-line text-orange-600 ml-3"></i>
                    <span class="text-sm font-medium text-orange-800">عرض الواجبات</span>
                </div>
                <i class="ri-arrow-left-s-line text-orange-600 group-hover:text-orange-700"></i>
            </a>

            <!-- Request Reschedule -->
            <button onclick="requestReschedule({{ $subscription->id }})" 
                    class="w-full flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-calendar-event-line text-blue-600 ml-3"></i>
                    <span class="text-sm font-medium text-blue-800">طلب إعادة جدولة</span>
                </div>
                <i class="ri-arrow-left-s-line text-blue-600 group-hover:text-blue-700"></i>
            </button>

            <!-- Contact Teacher -->
            <a href="{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}?user={{ $subscription->teacher->user_id ?? $subscription->teacher_id }}" 
               class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-chat-1-line text-gray-600 ml-3"></i>
                    <span class="text-sm font-medium text-gray-800">راسل المعلم</span>
                </div>
                <i class="ri-arrow-left-s-line text-gray-600 group-hover:text-gray-700"></i>
            </a>

            <!-- View Progress -->
            <a href="{{ route('student.progress.academic', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}?subscription={{ $subscription->id }}" 
               class="w-full flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors group">
                <div class="flex items-center">
                    <i class="ri-line-chart-line text-green-600 ml-3"></i>
                    <span class="text-sm font-medium text-green-800">متابعة التقدم</span>
                </div>
                <i class="ri-arrow-left-s-line text-green-600 group-hover:text-green-700"></i>
            </a>
        @endif
    </div>
</div>

<script>
@if($isTeacher)
function scheduleNewSession(subscriptionId) {
    // This will be implemented when session scheduling is ready
    alert('سيتم تنفيذ جدولة الجلسات قريباً');
}

function createHomework(subscriptionId) {
    // This will be implemented when homework creation is ready
    alert('سيتم تنفيذ إنشاء الواجبات قريباً');
}

function editSubscriptionSettings(subscriptionId) {
    // This will be implemented when settings are ready
    alert('سيتم تنفيذ تعديل الإعدادات قريباً');
}
@else
function requestReschedule(subscriptionId) {
    if (confirm('هل تريد إرسال طلب إعادة جدولة للمعلم؟')) {
        alert('سيتم تنفيذ طلب إعادة الجدولة قريباً');
    }
}
@endif
</script>
