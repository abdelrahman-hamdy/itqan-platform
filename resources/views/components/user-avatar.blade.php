@props(['user', 'size' => 'md', 'showBadge' => false, 'showStatus' => false])

@php
    $sizeClasses = match($size) {
        'xs' => 'w-8 h-8',
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16', 
        'lg' => 'w-24 h-24',
        'xl' => 'w-32 h-32',
        default => 'w-16 h-16'
    };
    
    $textSizeClasses = match($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-lg',
        'lg' => 'text-2xl', 
        'xl' => 'text-4xl',
        default => 'text-lg'
    };

    $badgeSizeClasses = match($size) {
        'xs' => 'w-3 h-3 -bottom-0.5 -right-0.5',
        'sm' => 'w-4 h-4 -bottom-1 -right-1',
        'md' => 'w-5 h-5 -bottom-1 -right-1',
        'lg' => 'w-6 h-6 -bottom-1 -right-1',
        'xl' => 'w-8 h-8 -bottom-2 -right-2',
        default => 'w-5 h-5 -bottom-1 -right-1'
    };

    // Get user data from different possible structures
    $userName = 'مستخدم';
    $avatarPath = null;
    $userType = 'student';
    $isOnline = false;
    
    if ($user) {
        // Handle different user structures
        $userName = $user->full_name ?? 
                   ($user->first_name && $user->last_name ? $user->first_name . ' ' . $user->last_name : null) ?? 
                   $user->first_name ?? 
                   $user->name ?? 
                   'مستخدم';
                   
        $avatarPath = $user->avatar ?? 
                     $user->user?->avatar ?? 
                     $user->profile?->avatar ??
                     $user->studentProfile?->avatar ??
                     $user->teacherProfile?->avatar ?? 
                     null;
                     
        $userType = $user->user_type ?? $user->type ?? 'student';
        $isOnline = $user->is_online ?? $user->isOnline ?? false;
    }

    // User type specific styling and icons
    $typeConfig = match($userType) {
        'quran_teacher' => [
            'border' => 'border-blue-200',
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600',
            'bgFallback' => 'bg-blue-100',
            'icon' => 'ri-user-star-line',
            'badge' => 'معلم قرآن',
            'badgeColor' => 'bg-blue-500'
        ],
        'academic_teacher' => [
            'border' => 'border-green-200',
            'bg' => 'bg-green-50',
            'text' => 'text-green-600',
            'bgFallback' => 'bg-green-100',
            'icon' => 'ri-book-line',
            'badge' => 'معلم أكاديمي',
            'badgeColor' => 'bg-green-500'
        ],
        'parent' => [
            'border' => 'border-purple-200',
            'bg' => 'bg-purple-50',
            'text' => 'text-purple-600',
            'bgFallback' => 'bg-purple-100',
            'icon' => 'ri-parent-line',
            'badge' => 'ولي أمر',
            'badgeColor' => 'bg-purple-500'
        ],
        'supervisor' => [
            'border' => 'border-orange-200',
            'bg' => 'bg-orange-50',
            'text' => 'text-orange-600',
            'bgFallback' => 'bg-orange-100',
            'icon' => 'ri-shield-user-line',
            'badge' => 'مشرف',
            'badgeColor' => 'bg-orange-500'
        ],
        'academy_admin', 'admin' => [
            'border' => 'border-red-200',
            'bg' => 'bg-red-50',
            'text' => 'text-red-600',
            'bgFallback' => 'bg-red-100',
            'icon' => 'ri-shield-star-line',
            'badge' => 'مدير',
            'badgeColor' => 'bg-red-500'
        ],
        default => [
            'border' => 'border-primary/20',
            'bg' => 'bg-gray-100',
            'text' => 'text-primary',
            'bgFallback' => 'bg-primary/10',
            'icon' => 'ri-user-line',
            'badge' => 'طالب',
            'badgeColor' => 'bg-primary'
        ]
    };
    
    $config = $typeConfig[$userType] ?? $typeConfig['default'];
    $initials = mb_substr($userName, 0, 1, 'UTF-8');
@endphp

<div {{ $attributes->merge(['class' => 'relative flex-shrink-0']) }}>
    <div class="{{ $sizeClasses }} rounded-full {{ $config['border'] }} overflow-hidden {{ $config['bg'] }}">
        @if($avatarPath)
            <img src="{{ asset('storage/' . $avatarPath) }}" 
                 alt="{{ $userName }}" 
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center {{ $config['text'] }} {{ $config['bgFallback'] }}">
                @if($user && $initials && mb_strlen($initials, 'UTF-8') > 0)
                    <span class="font-semibold {{ $textSizeClasses }}">{{ $initials }}</span>
                @else
                    <i class="{{ $config['icon'] }} {{ $textSizeClasses }}"></i>
                @endif
            </div>
        @endif
    </div>
    
    @if($showStatus && $size !== 'xs')
        <!-- Online Status Indicator -->
        <div class="absolute {{ $badgeSizeClasses }} {{ $isOnline ? 'bg-green-500' : 'bg-gray-300' }} border-2 border-white rounded-full"></div>
    @endif
    
    @if($showBadge && $size !== 'xs' && $size !== 'sm')
        <!-- Role Badge -->
        <div class="absolute {{ $badgeSizeClasses }} {{ $config['badgeColor'] }} text-white rounded-full flex items-center justify-center">
            <i class="{{ $config['icon'] }} text-xs"></i>
        </div>
    @endif
</div>
