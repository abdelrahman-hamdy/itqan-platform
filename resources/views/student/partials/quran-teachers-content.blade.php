  <!-- Header Section -->
  <div class="mb-6 md:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          {{ __('student.quran_teachers.title') }}
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          {{ __('student.quran_teachers.description') }}
        </p>
      </div>
      @auth
      <div class="bg-white rounded-xl px-4 md:px-6 py-2.5 md:py-3 border border-gray-200 shadow-sm flex-shrink-0">
        <span class="text-xs md:text-sm text-gray-600">{{ __('student.quran_teachers.my_teachers_count') }} </span>
        <span class="font-bold text-xl md:text-2xl text-yellow-600">{{ $activeSubscriptionsCount }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.quran-filters
    :route="route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :showSearch="true"
    :showExperience="true"
    :showGender="true"
    :showDays="true"
    color="yellow"
  />

  <!-- Results Summary -->
  <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <p class="text-sm md:text-base text-gray-600">
      <span class="font-semibold text-gray-900">{{ $quranTeachers->total() }}</span>
      {{ __('student.quran_teachers.available_teachers') }}
    </p>
    @if($quranTeachers->total() > 0)
    <p class="text-xs md:text-sm text-gray-500">
      {{ __('student.quran_teachers.showing_results') }} {{ $quranTeachers->firstItem() }} - {{ $quranTeachers->lastItem() }} {{ __('student.quran_teachers.of_total') }} {{ $quranTeachers->total() }}
    </p>
    @endif
  </div>

  <!-- Teachers Grid -->
  @if($quranTeachers->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
    @foreach($quranTeachers as $teacher)
      <x-quran-teacher-card-list
        :teacher="$teacher"
        :academy="$academy"
        :availablePackages="$availablePackages ?? collect()" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($quranTeachers->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4">
      <!-- Page Info -->
      <div class="text-xs md:text-sm text-gray-600 order-2 sm:order-1">
        {{ __('student.quran_teachers.page_label') }} <span class="font-semibold text-gray-900">{{ $quranTeachers->currentPage() }}</span>
        {{ __('student.quran_teachers.of_pages') }} <span class="font-semibold text-gray-900">{{ $quranTeachers->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2 order-1 sm:order-2">
        <!-- Previous Button -->
        @if($quranTeachers->onFirstPage())
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <i class="ri-arrow-right-s-line rtl:rotate-0 ltr:rotate-180"></i>
          <span class="hidden sm:inline me-1">{{ __('student.quran_teachers.previous') }}</span>
        </span>
        @else
        <a href="{{ $quranTeachers->previousPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors flex items-center">
          <i class="ri-arrow-right-s-line rtl:rotate-0 ltr:rotate-180"></i>
          <span class="hidden sm:inline me-1">{{ __('student.quran_teachers.previous') }}</span>
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden md:flex items-center gap-1">
          @php
            $start = max(1, $quranTeachers->currentPage() - 2);
            $end = min($quranTeachers->lastPage(), $quranTeachers->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $quranTeachers->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $quranTeachers->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-yellow-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $quranTeachers->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $quranTeachers->lastPage())
          @if($end < $quranTeachers->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $quranTeachers->url($quranTeachers->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $quranTeachers->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($quranTeachers->hasMorePages())
        <a href="{{ $quranTeachers->nextPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('student.quran_teachers.next') }}</span>
          <i class="ri-arrow-left-s-line rtl:rotate-0 ltr:rotate-180"></i>
        </a>
        @else
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <span class="hidden sm:inline ms-1">{{ __('student.quran_teachers.next') }}</span>
          <i class="ri-arrow-left-s-line rtl:rotate-0 ltr:rotate-180"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-xs md:text-sm text-gray-500 order-3 hidden sm:block">
        {{ $quranTeachers->count() }} {{ __('student.quran_teachers.teachers_of_total') }} {{ $quranTeachers->total() }} {{ __('student.quran_teachers.teachers_label') }}
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
    <div class="w-16 h-16 md:w-24 md:h-24 bg-gradient-to-br rtl:bg-gradient-to-bl from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-inner">
      <i class="ri-user-star-line text-gray-400 text-2xl md:text-4xl"></i>
    </div>
    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">{{ __('student.quran_teachers.no_teachers_title') }}</h3>
    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
        {{ __('student.quran_teachers.no_results_description') }}
      @else
        {{ __('student.quran_teachers.no_teachers_description') }}
      @endif
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
      <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 bg-yellow-600 text-white rounded-xl hover:bg-yellow-700 transition-colors shadow-sm font-medium">
        <i class="ri-refresh-line me-2"></i>
        {{ __('student.quran_teachers.reset_filters') }}
      </a>
      @endif
      @auth
      <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-left-line rtl:rotate-180 me-2"></i>
        {{ __('student.quran_teachers.back_to_profile') }}
      </a>
      @else
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-left-line rtl:rotate-180 me-2"></i>
        {{ __('student.quran_teachers.back_to_home') }}
      </a>
      @endauth
    </div>
  </div>
  @endif
