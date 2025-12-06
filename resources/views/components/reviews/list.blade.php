@props([
    'reviews' => collect(),
    'showEmpty' => true,
    'limit' => null,
    'showViewAll' => false,
    'viewAllUrl' => null,
])

@php
    $displayReviews = $limit ? $reviews->take($limit) : $reviews;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @forelse($displayReviews as $review)
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    {{-- Student Avatar - Using Unified Avatar Component --}}
                    <x-avatar
                        :user="$review->student ?? $review->user"
                        size="sm"
                    />

                    <div>
                        {{-- Student Name --}}
                        <p class="font-semibold text-gray-900">
                            {{ $review->student->name ?? $review->user->name ?? 'طالب' }}
                        </p>

                        {{-- Date --}}
                        <p class="text-xs text-gray-500">
                            {{ $review->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                {{-- Rating Stars --}}
                <x-reviews.star-rating
                    :rating="$review->rating"
                    size="sm"
                    :show-count="false"
                />
            </div>

            {{-- Comment --}}
            @if($review->comment ?? $review->review)
                <p class="text-gray-700 text-sm leading-relaxed">
                    {{ $review->comment ?? $review->review }}
                </p>
            @endif
        </div>
    @empty
        @if($showEmpty)
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <i class="ri-star-line text-3xl text-gray-400"></i>
                </div>
                <p class="text-gray-500">لا توجد تقييمات بعد</p>
            </div>
        @endif
    @endforelse

    {{-- View All Link --}}
    @if($showViewAll && $reviews->count() > $limit && $viewAllUrl)
        <div class="text-center pt-2">
            <a
                href="{{ $viewAllUrl }}"
                class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 text-sm font-medium"
            >
                <span>عرض جميع التقييمات ({{ $reviews->count() }})</span>
                <i class="ri-arrow-left-s-line"></i>
            </a>
        </div>
    @endif
</div>
