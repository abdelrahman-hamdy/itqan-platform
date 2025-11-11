@props([
    'session' => null,
    'circle' => null,
    'viewType' => 'student', // student, teacher
    'isClickable' => true,
    'showProgress' => true,
    'showActions' => true
])

@php
    // Use getStatusDisplayData method for consistent status handling (DRY principle)
    $statusData = method_exists($session, 'getStatusDisplayData')
        ? $session->getStatusDisplayData()
        : [
            'status' => is_object($session->status) ? $session->status->value : $session->status,
            'label' => is_object($session->status) ? $session->status->label() : $session->status,
            'icon' => 'ri-calendar-line',
            'color' => 'blue'
        ];

    $statusValue = $statusData['status'];
    $statusLabel = $statusData['label'];
    $statusIcon = $statusData['icon'];
    $statusColor = $statusData['color'];

    // Detect session type
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    
    // Determine session route based on type
    $sessionRoute = $isAcademicSession ? 'student.academic-sessions.show' : 'student.sessions.show';
    
    // Get session timing info
    $now = now();
    $isUpcoming = $session->scheduled_at && $session->scheduled_at->isFuture();
    $isPast = $session->scheduled_at && $session->scheduled_at->isPast();
    $isToday = $session->scheduled_at && $session->scheduled_at->isToday();
    
    // Calculate time remaining for upcoming sessions
    $timeRemaining = null;
    if ($isUpcoming && $session->scheduled_at) {
        $diff = $now->diff($session->scheduled_at);
        if ($diff->days > 0) {
            $timeRemaining = $diff->days . ' يوم';
        } elseif ($diff->h > 0) {
            $timeRemaining = $diff->h . ' ساعة';
        } elseif ($diff->i > 0) {
            $timeRemaining = $diff->i . ' دقيقة';
        } else {
            $timeRemaining = 'قريباً';
        }
    }
@endphp

<div class="session-item bg-white rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200 {{ $isClickable ? 'cursor-pointer hover:shadow-md' : '' }}"
     @if($isClickable) onclick="openSessionDetail({{ $session->id }})" @endif>
    
    <div class="p-6">
        <div class="flex items-start gap-4">
            <!-- Session Status Indicator with Animated Circles -->
            <div class="flex flex-col items-center">
                @if($statusValue === 'completed')
                    <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                    <span class="text-xs text-green-600 font-bold">مكتملة</span>
                @elseif($statusValue === 'ongoing')
                    <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                    <span class="text-xs text-green-600 font-bold">جارية</span>
                @elseif($statusValue === 'ready')
                    <div class="w-4 h-4 bg-green-400 rounded-full mb-1 animate-bounce"></div>
                    <span class="text-xs text-green-600 font-bold">جاهزة</span>
                @elseif($statusValue === 'scheduled')
                    <div class="w-4 h-4 bg-blue-500 rounded-full mb-1 animate-bounce"></div>
                    <span class="text-xs text-blue-600 font-bold">مجدولة</span>
                @elseif($statusValue === 'cancelled')
                    <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                    <span class="text-xs text-gray-500 font-bold">ملغاة</span>
                @elseif($statusValue === 'unscheduled')
                    <div class="w-4 h-4 bg-amber-400 rounded-full mb-1 animate-pulse"></div>
                    <span class="text-xs text-amber-600 font-bold">غير مجدولة</span>
                @elseif($statusValue === 'absent')
                    <div class="w-4 h-4 bg-red-400 rounded-full mb-1"></div>
                    <span class="text-xs text-red-700 font-bold">غائب</span>
                @else
                    <div class="w-4 h-4 bg-gray-300 rounded-full mb-1"></div>
                    <span class="text-xs text-gray-500 font-bold">{{ $statusValue }}</span>
                @endif
            </div>
            
            <!-- Session Details -->
            <div class="flex-1">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            {{ $session->title ?? ($isAcademicSession ? 'جلسة أكاديمية' : 'جلسة قرآنية') }}
                        </h3>
                        
                        @if($session->scheduled_at)
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span class="flex items-center gap-1">
                                <i class="ri-calendar-line"></i>
                                {{ $session->scheduled_at->format('Y/m/d') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <i class="ri-time-line"></i>
                                {{ formatTimeArabic($session->scheduled_at) }}
                            </span>
                            @if($session->duration_minutes)
                            <span class="flex items-center gap-1">
                                <i class="ri-timer-line"></i>
                                {{ $session->duration_minutes }} دقيقة
                            </span>
                            @endif
                        </div>
                        @endif
                    </div>
                    
                    <!-- Status Badges and Indicators -->
                    <div class="flex flex-col items-end gap-2">
                        <!-- Status Label -->
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm
                            bg-gradient-to-r from-{{ $statusColor }}-100 to-{{ $statusColor }}-200 text-{{ $statusColor }}-800 border border-{{ $statusColor }}-300">
                            <i class="{{ $statusIcon }} ml-1"></i>
                            {{ $statusLabel }}
                        </span>
                        
                        
                        <!-- Ready indicator - removed as requested -->
                    </div>
                </div>
                
                <!-- Session Description -->
                @if($session->description)
                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                    {{ $session->description }}
                </p>
                @endif
                
                <!-- Meeting timing info for active sessions -->
                @if(in_array($statusValue, ['scheduled', 'ready', 'ongoing']))
                    @php
                        // Handle both session types for meeting preparation
                        if ($isAcademicSession) {
                            $preparationMinutes = 15; // Default for academic sessions
                        } else {
                            $circle = $session->session_type === 'individual' ? $session->individualCircle : $session->circle;
                            $preparationMinutes = $circle?->preparation_minutes ?? 15;
                        }
                        
                        // Check if the meeting preparation function exists
                        $meetingInfo = function_exists('getMeetingPreparationMessage') 
                            ? getMeetingPreparationMessage($session->scheduled_at, $preparationMinutes)
                            : ['message' => null, 'type' => 'default'];
                    @endphp
                    
                    @if($meetingInfo['message'])
                        @php
                            $bgColor = match($meetingInfo['type']) {
                                'waiting' => 'text-amber-600 bg-amber-50',
                                'preparing' => 'text-blue-600 bg-blue-50',
                                'ready' => 'text-green-600 bg-green-50',
                                default => 'text-gray-600 bg-gray-50'
                            };
                        @endphp
                        <div class="mt-2 text-xs {{ $bgColor }} px-2 py-1 rounded w-fit">
                            <i class="{{ $meetingInfo['icon'] ?? 'ri-timer-line' }}"></i>
                            {{ $meetingInfo['message'] }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Session detail function -->
<script>
function openSessionDetail(sessionId) {
    console.log('Opening session details for ID:', sessionId);
    // This will be handled by the parent component
}
</script>
