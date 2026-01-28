<!-- Recorded Courses Section -->
<section id="courses" class="py-20 bg-gradient-to-b from-gray-50 to-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-16">
      <div class="w-20 h-20 flex items-center justify-center bg-warning-100 rounded-full mx-auto mb-6">
        <svg class="w-10 h-10 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a2.5 2.5 0 110 5H9m4.5-1.206a11.955 11.955 0 01-2.5 2.829 11.955 11.955 0 01-2.5-2.829m0 0V11m0 3.207L6.707 21A2 2 0 014 19.293V7a2 2 0 012.293-1.707L9 7h6l2.293-1.707A2 2 0 0119 7v12.293A2 2 0 0116.707 21L14 18.207"></path>
        </svg>
      </div>
      <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 font-arabic">الكورسات المسجلة</h2>
      <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto font-arabic">
        كورسات جاهزة ومسجلة بجودة عالية، تعلم في أي وقت وبالسرعة التي تناسبك
      </p>
    </div>

    <!-- Recorded Courses Grid -->
    @if($recordedCourses && $recordedCourses->count() > 0)
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @foreach($recordedCourses->take(6) as $course)
          <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <!-- Course Thumbnail -->
            <div class="h-48 relative overflow-hidden">
              @if($course->thumbnail)
                <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" 
                     class="w-full h-full object-cover object-top">
              @else
                <div class="h-full {{ $course->subject_color ?? 'bg-gradient-to-br from-primary-400 to-primary-600' }} flex items-center justify-center">
                  <svg class="w-16 h-16 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a2.5 2.5 0 110 5H9m4.5-1.206a11.955 11.955 0 01-2.5 2.829 11.955 11.955 0 01-2.5-2.829m0 0V11m0 3.207L6.707 21A2 2 0 014 19.293V7a2 2 0 012.293-1.707L9 7h6l2.293-1.707A2 2 0 0119 7v12.293A2 2 0 0116.707 21L14 18.207"></path>
                  </svg>
                </div>
              @endif
              
              <!-- Course Badge -->
              <div class="absolute top-4 right-4 bg-primary-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                {{ $course->grade_level ?? $course->subject->name ?? 'الصف الثالث الثانوي' }}
              </div>
              
              <!-- Play Button Overlay -->
              <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity duration-300">
                <div class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-primary-600 ms-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                </div>
              </div>
            </div>
            
            <!-- Course Content -->
            <div class="p-6">
              <h3 class="text-xl font-bold text-gray-900 mb-2 font-arabic">{{ $course->title }}</h3>
              <p class="text-gray-600 mb-4 font-arabic">{{ Str::limit($course->description, 80) }}</p>
              
              <!-- Course Meta -->
              <div class="flex items-center justify-between mb-4">
                <span class="text-2xl font-bold text-primary-600">{{ $course->price ?? 299 }} {{ getCurrencySymbol() }}</span>
                <div class="flex items-center text-warning-500">
                  <svg class="w-4 h-4 ms-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  <span class="text-gray-700">{{ number_format($course->rating ?? 4.8, 1) }} ({{ $course->reviews_count ?? 124 }})</span>
                </div>
              </div>
              
              <!-- Course Stats -->
              <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                <div class="flex items-center">
                  <svg class="w-4 h-4 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  {{ $course->duration_hours ?? $course->duration ?? '8' }} ساعات
                </div>
                <div class="flex items-center">
                  <svg class="w-4 h-4 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                  {{ $course->total_lessons ?? $course->lessons_count ?? 12 }} درس
                </div>
                @if($course->difficulty_level)
                <div class="flex items-center">
                  <svg class="w-4 h-4 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                  @switch($course->difficulty_level)
                    @case('easy') سهل @break
                    @case('medium') متوسط @break
                    @case('hard') صعب @break
                    @default {{ $course->difficulty_level }}
                  @endswitch
                </div>
                @endif
              </div>
              
              <!-- Action Button -->
              <button class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
                <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m.6 0L6 13m0 0l-1.5 1.5m2.5-1.5L9 13m8 0v6a1 1 0 01-1 1H8a1 1 0 01-1-1v-6m8 0V9a3 3 0 00-6 0v4.01"></path>
                </svg>
                اشتري الآن
              </button>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <!-- Placeholder when no courses available -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @for($i = 1; $i <= 3; $i++)
          <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <div class="h-48 relative overflow-hidden bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
              <svg class="w-16 h-16 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a2.5 2.5 0 110 5H9m4.5-1.206a11.955 11.955 0 01-2.5 2.829 11.955 11.955 0 01-2.5-2.829m0 0V11m0 3.207L6.707 21A2 2 0 014 19.293V7a2 2 0 012.293-1.707L9 7h6l2.293-1.707A2 2 0 0119 7v12.293A2 2 0 0116.707 21L14 18.207"></path>
              </svg>
              <div class="absolute top-4 right-4 bg-primary-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                الصف {{ $i === 1 ? 'الثالث' : ($i === 2 ? 'الثاني' : 'الأول') }} الثانوي
              </div>
            </div>
            
            <div class="p-6">
              <h3 class="text-xl font-bold text-gray-900 mb-2 font-arabic">
                {{ $i === 1 ? 'الرياضيات المتقدمة' : ($i === 2 ? 'الفيزياء التطبيقية' : 'اللغة العربية والأدب') }}
              </h3>
              <p class="text-gray-600 mb-4 font-arabic">
                {{ $i === 1 ? 'شرح شامل لمنهج الرياضيات مع حل التمارين والأمثلة التطبيقية' : ($i === 2 ? 'فهم قوانين الفيزياء من خلال التجارب العملية والأمثلة الواقعية' : 'تعلم قواعد اللغة العربية وتذوق الأدب والشعر العربي الأصيل') }}
              </p>
              
              <div class="flex items-center justify-between mb-4">
                <span class="text-2xl font-bold text-primary-600">{{ $i === 1 ? '299' : ($i === 2 ? '349' : '249') }} {{ getCurrencySymbol() }}</span>
                <div class="flex items-center text-warning-500">
                  <svg class="w-4 h-4 ms-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  <span class="text-gray-700">{{ $i === 1 ? '4.8 (124)' : ($i === 2 ? '4.9 (89)' : '4.7 (156)') }}</span>
                </div>
              </div>
              
              <button class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
                <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m.6 0L6 13m0 0l-1.5 1.5m2.5-1.5L9 13m8 0v6a1 1 0 01-1 1H8a1 1 0 01-1-1v-6m8 0V9a3 3 0 00-6 0v4.01"></path>
                </svg>
                اشتري الآن
              </button>
            </div>
          </div>
        @endfor
      </div>
    @endif

    <!-- View More Button -->
    <div class="text-center mt-8">
      @if(Route::has('courses.index'))
        <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
           class="inline-flex items-center px-8 py-3 bg-white border-2 border-primary-500 text-primary-600 hover:bg-primary-500 hover:text-white font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
          اعرض المزيد من الكورسات المسجلة
        </a>
      @else
        <a href="#courses" 
           class="inline-flex items-center px-8 py-3 bg-white border-2 border-primary-500 text-primary-600 hover:bg-primary-500 hover:text-white font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
          اعرض المزيد من الكورسات المسجلة
        </a>
      @endif
    </div>
  </div>
</section>