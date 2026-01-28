<!-- Enhanced Breadcrumb -->
<nav class="mb-8">
    <ol class="flex items-center gap-2 text-sm">
        <li>
            <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}"
               class="text-gray-500 hover:text-primary transition-colors flex items-center">
                <i class="ri-home-line ms-1"></i>
                {{ __('courses.show.breadcrumb.home') }}
            </a>
        </li>
        <li class="text-gray-400">
            <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line"></i>
        </li>
        <li>
            <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
               class="text-gray-500 hover:text-primary transition-colors">
                {{ __('courses.show.breadcrumb.courses') }}
            </a>
        </li>
        <li class="text-gray-400">
            <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line"></i>
        </li>
        <li class="text-cyan-500 font-medium">{{ $course->title }}</li>
    </ol>
</nav>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  
  <!-- Main Content -->
  <div class="lg:col-span-2 space-y-6">
    
    <!-- Course Hero - Moved to Main Column -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <!-- Featured Image/Video -->
      @if($course->getFirstMediaUrl('thumbnails'))
      <div class="aspect-video bg-gray-200 relative">
        <img src="{{ $course->getFirstMediaUrl('thumbnails') }}" 
             alt="{{ $course->title }}" 
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
          <div class="w-20 h-20 bg-white bg-opacity-90 rounded-full flex items-center justify-center cursor-pointer hover:bg-opacity-100 transition-all duration-200">
            <i class="ri-play-fill text-3xl text-primary"></i>
          </div>
        </div>
      </div>
      @elseif($course->thumbnail_url)
      <div class="aspect-video bg-gray-200 relative">
        <img src="{{ $course->thumbnail_url }}" 
             alt="{{ $course->title }}" 
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
          <div class="w-20 h-20 bg-white bg-opacity-90 rounded-full flex items-center justify-center cursor-pointer hover:bg-opacity-100 transition-all duration-200">
            <i class="ri-play-fill text-3xl text-primary"></i>
          </div>
        </div>
      </div>
      @else
      <div class="aspect-video bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
        <div class="text-center">
          <i class="ri-play-circle-line text-6xl text-white opacity-70 mb-2"></i>
          <span class="text-white text-sm opacity-60">{{ $course->subject?->name ?? __('courses.show.educational_course') }}</span>
        </div>
      </div>
      @endif
      
      <!-- Course Info -->
      <div class="p-6">
        <!-- Course Title with Rating -->
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-4xl font-bold text-gray-900 leading-tight">{{ $course->title }}</h1>
          @if($course->average_rating && $course->average_rating > 0)
          <div class="flex items-center gap-2">
            <div class="flex text-yellow-400">
              @for($i = 1; $i <= 5; $i++)
                <i class="ri-star-{{ $i <= floor($course->average_rating) ? 'fill' : 'line' }} text-lg"></i>
              @endfor
            </div>
            <span class="text-gray-600 font-medium">{{ number_format($course->average_rating, 1) }}</span>
            <span class="text-gray-400 text-sm">({{ $course->reviews_count ?? 0 }} {{ __('courses.show.reviews') }})</span>
          </div>
          @endif
        </div>
        
        <!-- Course Description -->
        @if($course->description)
        <p class="text-gray-600 text-lg leading-relaxed mb-8">
          {{ $course->description }}
        </p>
        @endif
        
        <!-- Action Button and Price -->
        <div class="flex items-center justify-between">
          <!-- Price floated to the left -->
          <div class="flex items-center gap-2 text-end">
            <span class="text-sm text-gray-600">{{ __('courses.show.price') }}</span>
            @if($course->price && $course->price > 0)
              @if($course->original_price && $course->original_price > $course->price)
              <span class="text-sm text-gray-500 line-through">{{ number_format($course->original_price) }} {{ getCurrencySymbol() }}</span>
              @endif
              <span class="text-2xl font-bold text-cyan-500">{{ number_format($course->price) }} {{ getCurrencySymbol() }}</span>
            @else
              <span class="text-2xl font-bold text-cyan-500">{{ __('courses.show.free') }}</span>
            @endif
          </div>
          
          <!-- Action Buttons -->
          <div class="flex items-center gap-4">
            @auth
              @if($isEnrolled)
              <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
                 class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-play-circle-fill text-2xl"></i>
                {{ __('courses.show.continue_studying') }}
              </a>
              @elseif(auth()->user()->isStudent())
              {{-- Only students can enroll --}}
              <button x-data @click="enrollInCourse($event)"
                      class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-shopping-cart-2-fill text-2xl"></i>
                {{ $course->price && $course->price > 0 ? __('courses.show.buy_now') : __('courses.show.register_free') }}
              </button>
              @else
              {{-- Non-student users cannot enroll - show disabled state --}}
              <span class="bg-gray-300 text-gray-500 px-10 py-4 rounded-lg font-bold text-lg cursor-not-allowed flex items-center gap-3">
                <i class="ri-lock-line text-2xl"></i>
                {{ __('courses.show.students_only') }}
              </span>
              @endif
            @else
              <button x-data @click="enrollInCourse($event)"
                      class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-shopping-cart-2-fill text-2xl"></i>
                {{ $course->price && $course->price > 0 ? __('courses.show.buy_now') : __('courses.show.register_free') }}
              </button>
            @endauth
          </div>
        </div>
      </div>
    </div>
    
    <!-- Curriculum -->
    @if($course->lessons && $course->lessons->count() > 0)
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.show.lessons_title') }}</h2>
        
        <div class="space-y-3">
          @foreach($course->lessons->sortBy('id') as $index => $lesson)
            @if($isEnrolled || $lesson->is_free_preview)
              <a href="{{ url('/courses/' . $course->id . '/lessons/' . $lesson->id) }}"
                 class="block border border-gray-200 rounded-lg p-4 transition-all duration-200 group hover:bg-gray-50 hover:border-primary/30 cursor-pointer no-underline">
            @else
              <div class="border border-gray-200 rounded-lg p-4 transition-all duration-200 group cursor-not-allowed opacity-75">
            @endif
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <!-- Lesson Number -->
                  <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <span class="text-sm font-bold text-primary">{{ $index + 1 }}</span>
                  </div>

                  <!-- Play/Lock Icon -->
                  <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                    @if($isEnrolled || $lesson->is_free_preview)
                      <i class="ri-play-circle-line text-primary"></i>
                    @else
                      <i class="ri-lock-line text-gray-400"></i>
                    @endif
                  </div>

                  <!-- Lesson Info -->
                  <div class="flex-1">
                    <h4 class="font-medium text-gray-900 mb-1 group-hover:text-primary transition-colors">{{ $lesson->title }}</h4>
                    @if($lesson->description)
                      <p class="text-sm text-gray-600">{{ Str::limit(html_entity_decode(strip_tags($lesson->description)), 120) }}</p>
                    @endif

                    <!-- Lesson Meta -->
                    <div class="flex items-center gap-4 mt-2">
                      @if($lesson->video_duration_seconds)
                        <span class="text-xs text-gray-500 flex items-center gap-1">
                          <i class="ri-time-line"></i>
                          {{ gmdate('i:s', $lesson->video_duration_seconds) }} {{ __('courses.show.minute') }}
                        </span>
                      @endif

                      @if($lesson->is_free_preview)
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">
                          <i class="ri-eye-line ms-1"></i>
                          {{ __('courses.show.free_preview') }}
                        </span>
                      @endif
                    </div>
                  </div>
                </div>

                <!-- Action Button -->
                <div class="flex items-center">
                  @if($isEnrolled || $lesson->is_free_preview)
                    <span class="text-xs px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                      {{ $lesson->is_free_preview ? __('courses.show.preview') : __('courses.show.available') }}
                    </span>
                    <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-gray-400 me-2 group-hover:text-primary transition-colors"></i>
                  @else
                    <i class="ri-lock-line text-gray-400"></i>
                  @endif
                </div>
              </div>
            @if($isEnrolled || $lesson->is_free_preview)
              </a>
            @else
              </div>
            @endif
          @endforeach
        </div>
      </div>
    @else
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center py-8">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-video-line text-2xl text-gray-400"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('courses.show.no_lessons') }}</h3>
          <p class="text-gray-600">{{ __('courses.show.lessons_coming_soon') }}</p>
        </div>
      </div>
    @endif

    <!-- Reviews -->
    @if($course->reviews && $course->reviews->count() > 0)
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.show.student_reviews') }}</h2>

        @foreach($course->reviews->take(3) as $review)
          <div class="flex gap-3 mb-4 last:mb-0">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm">
              {{ substr($review->student->name ?? __('courses.show.student'), 0, 1) }}
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between mb-1">
                <h4 class="font-medium text-gray-900 text-sm">{{ $review->student->name ?? __('courses.show.student') }}</h4>
                <div class="flex">
                  @for($i = 1; $i <= 5; $i++)
                    <i class="ri-star-{{ $i <= $review->rating ? 'fill' : 'line' }} text-yellow-400 text-sm"></i>
                  @endfor
                </div>
              </div>
              @if($review->comment)
                <p class="text-gray-700 text-sm">{{ $review->comment }}</p>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <!-- Sidebar -->
  <div class="space-y-6">
    
    <!-- Course Stats & Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
          <i class="ri-information-line text-cyan-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-gray-900 text-lg">{{ __('courses.show.course_info') }}</h3>
      </div>

      <!-- Key Stats -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="ri-play-list-2-line text-blue-600 text-2xl"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-blue-600">{{ $course->total_lessons ?? 0 }}</div>
              <div class="text-sm text-gray-600">{{ __('courses.show.lessons_count') }}</div>
            </div>
          </div>
        </div>
        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="ri-time-line text-green-600 text-2xl"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-green-600">{{ $course->duration_hours ?? 0 }}</div>
              <div class="text-sm text-gray-600">{{ __('courses.show.hours') }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Course Details -->
      <div class="space-y-4">

        @if($course->subject)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-book-line text-primary ms-2"></i>
            <span class="text-sm text-gray-600">{{ __('courses.show.subject') }}</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->subject->name }}</span>
        </div>
        @endif

        @if($course->gradeLevel)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-graduation-cap-line text-blue-500 ms-2"></i>
            <span class="text-sm text-gray-600">{{ __('courses.show.grade_level') }}</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->gradeLevel->getDisplayName() }}</span>
        </div>
        @endif

        @if($course->difficulty_level)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-bar-chart-2-line text-teal-500 ms-2"></i>
            <span class="text-sm text-gray-600">{{ __('courses.show.difficulty_level') }}</span>
          </div>
          <span class="font-medium text-gray-900">
            @switch($course->difficulty_level)
              @case('easy') {{ __('courses.show.difficulty.easy') }} @break
              @case('medium') {{ __('courses.show.difficulty.medium') }} @break
              @case('hard') {{ __('courses.show.difficulty.hard') }} @break
              @default {{ $course->difficulty_level }}
            @endswitch
          </span>
        </div>
        @endif

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-calendar-line text-orange-500 ms-2"></i>
            <span class="text-sm text-gray-600">{{ __('courses.show.publish_date') }}</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->published_at?->format('Y/m/d') ?? $course->created_at->format('Y/m/d') }}</span>
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-global-line text-blue-500 ms-2"></i>
            <span class="text-sm text-gray-600">{{ __('courses.show.language') }}</span>
          </div>
          <span class="font-medium text-gray-900">{{ __('courses.show.arabic') }}</span>
        </div>
      </div>
    </div>

    <!-- Course Features -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
          <i class="ri-star-line text-cyan-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-gray-900 text-lg">{{ __('courses.show.course_features') }}</h3>
      </div>

      <div class="space-y-3">
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-infinity-line text-cyan-500 ms-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">{{ __('courses.show.lifetime_access') }}</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-award-line text-cyan-500 ms-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">{{ __('courses.show.completion_certificate') }}</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-device-line text-cyan-500 ms-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">{{ __('courses.show.all_devices') }}</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-video-line text-cyan-500 ms-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">{{ __('courses.show.high_quality_videos') }}</span>
        </div>
        @if($course->has_assignments)
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-file-list-3-line text-cyan-500 ms-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">{{ __('courses.show.assignments') }}</span>
        </div>
        @endif
      </div>
    </div>

    <!-- Enrollment Status -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
          <i class="ri-user-settings-line text-cyan-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-gray-900 text-lg">{{ __('courses.show.enrollment_status') }}</h3>
      </div>

      @if($isEnrolled)
      <div class="space-y-4">
        <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-200">
          <i class="ri-check-circle-line text-green-500 ms-3 text-2xl"></i>
          <div class="me-3">
            <div class="font-semibold text-green-700">{{ __('courses.show.enrolled_checkmark') }}</div>
            <div class="text-sm text-green-600">{{ __('courses.show.enrolled_message') }}</div>
          </div>
        </div>

        <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
           class="w-full bg-cyan-500 text-white py-3 px-6 rounded-lg font-semibold text-center hover:bg-cyan-600 transition-all duration-300 flex items-center justify-center gap-2">
          <i class="ri-play-circle-fill text-xl"></i>
          {{ __('courses.show.continue_studying') }}
        </a>
      </div>
      @else
      <div class="space-y-4">
        <div class="text-center p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg border border-orange-200">
          <i class="ri-lock-line text-orange-500 text-3xl mb-2"></i>
          <div class="font-semibold text-orange-700 mb-1">{{ __('courses.show.not_enrolled') }}</div>
          <div class="text-sm text-orange-600">{{ __('courses.show.not_enrolled_message') }}</div>
        </div>

        <div class="text-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
          @if($course->price && $course->price > 0)
            @if($course->original_price && $course->original_price > $course->price)
            <div class="text-sm text-gray-500 line-through mb-1">{{ number_format($course->original_price) }} {{ getCurrencySymbol() }}</div>
            @endif
            <div class="text-2xl font-bold text-cyan-500 mb-1">{{ number_format($course->price) }} {{ getCurrencySymbol() }}</div>
            <div class="text-xs text-gray-600">{{ __('courses.show.course_price') }}</div>
          @else
            <div class="text-2xl font-bold text-cyan-500 mb-1">{{ __('courses.show.free') }}</div>
            <div class="text-xs text-gray-600">{{ __('courses.show.free_course') }}</div>
          @endif
        </div>

        @auth
          @if(auth()->user()->isStudent())
            {{-- Only students can enroll --}}
            <button x-data @click="enrollInCourse($event)"
                    class="w-full bg-cyan-500 text-white py-4 px-6 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
              <i class="ri-shopping-cart-2-fill text-xl"></i>
              {{ $course->price && $course->price > 0 ? __('courses.show.buy_now') : __('courses.show.register_free') }}
            </button>
          @else
            {{-- Non-student users cannot enroll --}}
            <div class="w-full bg-gray-200 text-gray-500 py-4 px-6 rounded-lg font-bold text-center cursor-not-allowed flex items-center justify-center gap-3">
              <i class="ri-lock-line text-xl"></i>
              {{ __('courses.show.students_only') }}
            </div>
          @endif
        @else
          {{-- Guest users - redirect to login --}}
          <button x-data @click="enrollInCourse($event)"
                  class="w-full bg-cyan-500 text-white py-4 px-6 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            <i class="ri-shopping-cart-2-fill text-xl"></i>
            {{ $course->price && $course->price > 0 ? __('courses.show.buy_now') : __('courses.show.register_free') }}
          </button>
        @endauth
        
        
      </div>
      @endif
    </div>

  </div>
</div>

<!-- Related Courses - Full Width Section -->
@if($relatedCourses && $relatedCourses->count() > 0)
<div class="mt-12">
  <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ __('courses.show.related_courses') }}</h2>
    <p class="text-gray-600">{{ __('courses.show.discover_more') }}</p>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($relatedCourses->take(3) as $relatedCourse)
      <x-course-card :course="$relatedCourse" :academy="$academy" />
    @endforeach
  </div>
</div>
@endif

<script>
function toggleSection(sectionId) {
  const content = document.getElementById(`section-${sectionId}`);
  const arrow = document.querySelector(`.section-arrow-${sectionId}`);
  
  if (content.classList.contains('hidden')) {
    content.classList.remove('hidden');
    arrow.style.transform = 'rotate(180deg)';
  } else {
    content.classList.add('hidden');
    arrow.style.transform = 'rotate(0deg)';
  }
}

function enrollInCourse(event) {
  // Check if user is authenticated
  @guest
    window.location.href = "{{ route('login', ['subdomain' => $academy->subdomain]) }}";
    return;
  @endguest

  // Show confirmation modal before enrolling
  if (typeof showConfirmModal === 'function') {
    showConfirmModal({
      title: '{{ __("courses.show.confirm_enrollment_title") }}',
      message: '{{ __("courses.show.confirm_enrollment_message") }}',
      type: 'success',
      confirmText: '{{ __("courses.show.yes_enroll") }}',
      cancelText: '{{ __("common.actions.cancel") }}',
      onConfirm: () => performEnrollment(event)
    });
  } else {
    // Fallback if modal not available
    performEnrollment(event);
  }
}

function performEnrollment(event) {
  const button = event.target.closest('button');
  const originalHTML = button.innerHTML;
  button.innerHTML = '<i class="ri-loader-4-line animate-spin ms-2"></i>{{ __('courses.show.enrolling') }}';
  button.disabled = true;

  fetch(`{{ route('courses.enroll.api', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    if (data && data.success) {
      if (window.toast) {
        window.toast.show({ type: 'success', message: data.message || '{{ __("courses.show.enrollment_success") }}' });
      }
      setTimeout(() => {
        window.location.reload();
      }, 800);
    } else if (data && data.message) {
      if (window.toast) {
        window.toast.show({ type: 'error', message: data.message });
      }
      button.innerHTML = originalHTML;
      button.disabled = false;
    } else {
      if (window.toast) {
        window.toast.show({ type: 'error', message: '{{ __("courses.show.enrollment_error") }}' });
      }
      button.innerHTML = originalHTML;
      button.disabled = false;
    }
  })
  .catch(error => {
    console.error('Enrollment error:', error);
    if (window.toast) {
      window.toast.show({ type: 'error', message: '{{ __("courses.show.connection_error") }}' });
    }
    button.innerHTML = originalHTML;
    button.disabled = false;
  });
}
</script>
