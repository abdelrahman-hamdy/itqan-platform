  <!-- Header Section -->
  <div class="mb-6 md:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          <i class="ri-book-mark-line text-green-600 ms-2"></i>
          {{ __('student.quran_circles.title') }}
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          {{ __('student.quran_circles.description') }}
        </p>
      </div>
      @auth
      <div class="bg-white rounded-lg px-4 md:px-6 py-2 md:py-3 border border-gray-200 shadow-sm flex-shrink-0">
        <span class="text-xs md:text-sm text-gray-600">{{ __('student.quran_circles.my_active_circles') }}: </span>
        <span class="font-bold text-xl md:text-2xl text-green-600">{{ count($enrolledCircleIds) }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
    <form method="GET" action="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="space-y-4">
      <div class="mb-4">
        <h3 class="text-base md:text-lg font-semibold text-gray-900">
          <i class="ri-filter-3-line ms-2"></i>
          {{ __('student.quran_circles.filters_title') }}
        </h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-search-line ms-1"></i>
            {{ __('student.quran_circles.search_label') }}
          </label>
          <input type="text"
                 name="search"
                 value="{{ request('search') }}"
                 placeholder="{{ __('student.quran_circles.search_placeholder') }}"
                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
        </div>

        <!-- Enrollment Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-user-follow-line ms-1"></i>
            {{ __('student.quran_circles.enrollment_status_label') }}
          </label>
          <div class="relative">
            <select name="enrollment_status"
                    style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white">
              <option value="">{{ __('student.quran_circles.status_all') }}</option>
              @auth
              <option value="enrolled" {{ request('enrollment_status') === 'enrolled' ? 'selected' : '' }}>{{ __('student.quran_circles.status_my_circles') }}</option>
              @endauth
              <option value="available" {{ request('enrollment_status') === 'available' ? 'selected' : '' }}>{{ __('student.quran_circles.status_available') }}</option>
              <option value="open" {{ request('enrollment_status') === 'open' ? 'selected' : '' }}>{{ __('student.quran_circles.status_open') }}</option>
              <option value="full" {{ request('enrollment_status') === 'full' ? 'selected' : '' }}>{{ __('student.quran_circles.status_full') }}</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
          </div>
        </div>

        <!-- Memorization Level -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-bar-chart-line ms-1"></i>
            {{ __('student.quran_circles.memorization_level_label') }}
          </label>
          <div class="relative">
            <select name="memorization_level"
                    style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white">
              <option value="">{{ __('student.quran_circles.level_all') }}</option>
              <option value="beginner" {{ request('memorization_level') === 'beginner' ? 'selected' : '' }}>{{ __('student.quran_circles.level_beginner') }}</option>
              <option value="intermediate" {{ request('memorization_level') === 'intermediate' ? 'selected' : '' }}>{{ __('student.quran_circles.level_intermediate') }}</option>
              <option value="advanced" {{ request('memorization_level') === 'advanced' ? 'selected' : '' }}>{{ __('student.quran_circles.level_advanced') }}</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
          </div>
        </div>

        <!-- Schedule Days (Multi-select) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-calendar-line ms-1"></i>
            {{ __('student.quran_circles.schedule_days_label') }}
          </label>
          <div class="relative" x-data="{ open: false, selected: {{ json_encode(request('schedule_days', [])) }} }">
            <button type="button" @click="open = !open"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white text-end">
              <span x-text="selected.length > 0 ? selected.length + ' {{ __('student.quran_circles.days_selected') }}' : '{{ __('student.quran_circles.all_days') }}'" class="text-gray-700"></span>
            </button>
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
            <div x-show="open" @click.away="open = false"
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
              @foreach([__('student.quran_circles.saturday'), __('student.quran_circles.sunday'), __('student.quran_circles.monday'), __('student.quran_circles.tuesday'), __('student.quran_circles.wednesday'), __('student.quran_circles.thursday'), __('student.quran_circles.friday')] as $day)
              <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" name="schedule_days[]" value="{{ $day }}"
                       x-model="selected"
                       {{ in_array($day, request('schedule_days', [])) ? 'checked' : '' }}
                       class="ms-3 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-gray-700">{{ $day }}</span>
              </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <!-- Buttons Row -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
        <button type="submit"
                class="inline-flex items-center justify-center min-h-[44px] bg-green-600 text-white px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
          <i class="ri-search-line ms-1"></i>
          {{ __('student.quran_circles.apply_filters') }}
        </button>

        @if(request()->hasAny(['enrollment_status', 'memorization_level', 'schedule_days', 'search']))
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
           class="inline-flex items-center justify-center min-h-[44px] bg-gray-100 text-gray-700 px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
          <i class="ri-close-circle-line ms-1"></i>
          {{ __('student.quran_circles.reset_filters') }}
        </a>
        @endif
      </div>
    </form>
  </div>

  <!-- Results Summary -->
  <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <p class="text-sm md:text-base text-gray-600">
      <span class="font-semibold text-gray-900">{{ $paginatedCircles->total() }}</span>
      {{ __('student.quran_circles.circles_available') }}
    </p>
    @if($paginatedCircles->total() > 0)
    <p class="text-xs md:text-sm text-gray-500">
      {{ __('student.quran_circles.showing_results') }} {{ $paginatedCircles->firstItem() }} - {{ $paginatedCircles->lastItem() }} {{ __('student.quran_circles.of_total') }} {{ $paginatedCircles->total() }}
    </p>
    @endif
  </div>

  <!-- Circles Grid -->
  @if($paginatedCircles->count() > 0)
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
    @foreach($paginatedCircles as $circle)
      <x-quran-circle-card-list
        :circle="$circle"
        :academy="$academy"
        :enrolledCircleIds="$enrolledCircleIds"
        :isAuthenticated="$isAuthenticated" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($paginatedCircles->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4">
      <!-- Page Info -->
      <div class="text-xs md:text-sm text-gray-600 order-2 sm:order-1">
        {{ __('student.quran_circles.page_label') }} <span class="font-semibold text-gray-900">{{ $paginatedCircles->currentPage() }}</span>
        {{ __('student.quran_circles.of_pages') }} <span class="font-semibold text-gray-900">{{ $paginatedCircles->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2 order-1 sm:order-2">
        <!-- Previous Button -->
        @if($paginatedCircles->onFirstPage())
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <i class="ri-arrow-right-s-line"></i>
          <span class="hidden sm:inline me-1">{{ __('student.quran_circles.previous') }}</span>
        </span>
        @else
        <a href="{{ $paginatedCircles->previousPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors flex items-center">
          <i class="ri-arrow-right-s-line"></i>
          <span class="hidden sm:inline me-1">{{ __('student.quran_circles.previous') }}</span>
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden md:flex items-center gap-1">
          @php
            $start = max(1, $paginatedCircles->currentPage() - 2);
            $end = min($paginatedCircles->lastPage(), $paginatedCircles->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $paginatedCircles->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $paginatedCircles->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-green-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $paginatedCircles->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $paginatedCircles->lastPage())
          @if($end < $paginatedCircles->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $paginatedCircles->url($paginatedCircles->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            {{ $paginatedCircles->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($paginatedCircles->hasMorePages())
        <a href="{{ $paginatedCircles->nextPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('student.quran_circles.next') }}</span>
          <i class="ri-arrow-left-s-line"></i>
        </a>
        @else
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('student.quran_circles.next') }}</span>
          <i class="ri-arrow-left-s-line"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-xs md:text-sm text-gray-500 order-3 hidden sm:block">
        {{ $paginatedCircles->count() }} {{ __('student.quran_circles.circles_of_total') }} {{ $paginatedCircles->total() }} {{ __('student.quran_circles.circles_label') }}
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
    <div class="w-20 h-20 md:w-24 md:h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-inner">
      <i class="ri-book-mark-line text-gray-400 text-3xl md:text-4xl"></i>
    </div>
    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">{{ __('student.quran_circles.no_circles_title') }}</h3>
    <p class="text-sm md:text-base text-gray-600 mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search']))
        {{ __('student.quran_circles.no_results_description') }}
      @else
        {{ __('student.quran_circles.no_circles_description') }}
      @endif
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      @if(request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search']))
      <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[44px] px-6 py-3 bg-green-600 text-white rounded-xl md:rounded-lg hover:bg-green-700 transition-colors shadow-sm font-medium">
        <i class="ri-refresh-line ms-2"></i>
        {{ __('student.quran_circles.reset_filters_button') }}
      </a>
      @endif
      @auth
      <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[44px] px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl md:rounded-lg hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ms-2"></i>
        {{ __('student.quran_circles.back_to_profile') }}
      </a>
      @else
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[44px] px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl md:rounded-lg hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ms-2"></i>
        {{ __('student.quran_circles.back_to_home') }}
      </a>
      @endauth
    </div>
  </div>
  @endif
