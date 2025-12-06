@props([
    'reviewableType' => null, // Full class name: App\Models\QuranTeacherProfile
    'reviewableId' => null,
    'reviewType' => 'teacher', // 'teacher' or 'course'
    'reviews' => collect(),
    'rating' => 0,
    'totalReviews' => 0,
    'showSummary' => true,
    'showBreakdown' => false,
    'showReviewForm' => true,
    'limit' => 5,
])

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    {{-- Section Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold text-gray-900">
            {{ $reviewType === 'teacher' ? 'تقييمات الطلاب' : 'تقييمات الدورة' }}
        </h3>

        {{-- Review Form Button --}}
        @if($showReviewForm && auth()->check())
            @livewire('review-form', [
                'reviewType' => $reviewType,
                'reviewableType' => $reviewableType,
                'reviewableId' => $reviewableId,
            ])
        @endif
    </div>

    {{-- Summary --}}
    @if($showSummary && $totalReviews > 0)
        <x-reviews.summary
            :rating="$rating"
            :total-reviews="$totalReviews"
            :show-breakdown="$showBreakdown"
            :reviews="$reviews"
        />
    @endif

    {{-- Reviews List --}}
    <x-reviews.list
        :reviews="$reviews"
        :limit="$limit"
        :show-view-all="$reviews->count() > $limit"
    />
</div>
