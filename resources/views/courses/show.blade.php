@extends('components.layouts.student')

@section('title', $course->title . ' - ' . $academy->name)

@section('content')
<!-- Enhanced Breadcrumb -->
<nav class="mb-8">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm">
        <li>
            <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
               class="text-gray-500 hover:text-primary transition-colors flex items-center">
                <i class="ri-home-line ml-1"></i>
                الرئيسية
            </a>
        </li>
        <li class="text-gray-400">
            <i class="ri-arrow-left-s-line"></i>
        </li>
        <li>
            <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
               class="text-gray-500 hover:text-primary transition-colors">
                الدورات المسجلة
            </a>
        </li>
        <li class="text-gray-400">
            <i class="ri-arrow-left-s-line"></i>
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
          <span class="text-white text-sm opacity-60">{{ $course->subject?->name ?? 'كورس تعليمي' }}</span>
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
            <span class="text-gray-400 text-sm">({{ $course->reviews_count ?? 0 }} تقييم)</span>
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
          <div class="flex items-center gap-2 text-left">
            <span class="text-sm text-gray-600">السعر:</span>
            @if($course->price && $course->price > 0)
              @if($course->original_price && $course->original_price > $course->price)
              <span class="text-sm text-gray-500 line-through">{{ number_format($course->original_price) }} ريال</span>
              @endif
              <span class="text-2xl font-bold text-cyan-500">{{ number_format($course->price) }} ريال</span>
            @else
              <span class="text-2xl font-bold text-cyan-500">مجاني</span>
            @endif
          </div>
          
          <!-- Action Buttons -->
          <div class="flex items-center gap-4">
            @auth
              @if($isEnrolled)
              <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
                 class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-play-circle-fill text-2xl"></i>
                متابعة الدراسة
              </a>
              @else
              <button onclick="enrollInCourse()"
                      class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-shopping-cart-2-fill text-2xl"></i>
                {{ $course->price && $course->price > 0 ? 'اشتري الآن' : 'سجل مجاناً' }}
              </button>
              @endif
            @else
              <button onclick="enrollInCourse()"
                      class="bg-cyan-500 text-white px-10 py-4 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="ri-shopping-cart-2-fill text-2xl"></i>
                {{ $course->price && $course->price > 0 ? 'اشتري الآن' : 'سجل مجاناً' }}
              </button>
            @endauth
          </div>
        </div>
      </div>
    </div>
    
    <!-- Curriculum -->
    @if($course->lessons && $course->lessons->count() > 0)
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">دروس الدورة</h2>
        
        <div class="space-y-3">
          @foreach($course->lessons->sortBy('id') as $index => $lesson)
            <div class="border border-gray-200 rounded-lg p-4 transition-all duration-200 group {{ $isEnrolled || $lesson->is_free_preview ? 'hover:bg-gray-50 hover:border-primary/30 cursor-pointer' : 'cursor-not-allowed opacity-75' }}" 
                 @if($isEnrolled || $lesson->is_free_preview) onclick="openLesson({{ $lesson->id }}, {{ $course->id }})" @endif>
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
                          {{ gmdate('i:s', $lesson->video_duration_seconds) }} دقيقة
                        </span>
                      @endif
                      
                      @if($lesson->is_free_preview)
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">
                          <i class="ri-eye-line ml-1"></i>
                          معاينة مجانية
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
                
                <!-- Action Button -->
                <div class="flex items-center">
                  @if($isEnrolled || $lesson->is_free_preview)
                    <span class="text-xs px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                      {{ $lesson->is_free_preview ? 'معاينة' : 'متاح' }}
                    </span>
                    <i class="ri-arrow-left-s-line text-gray-400 mr-2 group-hover:text-primary transition-colors"></i>
                  @else
                    <i class="ri-lock-line text-gray-400"></i>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center py-8">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-video-line text-2xl text-gray-400"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد دروس متاحة</h3>
          <p class="text-gray-600">سيتم إضافة الدروس قريباً</p>
        </div>
      </div>
    @endif

    <!-- Reviews -->
    @if($course->reviews && $course->reviews->count() > 0)
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">تقييمات الطلاب</h2>
        
        @foreach($course->reviews->take(3) as $review)
          <div class="flex space-x-3 space-x-reverse mb-4 last:mb-0">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm">
              {{ substr($review->student->name ?? 'طالب', 0, 1) }}
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between mb-1">
                <h4 class="font-medium text-gray-900 text-sm">{{ $review->student->name ?? 'طالب' }}</h4>
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
        <h3 class="font-bold text-gray-900 text-lg">معلومات الدورة</h3>
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
              <div class="text-sm text-gray-600">عدد الدروس</div>
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
              <div class="text-sm text-gray-600">ساعة</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Course Details -->
      <div class="space-y-4">

        @if($course->subject)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-book-line text-primary ml-2"></i>
            <span class="text-sm text-gray-600">المادة</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->subject->name }}</span>
        </div>
        @endif

        @if($course->gradeLevel)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-graduation-cap-line text-blue-500 ml-2"></i>
            <span class="text-sm text-gray-600">الصف الدراسي</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->gradeLevel->name }}</span>
        </div>
        @endif

        @if($course->difficulty_level)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-bar-chart-2-line text-teal-500 ml-2"></i>
            <span class="text-sm text-gray-600">مستوى الصعوبة</span>
          </div>
          <span class="font-medium text-gray-900">
            @switch($course->difficulty_level)
              @case('easy') سهل @break
              @case('medium') متوسط @break
              @case('hard') صعب @break
              @default {{ $course->difficulty_level }}
            @endswitch
          </span>
        </div>
        @endif

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-calendar-line text-orange-500 ml-2"></i>
            <span class="text-sm text-gray-600">تاريخ النشر</span>
          </div>
          <span class="font-medium text-gray-900">{{ $course->published_at?->format('Y/m/d') ?? $course->created_at->format('Y/m/d') }}</span>
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <i class="ri-global-line text-blue-500 ml-2"></i>
            <span class="text-sm text-gray-600">اللغة</span>
          </div>
          <span class="font-medium text-gray-900">العربية</span>
        </div>
      </div>
    </div>

    <!-- Course Features -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
          <i class="ri-star-line text-cyan-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-gray-900 text-lg">مميزات الدورة</h3>
      </div>

      <div class="space-y-3">
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-infinity-line text-cyan-500 ml-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">وصول مدى الحياة</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-award-line text-cyan-500 ml-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">شهادة إتمام</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-device-line text-cyan-500 ml-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">متاح على جميع الأجهزة</span>
        </div>
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-video-line text-cyan-500 ml-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">دروس مسجلة عالية الجودة</span>
        </div>
        @if($course->has_assignments)
        <div class="flex items-center p-3 bg-cyan-50 rounded-lg border border-cyan-100">
          <i class="ri-file-list-3-line text-cyan-500 ml-2 text-lg"></i>
          <span class="text-sm text-gray-700 font-medium">واجبات وتطبيقات عملية</span>
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
        <h3 class="font-bold text-gray-900 text-lg">حالة التسجيل</h3>
      </div>
      
      @if($isEnrolled)
      <div class="space-y-4">
        <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-200">
          <i class="ri-check-circle-line text-green-500 ml-3 text-2xl"></i>
          <div class="mr-3">
            <div class="font-semibold text-green-700">مسجل في الدورة ✓</div>
            <div class="text-sm text-green-600">يمكنك الوصول لجميع الدروس والمحتوى</div>
          </div>
        </div>
        
        <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
           class="w-full bg-cyan-500 text-white py-3 px-6 rounded-lg font-semibold text-center hover:bg-cyan-600 transition-all duration-300 flex items-center justify-center gap-2">
          <i class="ri-play-circle-fill text-xl"></i>
          متابعة الدراسة
        </a>
      </div>
      @else
      <div class="space-y-4">
        <div class="text-center p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg border border-orange-200">
          <i class="ri-lock-line text-orange-500 text-3xl mb-2"></i>
          <div class="font-semibold text-orange-700 mb-1">غير مسجل في الدورة</div>
          <div class="text-sm text-orange-600">سجل الآن للوصول لجميع الدروس</div>
        </div>
        
        <div class="text-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
          @if($course->price && $course->price > 0)
            @if($course->original_price && $course->original_price > $course->price)
            <div class="text-sm text-gray-500 line-through mb-1">{{ number_format($course->original_price) }} ريال</div>
            @endif
            <div class="text-2xl font-bold text-cyan-500 mb-1">{{ number_format($course->price) }} ريال</div>
            <div class="text-xs text-gray-600">سعر الدورة</div>
          @else
            <div class="text-2xl font-bold text-cyan-500 mb-1">مجاني</div>
            <div class="text-xs text-gray-600">دورة مجانية بالكامل</div>
          @endif
        </div>

                                <button onclick="enrollInCourse()"
                                class="w-full bg-cyan-500 text-white py-4 px-6 rounded-lg font-bold text-lg hover:bg-cyan-600 transition-all duration-300 flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                          <i class="ri-shopping-cart-2-fill text-xl"></i>
                          {{ $course->price && $course->price > 0 ? 'اشتري الآن' : 'سجل مجاناً' }}
                        </button>
        
        
      </div>
      @endif
    </div>

  </div>
</div>

<!-- Related Courses - Full Width Section -->
@if($relatedCourses && $relatedCourses->count() > 0)
<div class="mt-12">
  <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">دورات ذات صلة</h2>
    <p class="text-gray-600">اكتشف المزيد من الدورات التي قد تهمك</p>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($relatedCourses->take(3) as $relatedCourse)
      <x-course-card :course="$relatedCourse" :academy="$academy" />
    @endforeach
  </div>
</div>
@endif

<script>
function openLesson(lessonId, courseId) {
  // Navigate to individual lesson page
  window.location.href = `/courses/${courseId}/lessons/${lessonId}`;
}

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

function enrollInCourse() {
  // Check if user is authenticated
  @guest
    window.location.href = "{{ route('login', ['subdomain' => $academy->subdomain]) }}";
    return;
  @endguest

  const button = event.target;
  const originalHTML = button.innerHTML;
  button.innerHTML = '<i class="ri-loader-4-line animate-spin ml-2"></i>جاري التسجيل...';
  button.disabled = true;

  fetch(`/api/courses/{{ $course->id }}/enroll`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
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
      alert(data.message);
      window.location.reload();
    } else if (data && data.message) {
      alert(data.message);
      button.innerHTML = originalHTML;
      button.disabled = false;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('حدث خطأ أثناء التسجيل');
    button.innerHTML = originalHTML;
    button.disabled = false;
  });
}
</script>
@endsection