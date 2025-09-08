@props([
    'session' => null,
    'showIcon' => true,
    'showLabel' => true,
    'size' => 'sm', // sm, md, lg
    'variant' => 'badge' // badge, indicator, text
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
    
    // Size classes
    $sizeClasses = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base'
    ];
    
    $iconSizeClasses = [
        'sm' => 'w-3 h-3',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5'
    ];
@endphp

@if($variant === 'badge')
    <span class="inline-flex items-center px-2 py-1 rounded-full text-{{ $sizeClasses[$size] }} font-medium
        bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 border border-{{ $statusColor }}-300">
        @if($showIcon)
            <i class="{{ $statusIcon }} ml-1"></i>
        @endif
        @if($showLabel)
            {{ $statusLabel }}
        @endif
    </span>
@elseif($variant === 'indicator')
    <div class="flex flex-col items-center">
        @if($statusValue === 'completed')
            <div class="{{ $iconSizeClasses[$size] }} bg-green-500 rounded-full mb-1 animate-pulse"></div>
        @elseif($statusValue === 'ongoing')
            <div class="{{ $iconSizeClasses[$size] }} bg-green-500 rounded-full mb-1 animate-pulse"></div>
        @elseif($statusValue === 'ready')
            <div class="{{ $iconSizeClasses[$size] }} bg-green-400 rounded-full mb-1 animate-bounce"></div>
        @elseif($statusValue === 'scheduled')
            <div class="{{ $iconSizeClasses[$size] }} bg-blue-500 rounded-full mb-1 animate-bounce"></div>
        @elseif($statusValue === 'cancelled')
            <div class="{{ $iconSizeClasses[$size] }} bg-gray-400 rounded-full mb-1"></div>
        @elseif($statusValue === 'absent')
            <div class="{{ $iconSizeClasses[$size] }} bg-red-500 rounded-full mb-1"></div>
        @else
            <div class="{{ $iconSizeClasses[$size] }} bg-gray-300 rounded-full mb-1"></div>
        @endif
        @if($showLabel)
            <span class="{{ $sizeClasses[$size] }} text-{{ $statusValue === 'ongoing' ? 'green' : $statusColor }}-600 font-bold">{{ $statusLabel }}</span>
        @endif
    </div>
@elseif($variant === 'text')
    <span class="{{ $sizeClasses[$size] }} text-{{ $statusValue === 'ongoing' ? 'green' : $statusColor }}-600 font-medium">
        @if($showIcon)
            <i class="{{ $statusIcon }} ml-1"></i>
        @endif
        {{ $statusLabel }}
    </span>
@endif
