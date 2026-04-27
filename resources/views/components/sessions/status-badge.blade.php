@props([
    'status',
    'session' => null,
    'size' => 'md', // sm, md, lg
    // When set, the badge renders the derived UI-only display status
    // (absent / canceled / completed) instead of the raw $status. The role
    // controls the absent color: red for student/parent (their fault),
    // gray for teacher (didn't earn).
    'displayStatus' => null,
    'role' => null, // 'student' | 'parent' | 'teacher'
])

@php
    use App\Enums\SessionStatus;

    // Define size classes once — shared across the raw and derived branches.
    $sizeClasses = match($size) {
        'sm' => 'px-1.5 md:px-2 py-0.5 md:py-1 text-[10px] md:text-xs',
        'lg' => 'px-3 md:px-4 py-1.5 md:py-2 text-xs md:text-sm',
        default => 'px-2 md:px-3 py-1 md:py-1.5 text-[10px] md:text-xs' // md
    };

    if ($displayStatus !== null) {
        // Derived UI-only labels (absent / canceled / completed). Falls
        // through to raw rendering for non-completed statuses.
        $statusConfig = match ($displayStatus) {
            'completed' => [
                'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
                'icon' => 'ri-check-circle-line',
                'label' => __('components.sessions.status.completed'),
            ],
            'absent' => $role === 'teacher'
                ? [
                    'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 border border-gray-300',
                    'icon' => 'ri-user-unfollow-line',
                    'label' => __('components.sessions.status.absent'),
                ]
                : [
                    // Student/parent: red flags it as the student's fault.
                    'classes' => 'bg-gradient-to-r from-red-100 to-red-200 text-red-700 border border-red-300',
                    'icon' => 'ri-user-unfollow-line',
                    'label' => __('components.sessions.status.absent'),
                ],
            'canceled' => [
                'classes' => 'bg-gray-50 text-gray-500 border border-gray-200',
                'icon' => 'ri-close-line',
                'label' => __('components.sessions.status.canceled'),
            ],
            default => null,
        };

        if ($statusConfig === null) {
            // Non-completed statuses — fall back to raw rendering by reusing $displayStatus as $status.
            $status = $displayStatus;
        }
    }

    // Check if session is in preparation phase
    $isInPreparation = false;
    if (! ($statusConfig ?? null) && $session && $session->scheduled_at) {
        $statusValue = is_object($status) ? $status->value : $status;
        if ($statusValue === SessionStatus::SCHEDULED->value) {
            $prepMessage = getMeetingPreparationMessage($session);
            $isInPreparation = $prepMessage['type'] === 'preparing';
        }
    }

    // If in preparation, override with amber styling (raw-status branch only).
    if (! ($statusConfig ?? null) && $isInPreparation) {
        $statusConfig = [
            'classes' => 'bg-gradient-to-r from-amber-100 to-amber-200 text-amber-800 border border-amber-300',
            'icon' => 'ri-settings-3-line animate-spin',
            'label' => __('components.sessions.status.preparing')
        ];
    } elseif (! ($statusConfig ?? null)) {
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
            default => [
                'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
                'icon' => 'ri-question-line',
                'label' => is_object($status) ? $status->label() : (string) $status
            ]
        };
    }

    $finalClasses = "inline-flex items-center gap-1 rounded-lg font-semibold shadow-sm {$sizeClasses} {$statusConfig['classes']}";
@endphp

<span {{ $attributes->merge(['class' => $finalClasses]) }}>
    <i class="{{ $statusConfig['icon'] }}"></i>
    <span class="whitespace-nowrap">{{ $statusConfig['label'] }}</span>
</span>
