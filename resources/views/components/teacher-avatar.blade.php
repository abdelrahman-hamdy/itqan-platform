@props(['teacher', 'size' => 'md', 'showBadge' => false])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16', 
        'lg' => 'w-24 h-24',
        'xl' => 'w-32 h-32',
        default => 'w-16 h-16'
    };
    
    $textSizeClasses = match($size) {
        'sm' => 'text-sm',
        'md' => 'text-lg',
        'lg' => 'text-2xl', 
        'xl' => 'text-4xl',
        default => 'text-lg'
    };

    $badgeSizeClasses = match($size) {
        'sm' => 'w-4 h-4 -bottom-1 -right-1',
        'md' => 'w-5 h-5 -bottom-1 -right-1',
        'lg' => 'w-6 h-6 -bottom-1 -right-1',
        'xl' => 'w-8 h-8 -bottom-2 -right-2',
        default => 'w-5 h-5 -bottom-1 -right-1'
    };
@endphp

<div {{ $attributes->merge(['class' => 'relative flex-shrink-0']) }}>
    <div class="{{ $sizeClasses }} rounded-full border border-primary/20 overflow-hidden bg-gray-100">
        @if($teacher->avatar)
            <img src="{{ asset('storage/' . $teacher->avatar) }}" 
                 alt="{{ $teacher->full_name }}" 
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-primary bg-primary/10">
                <i class="ri-user-line {{ $textSizeClasses }}"></i>
            </div>
        @endif
    </div>
    
    @if($showBadge)
        <!-- Verified Badge -->
        <div class="absolute {{ $badgeSizeClasses }} bg-green-500 text-white rounded-full flex items-center justify-center">
            <i class="ri-shield-check-line text-xs"></i>
        </div>
    @endif
</div>