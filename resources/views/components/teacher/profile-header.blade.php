@props([
    'teacher',
    'stats',
    'color' => 'yellow', // yellow or violet
    'badgeIcon' => 'ri-book-read-line',
    'badgeText' => 'معلم'
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

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
    <div class="flex flex-col md:flex-row gap-6 items-start">

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
      <div class="flex-1">
        <!-- Name, Badge & Rating -->
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-gray-900">{{ $teacher->full_name }}</h1>
            <!-- Teacher Badge -->
            <span class="px-3 py-1.5 bg-{{ $color }}-600 text-white rounded-lg text-sm font-bold flex items-center gap-1.5">
              <i class="{{ $badgeIcon }}"></i>
              <span>{{ $badgeText }}</span>
            </span>
          </div>

          <!-- Star Rating -->
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1">
              @if($stats['rating'] > 0)
                @for($i = 1; $i <= 5; $i++)
                  @if($i <= floor($stats['rating']))
                    <i class="ri-star-fill text-yellow-500 text-lg"></i>
                  @elseif($i - $stats['rating'] < 1)
                    <i class="ri-star-half-fill text-yellow-500 text-lg"></i>
                  @else
                    <i class="ri-star-line text-gray-300 text-lg"></i>
                  @endif
                @endfor
                <span class="text-gray-900 font-bold mr-2">{{ number_format($stats['rating'], 1) }}</span>
              @else
                <span class="text-gray-500 text-sm">معلم جديد</span>
              @endif
            </div>
          </div>
        </div>

        <!-- Bio -->
        @if($teacher->bio_arabic || $teacher->bio)
          <p class="text-gray-600 leading-relaxed mb-6">{{ $teacher->bio_arabic ?? $teacher->bio }}</p>
        @endif

        <!-- Slot for qualifications -->
        {{ $slot }}
      </div>
    </div>
</div>
