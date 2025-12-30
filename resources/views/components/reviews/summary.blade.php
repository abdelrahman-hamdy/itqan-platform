@props([
    'rating' => 0,
    'totalReviews' => 0,
    'showBreakdown' => false,
    'reviews' => collect(),
])

@php
    $rating = floatval($rating);

    // Calculate breakdown if needed
    $breakdown = [];
    if ($showBreakdown && $reviews->isNotEmpty()) {
        for ($i = 5; $i >= 1; $i--) {
            $count = $reviews->where('rating', $i)->count();
            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
            $breakdown[$i] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl p-6 border border-gray-100 shadow-sm']) }}>
    <div class="flex items-start gap-6">
        {{-- Main Rating --}}
        <div class="text-center">
            <div class="text-5xl font-bold text-gray-900">
                {{ number_format($rating, 1) }}
            </div>
            <x-reviews.star-rating
                :rating="$rating"
                size="md"
                :show-count="false"
                class="justify-center mt-2"
            />
            <p class="text-sm text-gray-500 mt-1">
                {{ $totalReviews }} {{ $totalReviews == 1 ? __('components.reviews.summary.review') : __('components.reviews.summary.reviews') }}
            </p>
        </div>

        {{-- Breakdown --}}
        @if($showBreakdown && !empty($breakdown))
            <div class="flex-1 space-y-2">
                @foreach($breakdown as $stars => $data)
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 w-4">{{ $stars }}</span>
                        <i class="ri-star-fill text-yellow-400 text-sm"></i>
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-yellow-400 rounded-full transition-all duration-300"
                                style="width: {{ $data['percentage'] }}%"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-500 w-8 text-end">{{ $data['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
