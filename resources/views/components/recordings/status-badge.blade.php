@props([
    'status',
    'size' => 'md',
    'showIcon' => true,
])

@php
    // Handle both enum objects and raw strings
    $statusValue = is_object($status) && method_exists($status, 'value') ? $status->value : (string) $status;

    $sizeClasses = match($size) {
        'sm' => 'px-1.5 py-0.5 text-[10px]',
        'lg' => 'px-3 py-1.5 text-xs',
        default => 'px-2 py-1 text-[11px]',
    };

    $config = match($statusValue) {
        'recording' => ['bg' => 'bg-red-50 text-red-600', 'icon' => 'ri-record-circle-fill', 'label' => __('components.recordings.status_badge.recording')],
        'processing' => ['bg' => 'bg-amber-50 text-amber-600', 'icon' => 'ri-loader-4-line animate-spin', 'label' => __('components.recordings.status_badge.processing')],
        'completed' => ['bg' => 'bg-green-50 text-green-600', 'icon' => 'ri-checkbox-circle-line', 'label' => __('components.recordings.status_badge.completed')],
        'failed' => ['bg' => 'bg-red-50 text-red-600', 'icon' => 'ri-close-circle-line', 'label' => __('components.recordings.status_badge.failed')],
        'deleted' => ['bg' => 'bg-gray-50 text-gray-400', 'icon' => 'ri-delete-bin-line', 'label' => __('components.recordings.status_badge.deleted')],
        default => ['bg' => 'bg-gray-50 text-gray-500', 'icon' => 'ri-question-line', 'label' => $statusValue],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-0.5 rounded-full font-medium {$sizeClasses} {$config['bg']}"]) }}>
    @if($showIcon)
        <i class="{{ $config['icon'] }} text-[0.85em]"></i>
    @endif
    {{ $config['label'] }}
</span>
