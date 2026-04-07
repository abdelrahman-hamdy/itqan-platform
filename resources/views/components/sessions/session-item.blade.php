@props([
    'session',
    'circle' => null,
    'viewType' => 'student'
])

@php
use App\Enums\SessionStatus;

    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };

    $statusValue = $getStatusValue($session);

    // Check if session is in preparation phase
    $isInPreparation = false;
    if($statusValue === SessionStatus::SCHEDULED->value && $session->scheduled_at) {
        $prepMessage = getMeetingPreparationMessage($session);
        $isInPreparation = $prepMessage['type'] === 'preparing';
    }

    // Check if session is preparing meeting (READY/ONGOING but meeting room not created)
    $isPreparingMeeting = method_exists($session, 'isPreparingMeeting') ? $session->isPreparingMeeting() : false;

    // Generate the correct session URL based on session type and view type
    // Different session types have different routes to prevent ID collision issues
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

    if ($viewType === 'supervisor') {
        // Supervisor/admin routes go to manage.sessions.show
        $sessionTypeSlug = match(true) {
            $session instanceof \App\Models\InteractiveCourseSession => 'interactive',
            $session instanceof \App\Models\AcademicSession => 'academic',
            default => 'quran',
        };
        $sessionUrl = route('manage.sessions.show', [
            'subdomain' => $subdomain,
            'sessionType' => $sessionTypeSlug,
            'sessionId' => $session->id,
        ]);
    } elseif ($viewType === 'student') {
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            $sessionUrl = route('student.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
        } elseif ($session instanceof \App\Models\AcademicSession) {
            $sessionUrl = route('student.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
        } else {
            $sessionUrl = route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]);
        }
    } else {
        // Teacher routes
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            $sessionUrl = route('teacher.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
        } elseif ($session instanceof \App\Models\AcademicSession) {
            $sessionUrl = route('teacher.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]);
        } else {
            $sessionUrl = route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]);
        }
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
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">{{ __('components.sessions.status.preparing_meeting') }}</span>
                @elseif($statusValue === SessionStatus::COMPLETED->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-500 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">{{ __('components.sessions.status.completed') }}</span>
                @elseif($statusValue === SessionStatus::ONGOING->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-500 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">{{ __('components.sessions.status.ongoing') }}</span>
                @elseif($statusValue === SessionStatus::READY->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-green-400 rounded-full mb-0.5 md:mb-1 animate-bounce"></div>
                    <span class="text-[10px] md:text-xs text-green-600 font-bold">{{ __('components.sessions.status.ready') }}</span>
                @elseif($statusValue === SessionStatus::SCHEDULED->value && $isInPreparation)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-amber-500 rounded-full mb-0.5 md:mb-1 animate-spin"></div>
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">{{ __('components.sessions.status.preparing') }}</span>
                @elseif($statusValue === SessionStatus::SCHEDULED->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-blue-500 rounded-full mb-0.5 md:mb-1 animate-bounce"></div>
                    <span class="text-[10px] md:text-xs text-blue-600 font-bold">{{ __('components.sessions.status.scheduled') }}</span>
                @elseif($statusValue === SessionStatus::CANCELLED->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-gray-400 rounded-full mb-0.5 md:mb-1"></div>
                    <span class="text-[10px] md:text-xs text-gray-500 font-bold">{{ __('components.sessions.status.cancelled') }}</span>
                @elseif($statusValue === SessionStatus::UNSCHEDULED->value)
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-amber-400 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                    <span class="text-[10px] md:text-xs text-amber-600 font-bold whitespace-nowrap">{{ __('components.sessions.status.unscheduled') }}</span>
                @else
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-gray-300 rounded-full mb-0.5 md:mb-1"></div>
                    <span class="text-[10px] md:text-xs text-gray-500 font-bold">{{ $statusValue }}</span>
                @endif
            </div>

            <!-- Session Details -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 md:gap-3 mb-1.5 md:mb-2">
                    <h4 class="font-bold text-gray-900 text-sm md:text-lg truncate">
                        {{ $session->title ?? ($circle && $circle->students ? __('components.sessions.item.group_session_prefix') . ' - ' . $circle->name : __('components.sessions.item.individual_session_prefix') . ' - ' . ($circle->subscription->package->name ?? __('components.sessions.item.quran_circle'))) }}
                    </h4>
                </div>

                <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line"></i>
                        <span>{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at) : __('components.sessions.status.not_scheduled') }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="ri-time-line"></i>
                        <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                    </span>
                    @if($session->duration_minutes)
                        <span class="flex items-center gap-1">
                            <i class="ri-timer-2-line"></i>
                            <span>{{ $session->duration_minutes }} {{ __('components.sessions.header.duration_minutes') }}</span>
                        </span>
                    @endif
                    @if($session instanceof \App\Contracts\RecordingCapable
                        && $session->hasCompletedRecordings()
                        && $session->shouldShowRecordingToUser(auth()->user()))
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-green-50 text-green-600 text-[10px] md:text-xs">
                            <i class="ri-video-line text-[0.85em]"></i>
                            {{ __('recordings.recorded_badge') }}
                        </span>
                    @endif
                </div>

                <!-- Meeting timing info for active sessions -->
                @if($isPreparingMeeting)
                    <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                        <i class="ri-settings-3-line animate-spin inline-block"></i>
                        {{ __('components.sessions.item.preparing_meeting_room') }}
                    </div>
                @elseif($statusValue === SessionStatus::SCHEDULED->value && $session->scheduled_at)
                    @php
                        $preparationMessage = getMeetingPreparationMessage($session);
                    @endphp
                    @if($preparationMessage['type'] !== 'none')
                        <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block self-start">
                            <i class="{{ $preparationMessage['icon'] ?? 'ri-timer-line' }}"></i>
                            {{ $preparationMessage['message'] }}
                        </div>
                    @endif
                @elseif($statusValue === SessionStatus::READY->value)
                    <div class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-green-600 bg-green-50 px-2 py-1 rounded inline-block self-start">
                        <i class="ri-video-line"></i>
                        {{ __('components.sessions.item.meeting_available') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Session Status and Actions -->
        <div class="text-start flex-shrink-0">
            <div class="flex flex-col items-start sm:items-end gap-1.5 md:gap-2">
                <!-- Status Badge -->
                <x-sessions.status-badge :status="$session->status" :session="$session" size="sm" />
            </div>
        </div>
    </div>
</a>
