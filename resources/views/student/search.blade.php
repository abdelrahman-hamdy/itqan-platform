@php
  $pageTitle = ($academy->name ?? __('student.common.academy_default')) . ' - ' . __('student.search.title');
  $pageDescription = __('student.search.results_for') . ': ' . $query;
@endphp

<x-layouts.student :title="$pageTitle" :description="$pageDescription">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Search Header -->
    <div class="mb-8">
      <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('student.profile', ['subdomain' => $subdomain]) }}"
           class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
          <i class="ri-arrow-right-line text-xl"></i>
        </a>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ __('student.search.title') }}
        </h1>
      </div>

      <!-- Search Query Display -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm">
              <i class="ri-search-line text-blue-600 text-xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">{{ __('student.search.search_for') }}</p>
              <p class="text-xl font-bold text-gray-900">"{{ $query }}"</p>
            </div>
          </div>
          <div class="bg-white rounded-lg px-6 py-3 shadow-sm">
            <span class="text-sm text-gray-600">{{ __('student.search.total_results') }}: </span>
            <span class="font-bold text-2xl text-blue-600">{{ $totalResults }}</span>
          </div>
        </div>
      </div>
    </div>

    @if($totalResults === 0)
      <!-- No Results -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
          <i class="ri-search-line text-gray-400 text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('student.search.no_results_title') }}</h3>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
          {{ __('student.search.no_results_description') }} "<span class="font-semibold">{{ $query }}</span>". {{ __('student.search.no_results_suggestion') }}
        </p>
        <a href="{{ route('student.profile', ['subdomain' => $subdomain]) }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm font-medium">
          <i class="ri-arrow-right-line ms-2"></i>
          {{ __('student.search.back_home') }}
        </a>
      </div>
    @else
      <!-- Results Sections -->

      <!-- Interactive Courses Results -->
      @if($interactiveCourses->count() > 0)
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-video-line text-purple-600"></i>
            {{ __('student.search.interactive_courses') }}
            <span class="text-lg font-normal text-gray-500">({{ $interactiveCourses->count() }})</span>
          </h2>
          <a href="{{ route('interactive-courses.index', ['subdomain' => $subdomain]) }}"
             class="text-purple-600 hover:text-purple-700 text-sm font-medium">
            {{ __('student.search.view_all') }}
            <i class="ri-arrow-left-s-line"></i>
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($interactiveCourses as $course)
            <x-interactive-course-card :course="$course" :academy="$academy" />
          @endforeach
        </div>
      </div>
      @endif

      <!-- Recorded Courses Results -->
      @if($recordedCourses->count() > 0)
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-play-circle-line text-red-600"></i>
            {{ __('student.search.recorded_courses') }}
            <span class="text-lg font-normal text-gray-500">({{ $recordedCourses->count() }})</span>
          </h2>
          <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}"
             class="text-red-600 hover:text-red-700 text-sm font-medium">
            {{ __('student.search.view_all') }}
            <i class="ri-arrow-left-s-line"></i>
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($recordedCourses as $course)
            <x-course-card :course="$course" :academy="$academy" />
          @endforeach
        </div>
      </div>
      @endif

      <!-- Quran Circles Results -->
      @if($quranCircles->count() > 0)
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-group-line text-amber-600"></i>
            {{ __('student.search.quran_circles') }}
            <span class="text-lg font-normal text-gray-500">({{ $quranCircles->count() }})</span>
          </h2>
          <a href="{{ route('quran-circles.index', ['subdomain' => $subdomain]) }}"
             class="text-amber-600 hover:text-amber-700 text-sm font-medium">
            {{ __('student.search.view_all') }}
            <i class="ri-arrow-left-s-line"></i>
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($quranCircles as $circle)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <!-- Circle Header -->
              <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-sm flex-shrink-0">
                  <i class="ri-group-line text-white text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-gray-900 text-lg leading-tight truncate">
                    {{ $circle->name_ar ?? $circle->name_en ?? __('student.search.circle_title_default') }}
                  </h3>
                  @if($circle->circle_code)
                  <p class="text-xs text-gray-500">{{ $circle->circle_code }}</p>
                  @endif
                </div>
              </div>

              <!-- Teacher Info -->
              @if($circle->teacher)
              <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-100">
                <x-avatar
                  :user="$circle->teacher"
                  size="sm"
                  userType="quran_teacher"
                  :gender="$circle->teacher->gender ?? 'male'"
                  class="flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-gray-500">{{ __('student.search.teacher_label') }}</p>
                  <p class="text-sm font-semibold text-gray-900 truncate">
                    {{ $circle->teacher->full_name ?? $circle->teacher->name ?? __('student.search.quran_teacher_default') }}
                  </p>
                </div>
              </div>
              @endif

              <!-- Circle Details -->
              <div class="space-y-2 mb-4">
                @if($circle->specialization)
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-book-open-line text-amber-600 ms-2"></i>
                  <span>{{ $circle->specialization }}</span>
                </div>
                @endif

                @if($circle->max_students)
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-group-2-line text-amber-600 ms-2"></i>
                  <span>{{ $circle->enrolled_students ?? 0 }} / {{ $circle->max_students }} {{ __('student.calendar.student_label') }}</span>
                </div>
                @endif

                @if($circle->monthly_fee)
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-money-dollar-circle-line text-amber-600 ms-2"></i>
                  <span>{{ number_format($circle->monthly_fee) }} {{ __('student.search.per_month') }}</span>
                </div>
                @endif
              </div>

              <!-- Description -->
              @if($circle->description_ar || $circle->description_en)
              <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                {{ $circle->description_ar ?? $circle->description_en }}
              </p>
              @endif

              <!-- View Button -->
              <a href="{{ route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id]) }}"
                 class="inline-block w-full text-center bg-amber-600 text-white px-4 py-2.5 rounded-lg hover:bg-amber-700 transition-colors font-medium">
                <i class="ri-eye-line ms-1"></i>
                {{ __('student.search.view_details') }}
              </a>
            </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Quran Teachers Results -->
      @if($quranTeachers->count() > 0)
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-book-read-line text-yellow-600"></i>
            {{ __('student.search.quran_teachers') }}
            <span class="text-lg font-normal text-gray-500">({{ $quranTeachers->count() }})</span>
          </h2>
          <a href="{{ route('quran-teachers.index', ['subdomain' => $subdomain]) }}"
             class="text-yellow-600 hover:text-yellow-700 text-sm font-medium">
            {{ __('student.search.view_all') }}
            <i class="ri-arrow-left-s-line"></i>
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @foreach($quranTeachers as $teacher)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center gap-4 mb-4">
                <x-avatar
                  :user="$teacher"
                  size="lg"
                  userType="quran_teacher"
                  :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
                  class="flex-shrink-0" />
                <div class="flex-1">
                  <h3 class="font-bold text-gray-900 text-lg mb-1">
                    {{ $teacher->user->full_name ?? $teacher->user->name ?? __('student.search.quran_teacher_default') }}
                  </h3>
                  @if($teacher->teaching_experience_years)
                  <p class="text-sm text-gray-600">
                    <i class="ri-time-line text-yellow-600"></i>
                    {{ $teacher->teaching_experience_years }} {{ __('student.search.years_experience') }}
                  </p>
                  @endif
                </div>
              </div>
              @if($teacher->bio_arabic || $teacher->bio_english)
              <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $teacher->bio_arabic ?? $teacher->bio_english }}</p>
              @endif
              <a href="{{ route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $teacher->id]) }}"
                 class="inline-block w-full text-center bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors font-medium">
                <i class="ri-eye-line ms-1"></i>
                {{ __('student.search.view_profile') }}
              </a>
            </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Academic Teachers Results -->
      @if($academicTeachers->count() > 0)
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ri-graduation-cap-line text-violet-600"></i>
            {{ __('student.search.academic_teachers') }}
            <span class="text-lg font-normal text-gray-500">({{ $academicTeachers->count() }})</span>
          </h2>
          <a href="{{ route('academic-teachers.index', ['subdomain' => $subdomain]) }}"
             class="text-violet-600 hover:text-violet-700 text-sm font-medium">
            {{ __('student.search.view_all') }}
            <i class="ri-arrow-left-s-line"></i>
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @foreach($academicTeachers as $teacher)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center gap-4 mb-4">
                <x-avatar
                  :user="$teacher"
                  size="lg"
                  userType="academic_teacher"
                  :gender="$teacher->gender ?? 'male'"
                  class="flex-shrink-0" />
                <div class="flex-1">
                  <h3 class="font-bold text-gray-900 text-lg mb-1">
                    {{ $teacher->full_name ?? __('student.search.academic_teacher_default') }}
                  </h3>
                  @if($teacher->teaching_experience_years)
                  <p class="text-sm text-gray-600">
                    <i class="ri-time-line text-violet-600"></i>
                    {{ $teacher->teaching_experience_years }} {{ __('student.search.years_experience') }}
                  </p>
                  @endif
                </div>
              </div>
              @if($teacher->bio_arabic || $teacher->bio_english)
              <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $teacher->bio_arabic ?? $teacher->bio_english }}</p>
              @endif
              <a href="{{ route('academic-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $teacher->id]) }}"
                 class="inline-block w-full text-center bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition-colors font-medium">
                <i class="ri-eye-line ms-1"></i>
                {{ __('student.search.view_profile') }}
              </a>
            </div>
          @endforeach
        </div>
      </div>
      @endif

    @endif

  </div>
</x-layouts.student>
