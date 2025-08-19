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

    // For teachers, we might have different naming patterns
    $teacherName = $teacher->full_name ?? 
                   ($teacher->first_name && $teacher->last_name ? $teacher->first_name . ' ' . $teacher->last_name : null) ?? 
                   $teacher->first_name ?? 
                   $teacher->name ??
                   ($teacher->user ? ($teacher->user->name ?? 'معلم') : 'معلم');
                   
    // Check for avatar in different possible locations
    $avatarPath = $teacher->avatar ?? 
                  $teacher->user?->avatar ?? 
                  ($teacher->teacherProfile?->avatar ?? null);
@endphp

<div {{ $attributes->merge(['class' => 'relative flex-shrink-0']) }}>
    <div class="{{ $sizeClasses }} rounded-full border border-blue-200 overflow-hidden bg-blue-50">
        @if($avatarPath)
            <img src="{{ asset('storage/' . $avatarPath) }}" 
                 alt="{{ $teacherName }}" 
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-blue-600 bg-blue-100">
                <i class="ri-user-star-line {{ $textSizeClasses }}"></i>
            </div>
        @endif
    </div>
    
    @if($showBadge)
        <!-- Verified Teacher Badge -->
        <div class="absolute {{ $badgeSizeClasses }} bg-blue-500 text-white rounded-full flex items-center justify-center">
            <i class="ri-shield-star-line text-xs"></i>
        </div>
    @endif
</div>