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
                            {{ $review->student->name ?? $review->user->name ?? __('components.reviews.list.student') }}
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
            <div class="bg-gray-50 rounded-xl py-12 text-center">
                <div class="max-w-md mx-auto px-4">
                    <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-star-line text-3xl text-amber-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('components.reviews.list.no_reviews_yet') }}</h3>
                </div>
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
                <span>{{ __('components.reviews.list.view_all_reviews') }} ({{ $reviews->count() }})</span>
                <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line"></i>
            </a>
        </div>
    @endif
</div>
