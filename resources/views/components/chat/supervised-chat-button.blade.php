@props([
    'teacher' => null,           // Teacher User model or profile with user relation
    'student' => null,           // Student User model or profile with user relation
    'entityType' => null,        // 'quran_individual', 'academic_lesson', 'quran_circle', 'interactive_course'
    'entityId' => null,          // ID of the related entity
    'variant' => 'default',      // 'default', 'icon-only', 'card', 'link'
    'size' => 'md',              // 'sm', 'md', 'lg'
    'class' => '',               // Additional CSS classes
    'showLabel' => true,         // Whether to show text label
    'label' => null,             // Custom label override
])

@php
    use App\Models\User;

    $subdomain = request()->route('subdomain') ?? auth()->user()?->academy?->subdomain ?? 'itqan-academy';

    // Resolve teacher User model
    $teacherUser = null;
    if ($teacher instanceof User) {
        $teacherUser = $teacher;
    } elseif ($teacher && method_exists($teacher, 'getAttribute') && $teacher->user) {
        $teacherUser = $teacher->user;
    }

    // Resolve student User model
    $studentUser = null;
    if ($student instanceof User) {
        $studentUser = $student;
    } elseif ($student && method_exists($student, 'getAttribute') && $student->user) {
        $studentUser = $student->user;
    }

    // Check if teacher has supervisor assigned
    $hasSupervisor = $teacherUser && $teacherUser->hasSupervisor();

    // Only show button if all required data is available and teacher has supervisor
    $canShow = $hasSupervisor && $teacherUser && $studentUser && $entityType && $entityId;

    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };

    $iconSize = match($size) {
        'sm' => 'text-sm',
        'lg' => 'text-xl',
        default => 'text-base',
    };

    // Variant styles
    $variantClasses = match($variant) {
        'icon-only' => 'inline-flex items-center justify-center bg-green-50 border-2 border-green-200 rounded-lg text-green-700 hover:bg-green-100 transition-colors',
        'card' => 'flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors w-full',
        'link' => 'inline-flex items-center gap-2 text-green-600 hover:text-green-700 hover:underline transition-colors',
        default => 'inline-flex items-center justify-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium',
    };

    $routeUrl = $canShow ? route('chat.start-supervised', [
        'subdomain' => $subdomain,
        'teacher' => $teacherUser->id,
        'student' => $studentUser->id,
        'entityType' => $entityType,
        'entityId' => $entityId,
    ]) : '#';

    // Determine button label - auto-detect based on current user or use custom label
    $currentUserId = auth()->id();
    $isCurrentUserTeacher = $teacherUser && $currentUserId === $teacherUser->id;
    $buttonLabel = $label ?? ($isCurrentUserTeacher ? __('chat.message_student') : __('chat.message_teacher'));
@endphp

@if($canShow)
    <a href="{{ $routeUrl }}"
       class="{{ $variantClasses }} {{ $variant !== 'link' ? $sizeClasses : '' }} {{ $class }}"
       title="{{ $buttonLabel }}">

        @if($variant === 'card')
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <i class="ri-message-3-line text-green-600 {{ $iconSize }}"></i>
            </div>
            <div class="flex-1">
                <span class="text-gray-700 font-medium block">{{ $buttonLabel }}</span>
            </div>
            <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
        @elseif($variant === 'icon-only')
            <i class="ri-message-3-line {{ $iconSize }}"></i>
        @elseif($variant === 'link')
            <i class="ri-message-3-line {{ $iconSize }}"></i>
            @if($showLabel)
                <span>{{ $buttonLabel }}</span>
            @endif
        @else
            <i class="ri-message-3-line rtl:ml-2 ltr:mr-2 {{ $iconSize }}"></i>
            @if($showLabel)
                <span>{{ $buttonLabel }}</span>
            @endif
        @endif
    </a>
@endif
