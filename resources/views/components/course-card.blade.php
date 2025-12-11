@props(['course', 'academy', 'showEnrollmentStatus' => false])

<div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
  <!-- Course Image -->
  <div class="relative">
    @if($course->getFirstMediaUrl('thumbnails'))
      <img src="{{ $course->getFirstMediaUrl('thumbnails') }}" alt="{{ $course->title }}" class="w-full h-36 sm:h-40 md:h-48 object-cover">
    @elseif($course->thumbnail_url)
      <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-36 sm:h-40 md:h-48 object-cover">
    @else
      <div class="w-full h-36 sm:h-40 md:h-48 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
        <i class="ri-play-circle-line text-white text-3xl md:text-4xl"></i>
      </div>
    @endif

    <!-- Course Price Badge -->
    <div class="absolute top-2 right-2 md:top-3 md:right-3">
      @if($course->is_free)
        <span class="px-2 md:px-3 py-1 md:py-2 bg-cyan-500 text-white text-xs md:text-sm rounded-full font-semibold">مجاني</span>
      @else
        <span class="px-2 md:px-3 py-1 md:py-2 bg-cyan-500 text-white text-xs md:text-sm rounded-full font-semibold">
          {{ number_format($course->price) }} ر.س
        </span>
      @endif
    </div>
  </div>

  <!-- Course Content -->
  <div class="p-3 sm:p-4 md:p-6">
    <div class="flex items-start justify-between gap-2 mb-2 md:mb-3">
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-sm md:text-base text-gray-900 mb-0.5 md:mb-1 line-clamp-2">{{ $course->title }}</h3>
      </div>
      @if($course->avg_rating)
        <x-reviews.star-rating
          :rating="$course->avg_rating"
          :total-reviews="$course->total_reviews ?? null"
          size="sm"
          :show-count="false"
        />
      @endif
    </div>

    <p class="text-xs md:text-sm text-gray-600 mb-3 md:mb-4 line-clamp-2">{{ $course->description }}</p>

    <!-- Course Meta -->
    <div class="space-y-1.5 md:space-y-2 mb-3 md:mb-4">
      @if($course->subject)
        <div class="flex items-center text-xs md:text-sm text-gray-600">
          <i class="ri-book-line ml-1.5 md:ml-2 text-cyan-500"></i>
          <span class="truncate">{{ $course->subject->name }}</span>
        </div>
      @endif
      @if($course->gradeLevel || $course->difficulty_level)
        <div class="flex items-center justify-between gap-2 text-xs md:text-sm text-gray-600">
          @if($course->gradeLevel)
            <div class="flex items-center min-w-0">
              <i class="ri-graduation-cap-line ml-1.5 md:ml-2 text-cyan-500 flex-shrink-0"></i>
              <span class="truncate">{{ $course->gradeLevel->name }}</span>
            </div>
          @endif
          @if($course->difficulty_level)
            <div class="flex items-center flex-shrink-0">
              <i class="ri-bar-chart-2-line text-cyan-500 ml-1.5 md:ml-2"></i>
              <span>
                @switch($course->difficulty_level)
                  @case('easy') سهل @break
                  @case('medium') متوسط @break
                  @case('hard') صعب @break
                  @default {{ $course->difficulty_level }}
                @endswitch
              </span>
            </div>
          @endif
        </div>
      @endif
      @if($course->total_enrollments)
        <div class="flex items-center text-xs md:text-sm text-gray-600">
          <i class="ri-group-line ml-1.5 md:ml-2"></i>
          <span>{{ $course->total_enrollments }} طالب مسجل</span>
        </div>
      @endif
    </div>

    <!-- Course Footer -->
    @auth
      @if(auth()->user()->user_type === 'student')
        <!-- Student View: Continue Learning Button -->
        <div class="w-full">
          @php
            $enrollment = $course->enrollments->first();
            $isEnrolled = $enrollment !== null;
          @endphp

          @if($isEnrolled)
            <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
               class="min-h-[44px] w-full bg-cyan-500 text-white px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-medium hover:bg-cyan-600 transition-colors flex items-center justify-center">
              <i class="ri-play-line ml-1.5 md:ml-2"></i>
              متابعة التعلم
            </a>
          @else
            <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
               class="min-h-[44px] w-full bg-cyan-500 text-white px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-medium hover:bg-cyan-600 transition-colors flex items-center justify-center">
              <i class="ri-eye-line ml-1.5 md:ml-2"></i>
              عرض التفاصيل
            </a>
          @endif
        </div>
      @else
        <!-- Non-Student View: Price and Details -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
          <div class="flex items-center">
            @if($course->price > 0)
              <span class="text-lg md:text-xl font-bold text-cyan-500">{{ number_format($course->price) }} ر.س</span>
              @if($course->original_price && $course->original_price > $course->price)
                <span class="text-xs md:text-sm text-gray-500 line-through mr-1.5 md:mr-2">{{ number_format($course->original_price) }} ر.س</span>
              @endif
            @else
              <span class="text-lg md:text-xl font-bold text-cyan-500">مجاني</span>
            @endif
          </div>
          <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
               class="min-h-[44px] flex-1 sm:flex-initial bg-cyan-500 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium hover:bg-cyan-600 transition-colors flex items-center justify-center">
              عرض التفاصيل
            </a>
          </div>
        </div>
      @endif
    @else
      <!-- Guest View: Price and Details -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
        <div class="flex items-center">
          @if($course->price > 0)
            <span class="text-lg md:text-xl font-bold text-cyan-500">{{ number_format($course->price) }} ر.س</span>
            @if($course->original_price && $course->original_price > $course->price)
              <span class="text-xs md:text-sm text-gray-500 line-through mr-1.5 md:mr-2">{{ number_format($course->original_price) }} ر.س</span>
            @endif
          @else
            <span class="text-lg md:text-xl font-bold text-cyan-500">مجاني</span>
          @endif
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
          <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
             class="min-h-[44px] flex-1 sm:flex-initial bg-cyan-500 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium hover:bg-cyan-600 transition-colors flex items-center justify-center">
            عرض التفاصيل
          </a>
        </div>
      </div>
    @endauth
  </div>
</div>