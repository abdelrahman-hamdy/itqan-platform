@props([
    'status',
    'session' => null,
    'size' => 'md' // sm, md, lg
])

@php
    use App\Enums\SessionStatus;

    // Check if session is in preparation phase
    $isInPreparation = false;
    if ($session && $session->scheduled_at) {
        $statusValue = is_object($status) ? $status->value : $status;
        if ($statusValue === SessionStatus::SCHEDULED->value) {
            $prepMessage = getMeetingPreparationMessage($session);
            $isInPreparation = $prepMessage['type'] === 'preparing';
        }
    }

    // Define size classes
    $sizeClasses = match($size) {
        'sm' => 'px-1.5 md:px-2 py-0.5 md:py-1 text-[10px] md:text-xs',
        'lg' => 'px-3 md:px-4 py-1.5 md:py-2 text-xs md:text-sm',
        default => 'px-2 md:px-3 py-1 md:py-1.5 text-[10px] md:text-xs' // md
    };

    // If in preparation, override with amber styling
    if ($isInPreparation) {
        $statusConfig = [
            'classes' => 'bg-gradient-to-r from-amber-100 to-amber-200 text-amber-800 border border-amber-300',
            'icon' => 'ri-settings-3-line animate-spin',
            'label' => __('components.sessions.status.preparing')
        ];
    } else {
        // Define status configurations with gradient backgrounds, colors, icons, and localized labels
        $statusConfig = match($status) {
            SessionStatus::SCHEDULED->value, SessionStatus::SCHEDULED => [
                'classes' => 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 border border-blue-300',
                'icon' => 'ri-calendar-line',
                'label' => __('components.sessions.status.scheduled')
            ],
            SessionStatus::ONGOING->value, SessionStatus::ONGOING => [
                'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
                'icon' => 'ri-live-line animate-pulse',
                'label' => __('components.sessions.status.ongoing_now')
            ],
            SessionStatus::READY->value, SessionStatus::READY => [
                'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
                'icon' => 'ri-video-line',
                'label' => __('components.sessions.status.ready')
            ],
            SessionStatus::COMPLETED->value, SessionStatus::COMPLETED => [
                'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
                'icon' => 'ri-check-circle-line',
                'label' => __('components.sessions.status.completed')
            ],
            SessionStatus::CANCELLED->value, SessionStatus::CANCELLED => [
                'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
                'icon' => 'ri-close-line',
                'label' => __('components.sessions.status.cancelled')
            ],
            SessionStatus::UNSCHEDULED->value, SessionStatus::UNSCHEDULED => [
                'classes' => 'bg-gradient-to-r from-amber-100 to-amber-200 text-amber-800 border border-amber-300',
                'icon' => 'ri-time-line',
                'label' => __('components.sessions.status.unscheduled')
            ],
            SessionStatus::ABSENT->value, SessionStatus::ABSENT => [
                'classes' => 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border border-red-300',
                'icon' => 'ri-user-unfollow-line',
                'label' => __('components.sessions.status.absent')
            ],
            default => [
                'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
                'icon' => 'ri-question-line',
                'label' => is_object($status) ? $status->label() : (string) $status
            ]
        };
    }

    $finalClasses = "inline-flex items-center rounded-lg font-semibold shadow-sm {$sizeClasses} {$statusConfig['classes']}";
@endphp

<span {{ $attributes->merge(['class' => $finalClasses]) }}>
    <i class="{{ $statusConfig['icon'] }} ms-0.5 md:ms-1 rtl:ms-0.5 rtl:md:ms-1 ltr:me-0.5 ltr:md:me-1"></i>
    <span class="whitespace-nowrap">{{ $statusConfig['label'] }}</span>
</span>
