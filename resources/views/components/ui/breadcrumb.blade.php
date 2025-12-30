@props([
    'items' => [],
    'homeRoute' => null, // Optional custom home route
    'showProfile' => true, // Always show profile as first item (default true)
    'viewType' => 'student', // student, teacher, academic_teacher, parent
])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    // Determine profile route based on view type
    $profileRoutes = [
        'student' => 'student.profile',
        'teacher' => 'teacher.profile',
        'academic_teacher' => 'academic-teacher.profile',
        'parent' => 'parent.profile',
    ];

    $profileRoute = $profileRoutes[$viewType] ?? 'student.profile';
    $profileLabel = __('components.ui.breadcrumb.profile');
@endphp

<nav class="mb-4 md:mb-6 lg:mb-8 overflow-x-auto" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 sm:gap-2 text-xs md:text-sm text-gray-500 whitespace-nowrap min-w-max">
        {{-- Profile Link (Always First) --}}
        @if($showProfile)
        <li>
            <a href="{{ route($profileRoute, ['subdomain' => $subdomain]) }}"
               class="hover:text-primary transition-colors min-h-[44px] inline-flex items-center gap-1">
                <i class="ri-user-line text-sm hidden sm:inline"></i>
                <span>{{ $profileLabel }}</span>
            </a>
        </li>
        @endif

        {{-- Dynamic Items --}}
        @foreach($items as $index => $item)
            {{-- Separator - hide on mobile if the associated item is hidden on mobile --}}
            @if($showProfile || $index > 0)
            <li class="{{ $item['hideOnMobile'] ?? false ? 'hidden sm:inline-flex' : '' }} text-gray-300 flex-shrink-0" aria-hidden="true">
                <i class="ri-arrow-left-s-line"></i>
            </li>
            @endif

            {{-- Item --}}
            <li class="{{ $item['hideOnMobile'] ?? false ? 'hidden sm:inline-flex' : 'inline-flex' }} items-center">
                @if(isset($item['route']) && !$loop->last)
                    <a href="{{ $item['route'] }}"
                       class="hover:text-primary transition-colors min-h-[44px] inline-flex items-center gap-1 {{ $item['class'] ?? '' }}">
                        @if(isset($item['icon']))
                            <i class="{{ $item['icon'] }} text-sm hidden sm:inline"></i>
                        @endif
                        <span class="{{ isset($item['truncate']) && $item['truncate'] ? 'truncate max-w-[120px] sm:max-w-[180px] md:max-w-[250px]' : '' }}">
                            {{ $item['label'] }}
                        </span>
                    </a>
                @else
                    <span class="text-gray-900 font-medium inline-flex items-center gap-1 {{ $item['class'] ?? '' }}">
                        @if(isset($item['icon']))
                            <i class="{{ $item['icon'] }} text-sm hidden sm:inline"></i>
                        @endif
                        <span class="{{ isset($item['truncate']) && $item['truncate'] ? 'truncate max-w-[120px] sm:max-w-[180px] md:max-w-[250px]' : '' }}">
                            {{ $item['label'] }}
                        </span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
