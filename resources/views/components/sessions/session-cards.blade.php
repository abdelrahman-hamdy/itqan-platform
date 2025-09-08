@props([
    'sessions' => collect(),
    'viewType' => 'student', // student, teacher
    'circle' => null,
    'title' => 'إدارة جلسات الحلقة',
    'subtitle' => null
])

@php
    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };
    
    $totalSessions = $sessions->count();
    $comingSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), ['scheduled', 'ready', 'ongoing']);
    });
    $passedSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), ['completed', 'cancelled']);
    });
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">{{ $title }}</h3>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                المجموع: {{ $totalSessions }}
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-8 px-6" id="sessionTabs">
            <button class="session-tab active py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600 text-sm" data-tab="all">
                الكل ({{ $totalSessions }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm" data-tab="coming">
                القادمة ({{ $comingSessions->count() }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm" data-tab="passed">
                المنتهية ({{ $passedSessions->count() }})
            </button>
        </nav>
    </div>
    
    <!-- Sessions Content -->
    <div class="p-6">
        <!-- All Sessions Tab -->
        <div id="all-sessions" class="session-tab-content block">
            @if($sessions->count() > 0)
                <div class="space-y-4">
                    @foreach($sessions as $session)
                        <div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer" onclick="openSessionDetail({{ $session->id }})">
                            <div class="flex items-center justify-between">
                                <!-- Session Info -->
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <!-- Session Status Indicator with Animated Circles -->
                                    <div class="flex flex-col items-center">
                                        @php $statusValue = $getStatusValue($session); @endphp
                                        @if($statusValue === 'completed')
                                            <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                            <span class="text-xs text-green-600 font-bold">مكتملة</span>
                                        @elseif($statusValue === 'ongoing')
                                            <div class="w-4 h-4 bg-orange-500 rounded-full mb-1 animate-pulse"></div>
                                            <span class="text-xs text-orange-600 font-bold">جارية</span>
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
                                        <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                            <h4 class="font-bold text-gray-900 text-lg">
                                                {{ $session->title ?? ($circle && $circle->students ? 'جلسة جماعية - ' . $circle->name : 'جلسة فردية - ' . ($circle->subscription->package->name ?? 'حلقة قرآنية')) }}
                                            </h4>
                                            
                                            <!-- Ready indicator - removed as requested -->
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-calendar-line"></i>
                                                <span>{{ $session->scheduled_at ? $session->scheduled_at->format('Y/m/d') : 'غير مجدولة' }}</span>
                                            </span>
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                                            </span>
                                            @if($session->duration_minutes)
                                                <span class="flex items-center space-x-1 space-x-reverse">
                                                    <i class="ri-timer-2-line"></i>
                                                    <span>{{ $session->duration_minutes }} دقيقة</span>
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <!-- Meeting timing info for active sessions -->
                                        @if($getStatusValue($session) === 'scheduled' && $session->scheduled_at)
                                            @php
                                                $timeData = formatTimeRemaining($session->scheduled_at);
                                                $timeText = $timeData['formatted'];
                                            @endphp
                                            @if(!$timeData['is_past'])
                                                <div class="mt-2 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                                                    <i class="ri-timer-line"></i>
                                                    سيتم تحضير الاجتماع خلال {{ $timeText }}
                                                </div>
                                            @endif
                                        @elseif($getStatusValue($session) === 'ready')
                                            <div class="mt-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block self-start">
                                                <i class="ri-video-line"></i>
                                                الاجتماع متاح الآن
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Session Status and Actions -->
                                <div class="text-left">
                                    <div class="flex flex-col items-end space-y-2">
                                        <!-- Status Badge -->
                                        <x-sessions.status-badge :status="$session->status" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات مسجلة بعد</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر جلساتك هنا عند إنشائها</p>
                </div>
            @endif
        </div>

        <!-- Coming Sessions Tab -->
        <div id="coming-sessions" class="session-tab-content hidden">
            @if($comingSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($comingSessions as $session)
                        <div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer" onclick="openSessionDetail({{ $session->id }})">
                            <div class="flex items-center justify-between">
                                <!-- Session Info -->
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <!-- Session Status Indicator with Animated Circles -->
                                    <x-sessions.status-display 
                                        :session="$session" 
                                        variant="indicator" 
                                        size="sm" 
                                        :show-icon="false" 
                                        :show-label="true" />
                                    
                                    <!-- Session Details -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                            <h4 class="font-bold text-gray-900 text-lg">
                                                {{ $session->title ?? ($circle && $circle->students ? 'جلسة جماعية - ' . $circle->name : 'جلسة فردية - ' . ($circle->subscription->package->name ?? 'حلقة قرآنية')) }}
                                            </h4>
                                            
                                            <!-- Ready indicator - removed as requested -->
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-calendar-line"></i>
                                                <span>{{ $session->scheduled_at ? $session->scheduled_at->format('Y/m/d') : 'غير مجدولة' }}</span>
                                            </span>
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                                            </span>
                                            @if($session->duration_minutes)
                                                <span class="flex items-center space-x-1 space-x-reverse">
                                                    <i class="ri-timer-2-line"></i>
                                                    <span>{{ $session->duration_minutes }} دقيقة</span>
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <!-- Meeting timing info for active sessions -->
                                        @if($getStatusValue($session) === 'scheduled' && $session->scheduled_at)
                                            @php
                                                $timeData = formatTimeRemaining($session->scheduled_at);
                                                $timeText = $timeData['formatted'];
                                            @endphp
                                            @if(!$timeData['is_past'])
                                                <div class="mt-2 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                                                    <i class="ri-timer-line"></i>
                                                    سيتم تحضير الاجتماع خلال {{ $timeText }}
                                                </div>
                                            @endif
                                        @elseif($getStatusValue($session) === 'ready')
                                            <div class="mt-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block self-start">
                                                <i class="ri-video-line"></i>
                                                الاجتماع متاح الآن
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Session Status and Actions -->
                                <div class="text-left">
                                    <div class="flex flex-col items-end space-y-2">
                                        <!-- Status Badge -->
                                        <x-sessions.status-badge :status="$session->status" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات قادمة</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر الجلسات القادمة هنا</p>
                </div>
            @endif
        </div>

        <!-- Passed Sessions Tab -->
        <div id="passed-sessions" class="session-tab-content hidden">
            @if($passedSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($passedSessions as $session)
                        <div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer" onclick="openSessionDetail({{ $session->id }})">
                            <div class="flex items-center justify-between">
                                <!-- Session Info -->
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <!-- Session Status Indicator with Animated Circles -->
                                    <div class="flex flex-col items-center">
                                        @if($getStatusValue($session) === 'completed')
                                            <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                            <span class="text-xs text-green-600 font-bold">مكتملة</span>
                                        @elseif($getStatusValue($session) === 'cancelled')
                                            <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                                            <span class="text-xs text-gray-500 font-bold">ملغاة</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Session Details -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                            <h4 class="font-bold text-gray-900 text-lg">
                                                {{ $session->title ?? ($circle && $circle->students ? 'جلسة جماعية - ' . $circle->name : 'جلسة فردية - ' . ($circle->subscription->package->name ?? 'حلقة قرآنية')) }}
                                            </h4>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-calendar-line"></i>
                                                <span>{{ $session->scheduled_at ? $session->scheduled_at->format('Y/m/d') : 'غير مجدولة' }}</span>
                                            </span>
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                                            </span>
                                            @if($session->duration_minutes)
                                                <span class="flex items-center space-x-1 space-x-reverse">
                                                    <i class="ri-timer-2-line"></i>
                                                    <span>{{ $session->duration_minutes }} دقيقة</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Session Status and Actions -->
                                <div class="text-left">
                                    <div class="flex flex-col items-end space-y-2">
                                        <!-- Status Badge -->
                                        <x-sessions.status-badge :status="$session->status" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات منتهية</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر الجلسات المكتملة والملغاة هنا</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple and robust event delegation for tabs
    document.addEventListener('click', function(e) {
        // Check if clicked element is a session tab
        if (e.target.classList.contains('session-tab')) {
            e.preventDefault();
            
            const clickedTab = e.target;
            const targetTab = clickedTab.getAttribute('data-tab');
            
            if (!targetTab) return;
            
            // Find the container
            const container = clickedTab.closest('.bg-white');
            if (!container) return;
            
            // Update all tabs in this container
            container.querySelectorAll('.session-tab').forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Activate clicked tab
            clickedTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            clickedTab.classList.remove('border-transparent', 'text-gray-500');
            
            // Update all tab contents in this container
            container.querySelectorAll('.session-tab-content').forEach(content => {
                content.style.display = 'none';
                content.classList.add('hidden');
                content.classList.remove('block');
            });
            
            // Show target content
            const targetContent = container.querySelector(`#${targetTab}-sessions`);
            if (targetContent) {
                targetContent.style.display = 'block';
                targetContent.classList.remove('hidden');
                targetContent.classList.add('block');
            }
        }
    });
});

// Session detail function
function openSessionDetail(sessionId) {
    console.log('Opening session details for ID:', sessionId);
}
</script> 