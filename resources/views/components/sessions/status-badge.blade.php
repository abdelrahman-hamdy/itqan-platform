@props([
    'status',
    'size' => 'md' // sm, md, lg
])

@php
    // Define size classes
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-1 text-xs',
        'lg' => 'px-4 py-2 text-sm',
        default => 'px-3 py-1.5 text-xs' // md
    };

    // Define status configurations with gradient backgrounds, colors, icons, and Arabic labels
    $statusConfig = match($status) {
        'scheduled', \App\Enums\SessionStatus::SCHEDULED => [
            'classes' => 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 border border-blue-300',
            'icon' => 'ri-calendar-line',
            'label' => 'مجدولة'
        ],
        'ongoing', \App\Enums\SessionStatus::ONGOING => [
            'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
            'icon' => 'ri-live-line',
            'label' => 'جارية الآن'
        ],
        'ready', \App\Enums\SessionStatus::READY => [
            'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
            'icon' => 'ri-video-line',
            'label' => 'جاهزة للبدء'
        ],
        'completed', \App\Enums\SessionStatus::COMPLETED => [
            'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
            'icon' => 'ri-check-circle-line',
            'label' => 'مكتملة'
        ],
        'cancelled', \App\Enums\SessionStatus::CANCELLED => [
            'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
            'icon' => 'ri-close-line',
            'label' => 'ملغية'
        ],
        'unscheduled', \App\Enums\SessionStatus::UNSCHEDULED => [
            'classes' => 'bg-gradient-to-r from-amber-100 to-amber-200 text-amber-800 border border-amber-300',
            'icon' => 'ri-time-line',
            'label' => 'غير مجدولة'
        ],
        'absent', \App\Enums\SessionStatus::ABSENT => [
            'classes' => 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border border-red-300',
            'icon' => 'ri-user-unfollow-line',
            'label' => 'غائب'
        ],
        default => [
            'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
            'icon' => 'ri-question-line',
            'label' => is_object($status) ? $status->label() : (string) $status
        ]
    };

    $finalClasses = "inline-flex items-center rounded-lg font-semibold shadow-sm {$sizeClasses} {$statusConfig['classes']}";
@endphp

<span {{ $attributes->merge(['class' => $finalClasses]) }}>
    <i class="{{ $statusConfig['icon'] }} ml-1"></i>
    {{ $statusConfig['label'] }}
</span>
