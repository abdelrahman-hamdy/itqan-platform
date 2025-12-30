{{--
    Attendance Status Badge Component
    Unified display for attendance status across the application
    Uses AttendanceStatus enum for consistent styling

    Usage:
    <x-ui.attendance-badge status="attended" />
    <x-ui.attendance-badge :status="$record->attendance_status" :percentage="$record->attendance_percentage" />
    <x-ui.attendance-badge status="late" show-icon="false" />
--}}

@props([
    'status',
    'percentage' => null,
    'showIcon' => true,
    'size' => 'md' // sm, md, lg
])

@php
    $statusEnum = \App\Enums\AttendanceStatus::tryFrom($status);

    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-xs',
        'lg' => 'px-4 py-2 text-base',
        default => 'px-3 py-1.5 text-sm'
    };

    // Icon size
    $iconSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-lg',
        default => 'text-sm'
    };
@endphp

@if($statusEnum)
<span {{ $attributes->merge([
    'class' => "inline-flex items-center {$sizeClasses} {$statusEnum->badgeClass()} rounded-full font-semibold"
]) }}>
    @if($showIcon)
        <i class="{{ $statusEnum->icon() }} {{ $iconSize }} ms-1 rtl:ms-1 ltr:me-1"></i>
    @endif
    {{ $statusEnum->label() }}
    @if($percentage !== null)
        <span class="ms-1 rtl:ms-1 ltr:me-1">({{ number_format($percentage, 0) }}%)</span>
    @endif
</span>
@else
{{-- Fallback for unknown status --}}
<span {{ $attributes->merge([
    'class' => "inline-flex items-center {$sizeClasses} bg-gray-100 text-gray-800 rounded-full font-semibold"
]) }}>
    @if($showIcon)
        <i class="ri-question-line {{ $iconSize }} ms-1 rtl:ms-1 ltr:me-1"></i>
    @endif
    {{ $status ?? __('components.ui.attendance_badge.unknown') }}
</span>
@endif
