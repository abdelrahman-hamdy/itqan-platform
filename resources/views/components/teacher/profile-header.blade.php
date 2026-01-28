@props([
    'teacher',
    'stats',
    'color' => 'yellow', // yellow or violet
    'badgeIcon' => 'ri-book-read-line',
    'badgeText' => __('components.teacher.profile_header.default_badge')
])

@php
    $colorClasses = [
        'yellow' => [
            'border' => 'border-yellow-600',
        ],
        'violet' => [
            'border' => 'border-violet-600',
        ]
    ];

    $classes = $colorClasses[$color] ?? $colorClasses['yellow'];

    // Determine teacher type based on color
    $teacherType = $color === 'violet' ? 'academic_teacher' : 'quran_teacher';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-8 mb-4 md:mb-8">
    <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-center md:items-start">

      <!-- Avatar -->
      <div class="flex-shrink-0">
        <x-avatar
          :user="$teacher"
          size="xl"
          :showBorder="true"
          :borderColor="$color"
          :userType="$teacherType"
          :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
      </div>

      <!-- Info -->
      <div class="flex-1 w-full">
        <!-- Name & Badge -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-3 md:mb-6 text-center md:text-start">
          <div class="flex flex-col md:flex-row items-center gap-2 md:gap-3">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900">{{ $teacher->full_name }}</h1>
            <!-- Teacher Badge -->
            <span class="px-2.5 py-1 md:px-3 md:py-1.5 bg-{{ $color }}-600 text-white rounded-lg text-xs md:text-sm font-bold inline-flex items-center gap-1.5">
              <i class="{{ $badgeIcon }}"></i>
              <span>{{ $badgeText }}</span>
            </span>
          </div>

          <!-- Star Rating -->
          <div class="flex items-center justify-center md:justify-end gap-1 mt-2 md:mt-0">
            @if($stats['rating'] > 0)
              @for($i = 1; $i <= 5; $i++)
                @if($i <= floor($stats['rating']))
                  <i class="ri-star-fill text-yellow-500 text-base md:text-lg"></i>
                @elseif($i - $stats['rating'] < 1)
                  <i class="ri-star-half-fill text-yellow-500 text-base md:text-lg"></i>
                @else
                  <i class="ri-star-line text-gray-300 text-base md:text-lg"></i>
                @endif
              @endfor
              <span class="text-gray-900 font-bold me-2 text-sm md:text-base">{{ number_format($stats['rating'], 1) }}</span>
            @else
              <span class="text-gray-500 text-xs md:text-sm">{{ __('components.teacher.profile_header.new_teacher') }}</span>
            @endif
          </div>
        </div>

        <!-- Bio -->
        @if($teacher->bio_arabic || $teacher->bio)
          <p class="text-gray-600 leading-relaxed mb-4 md:mb-6 text-sm md:text-base text-center md:text-start">{{ $teacher->bio_arabic ?? $teacher->bio }}</p>
        @endif

        <!-- Slot for qualifications -->
        {{ $slot }}
      </div>
    </div>
</div>
