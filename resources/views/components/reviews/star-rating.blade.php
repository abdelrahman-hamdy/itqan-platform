@props([
    'rating' => 0,
    'totalReviews' => null,
    'size' => 'md', // sm, md, lg
    'showCount' => true,
    'interactive' => false,
])

@php
    $rating = floatval($rating);
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

    $sizeClasses = match($size) {
        'sm' => 'text-sm',
        'lg' => 'text-2xl',
        default => 'text-lg',
    };

    $gapClasses = match($size) {
        'sm' => 'gap-0.5',
        'lg' => 'gap-1',
        default => 'gap-0.5',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center ' . $gapClasses]) }}>
    {{-- Stars --}}
    <div class="flex items-center {{ $gapClasses }}">
        {{-- Full Stars --}}
        @for($i = 0; $i < $fullStars; $i++)
            <i class="ri-star-fill text-yellow-400 {{ $sizeClasses }}"></i>
        @endfor

        {{-- Half Star --}}
        @if($hasHalfStar)
            <i class="ri-star-half-fill text-yellow-400 {{ $sizeClasses }}"></i>
        @endif

        {{-- Empty Stars --}}
        @for($i = 0; $i < $emptyStars; $i++)
            <i class="ri-star-line text-gray-300 {{ $sizeClasses }}"></i>
        @endfor
    </div>

    {{-- Rating Value & Count --}}
    @if($showCount && ($rating > 0 || $totalReviews !== null))
        <div class="flex items-center gap-1 text-gray-600 {{ $size === 'sm' ? 'text-xs' : 'text-sm' }}">
            @if($rating > 0)
                <span class="font-medium">{{ number_format($rating, 1) }}</span>
            @endif
            @if($totalReviews !== null)
                <span>({{ $totalReviews }} {{ $totalReviews == 1 ? 'تقييم' : 'تقييمات' }})</span>
            @endif
        </div>
    @endif
</div>
