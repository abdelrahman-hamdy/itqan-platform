<x-layouts.student 
    :title="($subscription->subject_name ?? 'درس أكاديمي') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الدرس الخاص مع ' . ($subscription->academicTeacher->full_name ?? 'المعلم الأكاديمي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الملف الشخصي</a></li>
            <li>/</li>
            <li><a href="{{ route('student.academic-private-lessons', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">دروسي الخاصة</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $subscription->subject_name ?? 'درس أكاديمي' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Lesson Header -->
            <x-lesson.header :lesson="$subscription" lesson-type="academic" view-type="student" />

            <!-- Academic Sessions Section -->
            @php
                // Use allSessions to include ongoing sessions in the display
                // Order sessions from recent to later (ascending sequence)
                $sessionsForDisplay = $allSessions->sortBy('session_sequence');
            @endphp
            
                    <x-sessions.unified-sessions-section
            :sessions="$sessionsForDisplay"
            title="جلسات الدرس الخاص"
            view-type="student"
            :circle="$subscription"
            :show-tabs="true"
            empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Lesson Info Sidebar -->
            <x-lesson.info-sidebar 
                :lesson="$subscription" 
                lesson-type="academic" 
                view-type="student" 
                context="individual" />
            
            <!-- Quick Actions -->
            <x-lesson.quick-actions 
                :lesson="$subscription" 
                lesson-type="academic" 
                view-type="student" />
                
            <!-- Progress Overview -->
            <x-lesson.progress-overview 
                :lesson="$subscription" 
                lesson-type="academic" 
                view-type="student" />
        </div>
    </div>
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("student.academic-sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Academic Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

function requestReschedule() {
    if (typeof showConfirmModal === 'function') {
        showConfirmModal({
            title: 'طلب إعادة جدولة',
            message: 'هل تريد إرسال طلب إعادة جدولة للمعلم؟',
            type: 'info',
            confirmText: 'إرسال الطلب',
            cancelText: 'إلغاء',
            onConfirm: () => {
                alert('سيتم تنفيذ طلب إعادة الجدولة قريباً');
            }
        });
    } else {
        alert('سيتم تنفيذ طلب إعادة الجدولة قريباً');
    }
}

// Add any additional student-specific functionality here
document.addEventListener('DOMContentLoaded', function() {
    console.log('Student academic private lesson page loaded');
});
</script>
</x-slot>

</x-layouts.student>
