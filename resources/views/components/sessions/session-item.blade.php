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

    // Check if session is preparing meeting (READY/ONGOING but meeting room not created)
    $isPreparingMeeting = method_exists($session, 'isPreparingMeeting') ? $session->isPreparingMeeting() : false;

    // Generate the correct session URL based on session type and view type
    // Different session types have different routes to prevent ID collision issues
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

    if ($session instanceof \App\Models\InteractiveCourseSession) {
        // Interactive course sessions use 'session' parameter
        $sessionUrl = $viewType === 'student'
            ? route('student.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id])
            : route('teacher.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
    } elseif ($session instanceof \App\Models\AcademicSession) {
        // Academic sessions use 'session' parameter
        $sessionUrl = $viewType === 'student'
            ? route('student.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id])
            : route('teacher.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
    } else {
        // Default: QuranSession - uses 'sessionId' parameter
        $sessionUrl = $viewType === 'student'
            ? route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id])
            : route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]);
    }
@endphp

<a href="{{ $sessionUrl }}" class="attendance-indicator block rounded-lg md:rounded-xl p-4 md:p-6 border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer min-h-[44px]">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 md:gap-4">
        <!-- Session Info -->
        <div class="flex items-start sm:items-center gap-3 md:gap-4">
            <!-- Session Status Indicator with Animated Circles -->
            <div class="flex flex-col items-center flex-shrink-0">
                @if($isPreparingMeeting)
                    <!-- Preparing Meeting State (READY/ONGOING but room not created) -->
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-amber-500 rounded-full mb-0.5 md:mb-1 animate-spin"></div>
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">تجهيز الاجتماع</span>
                @elseif($statusValue === 'completed')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-500 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">مكتملة</span>
                @elseif($statusValue === 'ongoing')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-500 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">جارية</span>
                @elseif($statusValue === 'ready')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-400 rounded-full mb-0.5 md:mb-1 animate-bounce"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">جاهزة</span>
                @elseif($statusValue === 'scheduled' && $isInPreparation)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-amber-500 rounded-full mb-0.5 md:mb-1 animate-spin"></div>
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">جاري التحضير</span>
                @elseif($statusValue === 'scheduled')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-blue-500 rounded-full mb-0.5 md:mb-1 animate-bounce"></div>
                    <span class="text-[10px] md:text-xs text-blue-600 font-bold">مجدولة</span>
                @elseif($statusValue === 'cancelled')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-gray-400 rounded-full mb-0.5 md:mb-1"></div>
                    <span class="text-[10px] md:text-xs text-gray-500 font-bold">ملغاة</span>
                @elseif($statusValue === 'unscheduled')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-amber-400 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">غير مجدولة</span>
                @elseif($statusValue === 'absent')
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-red-400 rounded-full mb-0.5 md:mb-1"></div>
                    <span class="text-[10px] md:text-xs text-red-700 font-bold">غائب</span>
                @else
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-gray-300 rounded-full mb-0.5 md:mb-1"></div>
                    <span class="text-[10px] md:text-xs text-gray-500 font-bold">{{ $statusValue }}</span>
                @endif
            </div>

            <!-- Session Details -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 md:gap-3 mb-1.5 md:mb-2">
                    <h4 class="font-bold text-gray-900 text-sm md:text-lg truncate">
                        {{ $session->title ?? ($circle && $circle->students ? 'جلسة جماعية - ' . $circle->name : 'جلسة فردية - ' . ($circle->subscription->package->name ?? 'حلقة قرآنية')) }}
                    </h4>
                </div>

                <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line"></i>
                        <span>{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at) : 'غير مجدولة' }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="ri-time-line"></i>
                        <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                    </span>
                    @if($session->duration_minutes)
                        <span class="flex items-center gap-1">
                            <i class="ri-timer-2-line"></i>
                            <span>{{ $session->duration_minutes }} دقيقة</span>
                        </span>
                    @endif
                </div>

                <!-- Meeting timing info for active sessions -->
                @if($isPreparingMeeting)
                    <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                        <i class="ri-settings-3-line animate-spin inline-block"></i>
                        جارٍ تجهيز غرفة الاجتماع... يرجى الانتظار
                    </div>
                @elseif($statusValue === 'scheduled' && $session->scheduled_at)
                    @php
                        $preparationMessage = getMeetingPreparationMessage($session);
                    @endphp
                    @if($preparationMessage['type'] !== 'none')
                        <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                            <i class="{{ $preparationMessage['icon'] ?? 'ri-timer-line' }}"></i>
                            {{ $preparationMessage['message'] }}
                        </div>
                    @endif
                @elseif($statusValue === 'ready')
                    <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block self-start">
                        <i class="ri-video-line"></i>
                        الاجتماع متاح الآن
                    </div>
                @endif
            </div>
        </div>

        <!-- Session Status and Actions -->
        <div class="text-left sm:text-right flex-shrink-0">
            <div class="flex flex-col items-start sm:items-end gap-1.5 md:gap-2">
                <!-- Status Badge -->
                <x-sessions.status-badge :status="$session->status" :session="$session" size="sm" />
            </div>
        </div>
    </div>
</a>
