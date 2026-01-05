  <!-- Header Section -->
  <div class="mb-6 md:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          {{ __('courses.index.heading') }}
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          {{ __('courses.index.subtitle') }}
        </p>
      </div>
      <div class="bg-white rounded-xl px-4 md:px-6 py-2.5 md:py-3 border border-gray-200 shadow-sm flex-shrink-0">
        <span class="text-xs md:text-sm text-gray-600">{{ __('courses.index.total_courses') }} </span>
        <span class="font-bold text-xl md:text-2xl text-cyan-500">{{ $courses->total() }}</span>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.course-filters
    :route="route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :subjects="$subjects"
    :gradeLevels="$gradeLevels"
    :levels="$levels"
    :showSearch="true"
    :showSubject="true"
    :showGradeLevel="true"
    :showDifficulty="true"
    color="cyan"
  />

  <!-- Results Summary -->
  <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <p class="text-sm md:text-base text-gray-600">
      <span class="font-semibold text-gray-900">{{ $courses->total() }}</span>
      {{ __('courses.index.available_courses') }}
    </p>
    @if($courses->total() > 0)
    <p class="text-xs md:text-sm text-gray-500">
      {{ __('courses.index.showing') }} {{ $courses->firstItem() }} - {{ $courses->lastItem() }} {{ __('courses.index.of') }} {{ $courses->total() }}
    </p>
    @endif
  </div>

  <!-- Courses Grid -->
  @if($courses->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
    @foreach($courses as $course)
    <x-course-card :course="$course" :academy="$academy" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($courses->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4">
      <!-- Page Info -->
      <div class="text-xs md:text-sm text-gray-600 order-2 sm:order-1">
        {{ __('courses.index.page_label') }} <span class="font-semibold text-gray-900">{{ $courses->currentPage() }}</span>
        {{ __('courses.index.of_pages') }} <span class="font-semibold text-gray-900">{{ $courses->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2 order-1 sm:order-2">
        <!-- Previous Button -->
        @if($courses->onFirstPage())
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <i class="ri-arrow-right-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
          <span class="hidden sm:inline me-1">{{ __('courses.index.previous') }}</span>
        </span>
        @else
        <a href="{{ $courses->previousPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-cyan-500 hover:text-cyan-600 transition-colors flex items-center">
          <i class="ri-arrow-right-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
          <span class="hidden sm:inline me-1">{{ __('courses.index.previous') }}</span>
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden md:flex items-center gap-1">
          @php
            $start = max(1, $courses->currentPage() - 2);
            $end = min($courses->lastPage(), $courses->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $courses->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-cyan-500 hover:text-cyan-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $courses->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-cyan-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $courses->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-cyan-500 hover:text-cyan-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $courses->lastPage())
          @if($end < $courses->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $courses->url($courses->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-cyan-500 hover:text-cyan-600 transition-colors">
            {{ $courses->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($courses->hasMorePages())
        <a href="{{ $courses->nextPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-cyan-500 hover:text-cyan-600 transition-colors flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('courses.index.next') }}</span>
          <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
        </a>
        @else
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('courses.index.next') }}</span>
          <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-xs md:text-sm text-gray-500 order-3 hidden sm:block">
        {{ $courses->count() }} {{ __('courses.index.of') }} {{ $courses->total() }} {{ __('courses.index.available_courses') }}
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
    <div class="w-16 h-16 md:w-24 md:h-24 bg-gradient-to-br rtl:bg-gradient-to-bl from-cyan-50 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-inner">
      <i class="ri-play-circle-line text-cyan-400 text-2xl md:text-4xl"></i>
    </div>
    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">{{ __('courses.index.no_courses') }}</h3>
    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 max-w-md mx-auto">{{ __('courses.index.no_courses_message') }}</p>
    <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
       class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 bg-cyan-500 text-white rounded-xl hover:bg-cyan-600 transition-colors shadow-sm font-medium">
      <i class="ri-refresh-line me-2"></i>
      {{ __('courses.index.view_all') }}
    </a>
  </div>
  @endif
