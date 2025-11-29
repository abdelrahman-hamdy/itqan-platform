@props([
    'session',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $isTeacher = $viewType === 'teacher';
    
    // Detect session type - check if it's an AcademicSession or QuranSession
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    
    if ($isAcademicSession) {
        // Academic session types and descriptions
        $sessionTypeText = 'جلسة أكاديمية';
        $sessionDescription = $session->academicSubscription?->subject_name 
            ? 'درس ' . $session->academicSubscription->subject_name 
            : 'جلسة تعليمية أكاديمية';
    } else {
        // Quran session types
        $sessionTypeText = match($session->session_type) {
            'group' => 'جلسة مجموعة',
            'individual' => 'جلسة فردية',
            'makeup' => 'جلسة تعويضية',
            'trial' => 'جلسة تجريبية',
            'assessment' => 'جلسة تقييم',
            default => 'جلسة'
        };
        $sessionDescription = 'جلسة تعليم القرآن الكريم';
    }
@endphp

<!-- Enhanced Session Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Session Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $session->title ?? $sessionTypeText }}
                </h1>
                <x-sessions.status-badge :status="$session->status" :session="$session" size="md" />
            </div>
            
            <!-- Session Description -->
            <p class="text-gray-600 mb-4 leading-relaxed">
                {{ $session->description ?? $sessionDescription }}
            </p>
            
            <!-- Session Quick Info -->
            <div class="flex flex-wrap items-center gap-4">
                @if($session->scheduled_at)
                <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                    <i class="ri-calendar-line ml-1"></i>
                    {{ $session->scheduled_at->format('Y/m/d') }}
                </span>
                @endif
                
                @if($session->scheduled_at)
                <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 text-sm font-medium rounded-full">
                    <i class="ri-time-line ml-1"></i>
                    {{ formatTimeArabic($session->scheduled_at) }}
                </span>
                @endif
                
                @if($session->duration_minutes)
                <span class="inline-flex items-center px-3 py-1 bg-purple-50 text-purple-700 text-sm font-medium rounded-full">
                    <i class="ri-timer-line ml-1"></i>
                    {{ $session->duration_minutes }} دقيقة
                </span>
                @endif
                
            </div>
        </div>
    </div>

    <!-- Current Session Info for Group Sessions -->
    @if($session->session_type === 'group' && $session->circle && $isTeacher)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $session->circle->students->count() ?? 0 }}</div>
                    <div class="text-sm text-gray-600">الطلاب المسجلين</div>
                </div>
                
                @if($session->status === \App\Enums\SessionStatus::COMPLETED)
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $session->attendances->where('attendance_status', 'present')->count() ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600">الحضور</div>
                </div>
                @endif
                
                @if($session->homework && $session->homework->count() > 0)
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $session->homework->count() }}</div>
                    <div class="text-sm text-gray-600">الواجبات</div>
                </div>
                @endif
                
                @if($session->actual_duration_minutes)
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $session->actual_duration_minutes }}</div>
                    <div class="text-sm text-gray-600">المدة الفعلية</div>
                </div>
                @endif
            </div>
        </div>
    @endif
</div> 