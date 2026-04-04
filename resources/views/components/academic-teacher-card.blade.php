@props(['teacher', 'academy'])

@php
  $rating = $teacher->avg_rating ?? $teacher->rating ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
  {{-- Header with Avatar beside Name --}}
  <div class="flex items-start gap-4 mb-3">
    <x-avatar
      :user="$teacher"
      size="md"
      userType="academic_teacher"
      :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
      class="flex-shrink-0" />

    <div class="flex-1 min-w-0">
      <h3 class="font-semibold text-gray-900 mb-1">
        {{ $teacher->full_name ?? $teacher->user->name ?? '' }}
      </h3>
      {{-- Qualification Badge --}}
      @if($teacher->educational_qualification)
      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
        <i class="ri-graduation-cap-line me-1"></i>
        {{ $teacher->educational_qualification instanceof \App\Enums\EducationalQualification ? $teacher->educational_qualification->label() : \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}
      </span>
      @endif
    </div>
  </div>

  {{-- Bio --}}
  @if($teacher->bio_arabic || $teacher->bio_english)
    <p class="text-sm text-gray-600 line-clamp-2 mb-3">{{ $teacher->bio_arabic ?? $teacher->bio_english }}</p>
  @endif

  {{-- Info Tags --}}
  <div class="flex flex-wrap items-center gap-2 mb-3">
    @if($teacher->teaching_experience_years)
    <span class="inline-flex items-center text-sm text-gray-600">
      <i class="ri-time-line text-violet-600 me-1"></i>
      {{ $teacher->teaching_experience_years }}
      {{ $teacher->teaching_experience_years == 1 ? __('components.cards.academic_teacher.year_experience') : __('components.cards.academic_teacher.years_experience') }}
    </span>
    @endif

    @if($teacher->subjects && $teacher->subjects->count() > 0)
    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
      <i class="ri-book-line me-1 text-violet-600"></i>
      {{ $teacher->subjects->count() }} {{ __('components.cards.academic_teacher.subjects_count') }}
    </span>
    @endif
  </div>

  <div class="space-y-2">
    @if($teacher->total_students ?? 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line me-2 text-violet-600"></i>
        <span>{{ $teacher->total_students }} {{ __('components.cards.academic_teacher.students_count') }}</span>
      </div>
    @endif

    @if($teacher->specialization)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-bookmark-line me-2 text-violet-600"></i>
        <span class="truncate">{{ $teacher->specialization }}</span>
      </div>
    @endif

    @if($rating > 0)
      {{-- Full rating (sm+) --}}
      <div class="hidden sm:block">
        <x-reviews.star-rating
          :rating="$rating"
          :total-reviews="$teacher->total_reviews ?? null"
          size="sm"
        />
      </div>
      {{-- Compact rating (mobile) --}}
      <div class="flex sm:hidden items-center gap-1 text-xs text-gray-600">
        <i class="ri-star-fill text-yellow-400 text-sm"></i>
        <span class="font-medium">{{ number_format($rating, 1) }}</span>
        @if($teacher->total_reviews ?? null)
          <span>({{ $teacher->total_reviews }})</span>
        @endif
      </div>
    @endif
  </div>

  {{-- Action Button --}}
  <div class="mt-6">
    <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
       class="w-full bg-violet-600 text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-violet-700 transition-colors text-center block">
      {{ __('components.cards.academic_teacher.view_details') }}
    </a>
  </div>
</div>
