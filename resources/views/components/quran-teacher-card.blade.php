@props(['teacher', 'academy', 'showEnrollmentStatus' => false])

@php
  $rating = $teacher->avg_rating ?? $teacher->rating ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
  {{-- Header with Avatar beside Name --}}
  <div class="flex items-start gap-4 mb-3">
    <x-avatar
      :user="$teacher"
      size="md"
      userType="quran_teacher"
      :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
      class="flex-shrink-0" />

    <div class="flex-1 min-w-0">
      <h3 class="font-semibold text-gray-900 mb-1">
        {{ $teacher->full_name ?? $teacher->user->name ?? '' }}
      </h3>
      {{-- Qualification Badge --}}
      @if($teacher->educational_qualification)
      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
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
      <i class="ri-time-line text-yellow-600 me-1"></i>
      {{ $teacher->teaching_experience_years }}
      {{ $teacher->teaching_experience_years == 1 ? __('components.cards.quran_teacher.year_experience') : __('components.cards.quran_teacher.years_experience') }}
    </span>
    @endif

    @if($teacher->available_time_start && $teacher->available_time_end)
    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
      <i class="ri-time-line me-1 text-yellow-600"></i>
      {{ formatTimeArabic($teacher->available_time_start) }} - {{ formatTimeArabic($teacher->available_time_end) }}
    </span>
    @endif
  </div>

  <div class="space-y-2">
    @if(!empty($teacher->certifications) && is_array($teacher->certifications) && count($teacher->certifications) > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-award-line me-2"></i>
        <span>{{ implode('، ', array_slice($teacher->certifications, 0, 2)) }}</span>
      </div>
    @endif

    @if($teacher->total_students ?? 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line me-2"></i>
        <span>{{ $teacher->total_students }} {{ __('components.cards.quran_teacher.students_count') }}</span>
      </div>
    @endif

    @if($rating > 0)
      <x-reviews.star-rating
        :rating="$rating"
        :total-reviews="$teacher->total_reviews ?? null"
        size="sm"
      />
    @endif

    @if($teacher->offers_trial_sessions)
    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-green-50 text-green-700 border border-green-200">
      <i class="ri-play-circle-line me-1"></i>
      {{ __('components.cards.quran_teacher.offers_trial') }}
    </span>
    @endif
  </div>

  {{-- Action Button --}}
  <div class="mt-6">
    <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
       class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors text-center block">
      {{ __('components.cards.quran_teacher.view_details') }}
    </a>
  </div>
</div>
