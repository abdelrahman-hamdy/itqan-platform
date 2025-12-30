@props(['circle', 'academy', 'showEnrollmentStatus' => false])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
  <div class="flex items-start justify-between mb-4">
    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
      <i class="ri-group-line text-green-600 text-xl"></i>
    </div>
    <div class="flex gap-1">
      @if($circle->gender_type)
        @php
          $genderLabels = [
            'male' => __('components.cards.quran_circle.gender_male'),
            'female' => __('components.cards.quran_circle.gender_female'),
            'mixed' => __('components.cards.quran_circle.gender_mixed')
          ];
          $genderColors = [
            'male' => 'bg-blue-100 text-blue-800',
            'female' => 'bg-pink-100 text-pink-800',
            'mixed' => 'bg-purple-100 text-purple-800'
          ];
        @endphp
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $genderColors[$circle->gender_type] ?? 'bg-gray-100 text-gray-800' }}">
          {{ $genderLabels[$circle->gender_type] ?? $circle->gender_type }}
        </span>
      @endif

      @if($circle->age_group)
        @php
          $ageLabels = [
            'children' => __('components.cards.quran_circle.age_children'),
            'youth' => __('components.cards.quran_circle.age_youth'),
            'adults' => __('components.cards.quran_circle.age_adults'),
            'all_ages' => __('components.cards.quran_circle.age_all')
          ];
          $ageColors = [
            'children' => 'bg-green-100 text-green-800',
            'youth' => 'bg-orange-100 text-orange-800',
            'adults' => 'bg-indigo-100 text-indigo-800',
            'all_ages' => 'bg-teal-100 text-teal-800'
          ];
        @endphp
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ageColors[$circle->age_group] ?? 'bg-gray-100 text-gray-800' }}">
          {{ $ageLabels[$circle->age_group] ?? $circle->age_group }}
        </span>
      @endif
    </div>
  </div>
  
  <h3 class="font-semibold text-gray-900 mb-2">{{ $circle->name ?? $circle->name_ar ?? $circle->name_en ?? '' }}</h3>
  @if($circle->description || $circle->description_ar || $circle->description_en)
    <p class="text-sm text-gray-600 mb-4">{{ $circle->description ?? $circle->description_ar ?? $circle->description_en }}</p>
  @endif
  
  <div class="space-y-2">
    @if($circle->quranTeacher?->user->full_name || $circle->quranTeacher?->user->name)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-user-star-line me-2"></i>
        <span>{{ $circle->quranTeacher->user->full_name ?? $circle->quranTeacher->user->name }}</span>
      </div>
    @endif

    @if($circle->students_count ?? 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line me-2"></i>
        <span>{{ $circle->students_count }} {{ __('components.cards.quran_circle.students_count') }}</span>
      </div>
    @endif

    @if($circle->schedule_days_text)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-calendar-line me-2"></i>
        <span>{{ $circle->schedule_days_text }}</span>
      </div>
    @endif

    @if($circle->max_students)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-user-add-line me-2"></i>
        <span>{{ __('components.cards.quran_circle.capacity') }}: {{ $circle->max_students }} {{ __('components.cards.quran_circle.students_count') }}</span>
      </div>
    @endif

    @if($circle->level)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-book-open-line me-2"></i>
        <span>{{ __('components.cards.quran_circle.level') }}: {{ $circle->level }}</span>
      </div>
    @endif
  </div>

  <!-- Action Button -->
  <div class="mt-6">
    <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain, 'circleId' => $circle->id]) }}"
       class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors text-center block">
      {{ __('components.cards.quran_circle.view_details') }}
    </a>
  </div>
</div>
