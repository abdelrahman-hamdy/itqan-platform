@props(['course', 'academy', 'enrollment' => null])

@php
  $isEnrolled = $enrollment !== null;
  $progress = $enrollment->progress_percentage ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-all duration-300 flex flex-col">
  <div class="flex items-start justify-between mb-4">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
      {{ $isEnrolled ? 'bg-green-100 text-green-800' : ($course->is_published ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
      {{ $isEnrolled ? __('components.cards.interactive_course.status_active') : ($course->is_published ? __('components.cards.interactive_course.status_available') : __('components.cards.interactive_course.status_unavailable')) }}
    </span>
  </div>

  <div class="flex items-start justify-between mb-2">
    <h3 class="font-bold text-xl text-gray-900 line-clamp-2 flex-1">{{ $course->title }}</h3>
    @if(($course->avg_rating ?? 0) > 0)
      <x-reviews.star-rating
        :rating="$course->avg_rating ?? 0"
        :total-reviews="$course->total_reviews ?? null"
        size="sm"
        :show-count="false"
        class="flex-shrink-0 me-2"
      />
    @endif
  </div>
  <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $course->description }}</p>

  <div class="grid grid-cols-1 gap-3 mb-4">
    <div class="flex items-center gap-3 bg-blue-50 rounded-lg p-3">
      <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
        <i class="ri-user-star-line text-blue-600 text-lg"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.interactive_course.teacher_label') }}</p>
        <p class="text-sm font-semibold text-gray-900 truncate">{{ $course->assignedTeacher->full_name ?? __('components.cards.interactive_course.teacher_unspecified') }}</p>
      </div>
    </div>

    @if($course->subject || $course->gradeLevel)
    <div class="grid grid-cols-2 gap-3">
      @if($course->subject)
      <div class="flex items-center gap-2 bg-blue-50 rounded-lg p-3">
        <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
          <i class="ri-bookmark-line text-blue-600 text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.interactive_course.subject_label') }}</p>
          <p class="text-xs font-semibold text-gray-900 truncate">{{ $course->subject->name }}</p>
        </div>
      </div>
      @endif

      @if($course->gradeLevel)
      <div class="flex items-center gap-2 bg-blue-50 rounded-lg p-3">
        <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
          <i class="ri-graduation-cap-line text-blue-600 text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.interactive_course.grade_label') }}</p>
          <p class="text-xs font-semibold text-gray-900 truncate">{{ $course->gradeLevel->getDisplayName() }}</p>
        </div>
      </div>
      @endif
    </div>
    @endif

    <div class="bg-blue-50 rounded-lg p-3">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
          <i class="ri-calendar-line text-blue-600 text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.interactive_course.duration_label') }}</p>
          <p class="text-sm font-semibold text-gray-900">{{ $course->total_sessions ?? 0 }} {{ __('components.cards.interactive_course.session_unit') }} <span class="text-gray-400">•</span> {{ $course->duration_weeks ?? 0 }} {{ __('components.cards.interactive_course.week_unit') }}</p>
        </div>
      </div>
      @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
      <div class="flex flex-wrap gap-1 mt-2 ms-12">
        @foreach($course->schedule as $day => $time)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-white text-blue-700 border border-blue-200">
          {{ $day }}: {{ $time }}
        </span>
        @endforeach
      </div>
      @endif
    </div>
  </div>

  @if($isEnrolled)
  <!-- Progress Bar for enrolled courses -->
  <div class="mb-4">
    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
      <span>{{ __('components.cards.interactive_course.progress_label') }}</span>
      <span>{{ $progress }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
      <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
    </div>
  </div>
  @endif

  <!-- Spacer to push button to bottom -->
  <div class="flex-grow"></div>

  @if($isEnrolled)
  <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
     class="w-full bg-blue-50 border-2 border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors text-center block mt-auto">
    <i class="ri-eye-line me-1"></i>
    {{ __('components.cards.interactive_course.view_details') }}
  </a>
  @else
  <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
     class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg text-sm font-semibold hover:bg-blue-600 transition-colors text-center block mt-auto">
    <i class="ri-information-line me-1"></i>
    {{ __('components.cards.interactive_course.view_details') }}{{ $course->student_price ? ' - ' . number_format($course->student_price) . ' ' . __('components.cards.interactive_course.currency') : '' }}
  </a>
  @endif
</div>
