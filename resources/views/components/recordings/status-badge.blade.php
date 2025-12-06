@props([
    'status',
    'size' => 'md', // sm, md, lg
    'showIcon' => true,
    'pulse' => false // Enable pulse animation for active states
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
        'recording' => [
            'classes' => 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border border-red-300',
            'icon' => 'ri-record-circle-fill',
            'label' => 'جاري التسجيل',
            'pulse' => true
        ],
        'processing' => [
            'classes' => 'bg-gradient-to-r from-amber-100 to-amber-200 text-amber-800 border border-amber-300',
            'icon' => 'ri-loader-4-line animate-spin',
            'label' => 'جاري المعالجة',
            'pulse' => false
        ],
        'completed' => [
            'classes' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300',
            'icon' => 'ri-check-circle-fill',
            'label' => 'مكتمل',
            'pulse' => false
        ],
        'failed' => [
            'classes' => 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border border-red-300',
            'icon' => 'ri-error-warning-fill',
            'label' => 'فشل',
            'pulse' => false
        ],
        'deleted' => [
            'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
            'icon' => 'ri-delete-bin-line',
            'label' => 'محذوف',
            'pulse' => false
        ],
        default => [
            'classes' => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300',
            'icon' => 'ri-question-line',
            'label' => $status,
            'pulse' => false
        ]
    };

    $shouldPulse = $pulse || $statusConfig['pulse'];
    $finalClasses = "inline-flex items-center rounded-lg font-semibold shadow-sm {$sizeClasses} {$statusConfig['classes']}";
@endphp

<span {{ $attributes->merge(['class' => $finalClasses]) }}>
    @if($showIcon)
        <span class="{{ $shouldPulse ? 'animate-pulse' : '' }}">
            <i class="{{ $statusConfig['icon'] }} ml-1"></i>
        </span>
    @endif
    {{ $statusConfig['label'] }}
</span>
