@props([
    'session',
    'circle' => null,
    'viewType' => 'student'
])

@php
    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };

    $statusValue = $getStatusValue($session);

    // Check if session is in preparation phase
    $isInPreparation = false;
    if($statusValue === 'scheduled' && $session->scheduled_at) {
        $prepMessage = getMeetingPreparationMessage($session);
        $isInPreparation = $prepMessage['type'] === 'preparing';
    }
@endphp

<div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer" onclick="openSessionDetail({{ $session->id }})">
    <div class="flex items-center justify-between">
        <!-- Session Info -->
        <div class="flex items-center space-x-4 space-x-reverse">
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
                @elseif($statusValue === 'scheduled' && $isInPreparation)
                    <div class="w-4 h-4 bg-amber-500 rounded-full mb-1 animate-spin"></div>
                    <span class="text-xs text-amber-600 font-bold">جاري التحضير</span>
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
                @if($statusValue === 'scheduled' && $session->scheduled_at)
                    @php
                        $preparationMessage = getMeetingPreparationMessage($session);
                    @endphp
                    @if($preparationMessage['type'] !== 'none')
                        <div class="mt-2 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                            <i class="{{ $preparationMessage['icon'] ?? 'ri-timer-line' }}"></i>
                            {{ $preparationMessage['message'] }}
                        </div>
                    @endif
                @elseif($statusValue === 'ready')
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
                <x-sessions.status-badge :status="$session->status" :session="$session" size="sm" />
            </div>
        </div>
    </div>
</div>
