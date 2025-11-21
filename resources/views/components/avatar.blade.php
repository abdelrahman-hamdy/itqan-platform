@props([
    'user' => null,
    'size' => 'md',
    'showBorder' => false,
    'showBadge' => false,
    'showStatus' => false,
    'borderColor' => null, // 'yellow', 'violet', or null for auto-detection
    'userType' => null, // Explicit user type: 'student', 'quran_teacher', 'academic_teacher', 'parent', 'supervisor', 'admin'
    'gender' => null, // Explicit gender: 'male' or 'female'
])

@php
    // Size classes for avatar container
    $sizeClasses = match($size) {
        'xs' => 'w-8 h-8',
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16',
        'lg' => 'w-24 h-24',
        'xl' => 'w-32 h-32',
        '2xl' => 'w-40 h-40',
        default => 'w-16 h-16'
    };

    // Text size for fallback icons/initials
    $textSizeClasses = match($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-lg',
        'lg' => 'text-2xl',
        'xl' => 'text-4xl',
        '2xl' => 'text-5xl',
        default => 'text-lg'
    };

    // Badge/status indicator size
    $badgeSizeClasses = match($size) {
        'xs' => 'w-3 h-3 -bottom-0.5 -right-0.5',
        'sm' => 'w-4 h-4 -bottom-1 -right-1',
        'md' => 'w-5 h-5 -bottom-1 -right-1',
        'lg' => 'w-6 h-6 -bottom-1 -right-1',
        'xl' => 'w-8 h-8 -bottom-2 -right-2',
        '2xl' => 'w-10 h-10 -bottom-2 -right-2',
        default => 'w-5 h-5 -bottom-1 -right-1'
    };

    // Extract user data from different possible structures
    // Initialize with defaults first
    $userName = 'مستخدم';
    $avatarPath = null;
    $detectedUserType = 'student';
    $detectedGender = 'male';
    $isOnline = false;

    if ($user) {
        // Get user name from various possible properties
        $userName = $user->full_name ??
                   ($user->first_name && $user->last_name ? $user->first_name . ' ' . $user->last_name : null) ??
                   $user->first_name ??
                   $user->name ??
                   'مستخدم';

        // Get avatar path from various possible properties
        $avatarPath = $user->avatar ??
                     $user->user?->avatar ??
                     $user->profile?->avatar ??
                     $user->studentProfile?->avatar ??
                     $user->teacherProfile?->avatar ??
                     $user->academicTeacherProfile?->avatar ??
                     $user->quranTeacherProfile?->avatar ??
                     null;

        // Auto-detect user type if not explicitly provided - check model class first
        try {
            $userClass = get_class($user);
            if ($userClass === 'App\Models\QuranTeacherProfile' || (isset($user->teacher_code) && !isset($user->student_code))) {
                $detectedUserType = 'quran_teacher';
            } elseif ($userClass === 'App\Models\AcademicTeacherProfile' || (isset($user->subjects) && !isset($user->student_code))) {
                $detectedUserType = 'academic_teacher';
            } else {
                $detectedUserType = $user->user_type ?? $user->type ?? 'student';
            }
        } catch (\Exception $e) {
            // If get_class fails, fall back to property checking
            $detectedUserType = $user->user_type ?? $user->type ?? 'student';
        }

        // Auto-detect gender if not explicitly provided
        $detectedGender = $user->gender ??
                     $user->user?->gender ??
                     $user->studentProfile?->gender ??
                     $user->academicTeacherProfile?->gender ??
                     $user->quranTeacherProfile?->gender ??
                     'male';

        // Check online status
        $isOnline = $user->is_online ?? $user->isOnline ?? false;
    }

    // Use explicit parameters if provided, otherwise use auto-detected values
    $finalUserType = $userType ?? $detectedUserType;
    $finalGender = $gender ?? $detectedGender;

    // User type specific configuration (use final values)
    $config = match($finalUserType) {
        'quran_teacher' => [
            'bgColor' => 'bg-yellow-100',
            'textColor' => 'text-yellow-700',
            'bgFallback' => 'bg-yellow-100',
            'borderColor' => 'border-yellow-600',
            'icon' => 'ri-book-read-line',
            'badge' => 'معلم قرآن',
            'badgeColor' => 'bg-yellow-500',
            'defaultAvatar' => 'app-design-assets/' . ($finalGender === 'female' ? 'female' : 'male') . '-quran-teacher-avatar.png',
        ],
        'academic_teacher' => [
            'bgColor' => 'bg-violet-100',
            'textColor' => 'text-violet-700',
            'bgFallback' => 'bg-violet-100',
            'borderColor' => 'border-violet-600',
            'icon' => 'ri-graduation-cap-line',
            'badge' => 'معلم أكاديمي',
            'badgeColor' => 'bg-violet-500',
            'defaultAvatar' => 'app-design-assets/' . ($finalGender === 'female' ? 'female' : 'male') . '-academic-teacher-avatar.png',
        ],
        'parent' => [
            'bgColor' => 'bg-purple-100',
            'textColor' => 'text-purple-700',
            'bgFallback' => 'bg-purple-100',
            'borderColor' => 'border-purple-600',
            'icon' => 'ri-parent-line',
            'badge' => 'ولي أمر',
            'badgeColor' => 'bg-purple-500',
            'defaultAvatar' => null, // No default avatar for parents
        ],
        'supervisor' => [
            'bgColor' => 'bg-orange-100',
            'textColor' => 'text-orange-700',
            'bgFallback' => 'bg-orange-100',
            'borderColor' => 'border-orange-600',
            'icon' => 'ri-shield-user-line',
            'badge' => 'مشرف',
            'badgeColor' => 'bg-orange-500',
            'defaultAvatar' => null,
        ],
        'academy_admin', 'admin' => [
            'bgColor' => 'bg-red-100',
            'textColor' => 'text-red-700',
            'bgFallback' => 'bg-red-100',
            'borderColor' => 'border-red-600',
            'icon' => 'ri-shield-star-line',
            'badge' => 'مدير',
            'badgeColor' => 'bg-red-500',
            'defaultAvatar' => null,
        ],
        default => [ // student
            'bgColor' => 'bg-blue-100',
            'textColor' => 'text-blue-700',
            'bgFallback' => 'bg-blue-100',
            'borderColor' => 'border-blue-600',
            'icon' => 'ri-user-line',
            'badge' => 'طالب',
            'badgeColor' => 'bg-blue-500',
            'defaultAvatar' => 'app-design-assets/' . ($finalGender === 'female' ? 'female' : 'male') . '-student-avatar.png',
        ]
    };

    // Determine border color (override if provided, otherwise use role-based)
    $finalBorderColor = $borderColor ? "border-{$borderColor}-600" : $config['borderColor'];

    // Get initials for fallback
    $initials = mb_substr($userName, 0, 1, 'UTF-8');
@endphp

<div {{ $attributes->merge(['class' => 'relative flex-shrink-0']) }}>
    @if($showBorder)
        <!-- Colored border wrapper (for teacher profiles) -->
        <div class="rounded-full border-2 {{ $finalBorderColor }} p-1">
            <div class="{{ $sizeClasses }} rounded-full overflow-hidden {{ $config['bgColor'] }}">
                @if($avatarPath)
                    <img src="{{ asset('storage/' . $avatarPath) }}"
                         alt="{{ $userName }}"
                         class="w-full h-full object-cover">
                @elseif($config['defaultAvatar'])
                    <img src="{{ asset('storage/' . $config['defaultAvatar']) }}"
                         alt="{{ $userName }}"
                         class="object-cover"
                         style="width: 120%; height: 120%; object-position: center top; margin-left: -10%; margin-top: 0;">
                @else
                    <div class="w-full h-full flex items-center justify-center {{ $config['textColor'] }} {{ $config['bgFallback'] }}">
                        @if($user && $initials && mb_strlen($initials, 'UTF-8') > 0)
                            <span class="font-semibold {{ $textSizeClasses }}">{{ $initials }}</span>
                        @else
                            <i class="{{ $config['icon'] }} {{ $textSizeClasses }}"></i>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Regular avatar without border -->
        <div class="{{ $sizeClasses }} rounded-full overflow-hidden {{ $config['bgColor'] }}">
            @if($avatarPath)
                <img src="{{ asset('storage/' . $avatarPath) }}"
                     alt="{{ $userName }}"
                     class="w-full h-full object-cover">
            @elseif($config['defaultAvatar'])
                <img src="{{ asset('storage/' . $config['defaultAvatar']) }}"
                     alt="{{ $userName }}"
                     class="object-cover"
                     style="width: 120%; height: 120%; object-position: center top; margin-left: -10%; margin-top: 0;">
            @else
                <div class="w-full h-full flex items-center justify-center {{ $config['textColor'] }} {{ $config['bgFallback'] }}">
                    @if($user && $initials && mb_strlen($initials, 'UTF-8') > 0)
                        <span class="font-semibold {{ $textSizeClasses }}">{{ $initials }}</span>
                    @else
                        <i class="{{ $config['icon'] }} {{ $textSizeClasses }}"></i>
                    @endif
                </div>
            @endif
        </div>
    @endif

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
