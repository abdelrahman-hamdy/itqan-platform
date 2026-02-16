@props(['teacher', 'academy'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
  <!-- Header with Avatar and Name -->
  <div class="p-6 bg-gradient-to-br from-violet-50 via-white to-violet-50 border-b border-violet-100">
    <div class="flex items-start gap-4 mb-3">
      <x-avatar
        :user="$teacher"
        size="md"
        userType="academic_teacher"
        :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
        class="flex-shrink-0" />

      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-gray-900 mb-1">
          {{ $teacher->full_name ?? $teacher->user->name ?? '' }}
        </h3>

        <!-- Rating -->
        @if(($teacher->avg_rating ?? $teacher->rating ?? 0) > 0)
          <x-reviews.star-rating
            :rating="$teacher->avg_rating ?? $teacher->rating ?? 0"
            :total-reviews="$teacher->total_reviews ?? null"
            size="sm"
          />
        @endif
      </div>
    </div>

    <!-- Bio (if available) -->
    @if($teacher->bio_arabic || $teacher->bio_english)
      <p class="text-xs text-gray-600 line-clamp-2 mt-2">
        {{ $teacher->bio_arabic ?? $teacher->bio_english }}
      </p>
    @endif
  </div>

  <!-- Teacher Info - 2 Column Grid -->
  <div class="p-6 space-y-3">
    <!-- Row 1: Experience & Students -->
    <div class="grid grid-cols-2 gap-3">
      @if($teacher->teaching_experience_years)
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i class="ri-time-line text-violet-600"></i>
          <span>{{ $teacher->teaching_experience_years }} سنوات</span>
        </div>
      @endif

      @if($teacher->total_students ?? 0)
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i class="ri-group-line text-violet-600"></i>
          <span>{{ $teacher->total_students }} طالب</span>
        </div>
      @endif
    </div>

    <!-- Row 2: Education & Subjects Count -->
    <div class="grid grid-cols-2 gap-3">
      @if($teacher->educational_qualification)
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i class="ri-graduation-cap-line text-violet-600"></i>
          <span class="truncate">{{ $teacher->educational_qualification }}</span>
        </div>
      @elseif($teacher->education_level_in_arabic)
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i class="ri-graduation-cap-line text-violet-600"></i>
          <span class="truncate">{{ $teacher->education_level_in_arabic }}</span>
        </div>
      @endif

      @if($teacher->subjects && $teacher->subjects->count() > 0)
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i class="ri-book-line text-violet-600"></i>
          <span>{{ $teacher->subjects->count() }} مواد</span>
        </div>
      @endif
    </div>

    <!-- Row 3: Specialization (full width if exists) -->
    @if($teacher->specialization)
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <i class="ri-bookmark-line text-violet-600"></i>
        <span class="truncate">{{ $teacher->specialization }}</span>
      </div>
    @endif

    <!-- Minimum Price Display -->
    @if(!empty($teacher->minimum_price))
      <div class="mt-4 pt-4 border-t border-gray-100">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">{{ __('components.cards.academic_teacher.starts_from') }}</span>
          <div class="flex items-baseline gap-1">
            <span class="text-2xl font-bold text-violet-600">{{ number_format($teacher->minimum_price) }}</span>
            <span class="text-sm text-violet-500">{{ getCurrencySymbol() }}/{{ __('components.cards.academic_teacher.per_month') }}</span>
          </div>
        </div>
      </div>
    @endif
  </div>

  <!-- Action Button -->
  <div class="px-6 pb-6">
    <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
       class="group block w-full bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-600 hover:to-violet-700 text-white px-4 py-3 rounded-xl font-medium transition-all shadow-sm hover:shadow-md text-center transform hover:-translate-y-0.5 relative overflow-hidden">
      <span class="relative z-10 flex items-center justify-center gap-2">
        <span>عرض الملف الشخصي</span>
        <i class="ri-arrow-left-line"></i>
      </span>
      <!-- Shimmer effect -->
      <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
    </a>
  </div>
</div>
