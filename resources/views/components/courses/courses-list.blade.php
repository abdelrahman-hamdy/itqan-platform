@props(['courses', 'academy', 'isStudent' => false])

<!-- Header Section -->
<div class="mb-8">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="ri-play-circle-line text-primary ms-2"></i>
        {{ $isStudent ? __('courses.list.registered_courses') : __('courses.list.recorded_courses') }}
      </h1>
      <p class="text-gray-600">
        {{ __('courses.list.subtitle') }}
      </p>
    </div>
    <div class="flex items-center gap-4">
      <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
        <span class="text-sm text-gray-600">{{ __('courses.list.total_courses') }} </span>
        <span class="font-semibold text-primary">{{ $courses->total() }}</span>
      </div>
    </div>
  </div>
</div>

<!-- Filters Section -->
<div class="mb-8">
          <form method="GET" action="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="filter-card border border-gray-200 rounded-xl p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

      <!-- Search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.list.search') }}</label>
        <div class="relative">
          <input type="text" name="search" value="{{ request('search') }}"
                 placeholder="{{ __('courses.list.search_placeholder') }}"
                 class="w-full ps-10 pe-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <i class="ri-search-line absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        </div>
      </div>

      <!-- Category Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.list.category') }}</label>
        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">{{ __('courses.list.all_categories') }}</option>
          @foreach($categories ?? [] as $category)
            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
              {{ $category }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Level Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.list.level') }}</label>
        <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">{{ __('courses.list.all_levels') }}</option>
          @foreach($levels ?? [] as $level)
            <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
              @switch($level)
                @case('easy') {{ __('courses.list.level_easy') }} @break
                @case('medium') {{ __('courses.list.level_medium') }} @break
                @case('hard') {{ __('courses.list.level_hard') }} @break
                @default {{ $level }}
              @endswitch
            </option>
          @endforeach
        </select>
      </div>

      @if($isStudent)
      <!-- Enrollment Status Filter (Student Only) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.list.enrollment_status') }}</label>
        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>{{ __('courses.list.all_courses') }}</option>
          <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>{{ __('courses.list.enrolled_courses') }}</option>
          <option value="not_enrolled" {{ request('status') == 'not_enrolled' ? 'selected' : '' }}>{{ __('courses.list.not_enrolled_courses') }}</option>
        </select>
      </div>
      @else
      <!-- Price Filter (Public Only) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.list.price') }}</label>
        <select name="price" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">{{ __('courses.list.all_prices') }}</option>
          <option value="free" {{ request('price') == 'free' ? 'selected' : '' }}>{{ __('courses.list.free') }}</option>
          <option value="paid" {{ request('price') == 'paid' ? 'selected' : '' }}>{{ __('courses.list.paid') }}</option>
        </select>
      </div>
      @endif
    </div>

    <!-- Filter Actions -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
          <i class="ri-filter-line ms-2"></i>
          {{ __('courses.list.apply_filters') }}
        </button>
                      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
                 class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
          <i class="ri-refresh-line ms-2"></i>
          {{ __('courses.list.reset') }}
        </a>
      </div>

      @if(request('search') || request('category') || request('level') || request('status') || request('price'))
        <div class="text-sm text-gray-600">
          <i class="ri-information-line ms-1"></i>
          {{ $courses->total() }} {{ trans_choice('courses.list.result|courses.list.results', $courses->total()) }}
        </div>
      @endif
    </div>
  </form>
</div>

<!-- Courses Grid -->
@if($courses->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($courses as $course)
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
        <!-- Course Image -->
        <div class="relative">
          @if($course->thumbnail_url)
            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-48 object-cover">
          @else
            <div class="w-full h-48 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
              <i class="ri-play-circle-line text-white text-4xl"></i>
            </div>
          @endif

          <!-- Course Status Badge -->
          <div class="absolute top-3 right-3">
            @if($course->is_free)
              <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full font-medium">{{ __('courses.list.free') }}</span>
            @else
              <span class="px-2 py-1 bg-primary text-white text-xs rounded-full font-medium">{{ __('courses.list.paid') }}</span>
            @endif
          </div>

          @if($isStudent)
          <!-- Enrollment Status Badge -->
          <div class="absolute top-3 left-3">
            @if($course->enrollments->count() > 0)
              <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full font-medium">{{ __('courses.list.enrolled_badge') }}</span>
            @else
              <span class="px-2 py-1 bg-gray-500 text-white text-xs rounded-full font-medium">{{ __('courses.list.not_enrolled_badge') }}</span>
            @endif
          </div>
          @endif
        </div>

        <!-- Course Content -->
        <div class="p-6">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $course->title }}</h3>
            </div>
            @if($course->avg_rating)
              <div class="flex items-center">
                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                <span class="text-sm text-gray-600 me-1">{{ number_format($course->avg_rating, 1) }}</span>
              </div>
            @endif
          </div>

          <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $course->description }}</p>

          <!-- Course Meta -->
          <div class="space-y-2 mb-4">
            @if($course->subject)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-book-line ms-2"></i>
                <span>{{ $course->subject->name }}</span>
              </div>
            @endif
            @if($course->gradeLevel)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-graduation-cap-line ms-2"></i>
                <span>{{ $course->gradeLevel->name }}</span>
              </div>
            @endif
            @if($course->total_enrollments)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ms-2"></i>
                <span>{{ $course->total_enrollments }} {{ __('courses.list.students_enrolled') }}</span>
              </div>
            @endif
          </div>

          <!-- Course Footer -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              @if($course->price > 0)
                <span class="text-lg font-bold text-primary">{{ number_format($course->price) }} {{ __('courses.list.currency') }}</span>
                @if($course->original_price && $course->original_price > $course->price)
                  <span class="text-sm text-gray-500 line-through me-2">{{ number_format($course->original_price) }} {{ __('courses.list.currency') }}</span>
                @endif
              @else
                <span class="text-lg font-bold text-green-600">{{ __('courses.list.free') }}</span>
              @endif
            </div>
            <div class="flex gap-2">
              <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
                 class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                {{ __('courses.list.view_details') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <!-- Pagination -->
  <div class="flex justify-center mt-8">
    {{ $courses->appends(request()->query())->links() }}
  </div>
@else
  <!-- Empty State -->
  <div class="text-center py-12">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <i class="ri-play-circle-line text-gray-400 text-3xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('courses.list.no_courses') }}</h3>
    <p class="text-gray-600 mb-6">{{ __('courses.list.no_courses_message') }}</p>
              <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
             class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
      <i class="ri-refresh-line ms-2"></i>
      {{ __('courses.list.view_all_courses') }}
    </a>
  </div>
@endif
