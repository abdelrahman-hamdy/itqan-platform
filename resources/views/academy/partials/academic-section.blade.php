<!-- Academic Section -->
<section id="academic" class="py-20 academic-section-gradient">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-16">
      <div class="w-20 h-20 flex items-center justify-center bg-primary-100 rounded-full mx-auto mb-6">
        <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
        </svg>
      </div>
      <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 font-arabic">القسم الأكاديمي</h2>
      <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto font-arabic">
        تعلم المواد الأكاديمية لجميع المراحل الدراسية مع أفضل المعلمين المتخصصين
      </p>
    </div>

    <!-- Interactive Courses Section -->
    @if($interactiveCourses && $interactiveCourses->count() > 0)
      <div class="mb-20">
        <div class="flex justify-between items-center mb-8">
          <h3 class="text-xl sm:text-2xl font-bold text-gray-900 font-arabic">الكورسات التفاعلية المتاحة</h3>
          <div class="flex gap-2">
            <button class="course-prev w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <button class="course-next w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <div class="courses-slider overflow-hidden">
          <div class="courses-track flex gap-6 transition-transform duration-300">
            @foreach($interactiveCourses as $course)
              <div class="bg-white rounded-xl shadow-lg min-w-[300px] card-hover">
                <div class="relative">
                  <div class="h-40 {{ $course->subject_color ?? 'bg-gradient-to-br from-primary-400 to-primary-600' }}">
                    @if($course->thumbnail)
                      <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" 
                           class="w-full h-full object-cover object-top">
                    @else
                      <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-16 h-16 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                      </div>
                    @endif
                  </div>
                  <span class="absolute top-3 right-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $course->is_active ? 'bg-success-100 text-success-800' : 'bg-warning-100 text-warning-800' }}">
                    {{ $course->is_active ? 'متاح' : 'قريباً' }}
                  </span>
                  <span class="absolute top-3 left-3 bg-primary-500 text-white text-sm font-semibold px-3 py-1 rounded-full">
                    {{ $course->subject->name ?? $course->subject_name ?? 'الرياضيات' }}
                  </span>
                </div>
                
                <div class="p-4">
                  <h4 class="text-lg font-bold text-gray-900 mb-2 font-arabic">{{ $course->title }}</h4>
                  <p class="text-sm text-gray-600 mb-4 font-arabic">{{ Str::limit($course->description, 100) }}</p>
                  
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between text-gray-600">
                      <div class="flex items-center">
                        <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        البداية: {{ $course->start_date ? $course->start_date->format('j F Y') : '15 أغسطس 2025' }}
                      </div>
                      <div class="flex items-center">
                        <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $course->duration ?? '8' }} أسابيع
                      </div>
                    </div>
                    <div class="flex items-center justify-between">
                      <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        {{ $course->enrolled_students ?? 15 }}/{{ $course->max_students ?? 20 }} طالب
                      </div>
                      <span class="font-bold text-primary-600">{{ $course->price ?? 299 }} {{ getCurrencySymbol() }}</span>
                    </div>
                  </div>
                  
                  <button class="w-full mt-4 py-2 
                    {{ $course->is_active ? 'bg-primary-500 hover:bg-primary-600 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}
                    font-medium rounded-md transition-colors duration-200 focus:outline-none 
                    {{ $course->is_active ? 'focus:ring-2 focus:ring-primary-500 focus:ring-offset-2' : '' }} font-arabic">
                    {{ $course->is_active ? 'سجل الآن' : 'قريباً' }}
                  </button>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    <!-- Academic Teachers Section -->
    @if($academicTeachers && $academicTeachers->count() > 0)
      <div class="mb-12">
        <div class="flex justify-between items-center mb-8">
          <h3 class="text-xl sm:text-2xl font-bold text-gray-900 font-arabic">المعلمون الأكاديميون المتميزون</h3>
          <div class="flex gap-2">
            <button class="academic-teacher-prev w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <button class="academic-teacher-next w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <div class="academic-teachers-slider overflow-hidden">
          <div class="academic-teachers-track flex gap-6 transition-transform duration-300">
            @foreach($academicTeachers as $teacher)
              <div class="bg-white rounded-xl shadow-lg p-6 min-w-[280px] card-hover">
                <div class="flex items-center gap-4 mb-4">
                  @if($teacher->profile_photo_url)
                    <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->name }}" 
                         class="w-16 h-16 rounded-full object-cover">
                  @else
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-xl font-bold">
                      {{ substr($teacher->name, 0, 1) }}
                    </div>
                  @endif
                  <div>
                    <h4 class="text-lg font-bold text-gray-900 font-arabic">{{ $teacher->name }}</h4>
                    <p class="text-sm text-gray-600 font-arabic">{{ $teacher->qualification ?? 'دكتوراه في الرياضيات' }}</p>
                  </div>
                </div>
                
                <div class="flex items-center gap-2 mb-4">
                  <div class="flex text-warning-500">
                    @for($i = 1; $i <= 5; $i++)
                      <svg class="w-4 h-4 {{ $i <= ($teacher->rating ?? 5) ? 'fill-current' : 'fill-gray-200' }}" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    @endfor
                  </div>
                  <span class="text-sm text-gray-600">{{ number_format($teacher->rating ?? 5, 1) }} ({{ $teacher->reviews_count ?? 180 }} تقييم)</span>
                </div>
                
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 ms-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    {{ $teacher->subject->name ?? $teacher->specialization ?? 'الرياضيات المتقدمة' }}
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 ms-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $teacher->experience_years ?? 12 }} سنة خبرة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 ms-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{ $teacher->students_count ?? 600 }}+ طالب
                  </div>
                </div>
                
                <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
                  احجز حصة
                </button>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    <!-- View More Button -->
    <div class="text-center mt-8">
      <a href="#academic" 
         class="inline-flex items-center px-8 py-3 bg-white border-2 border-primary-500 text-primary-600 hover:bg-primary-500 hover:text-white font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
        اعرض المزيد من الخدمات الأكاديمية
      </a>
    </div>
  </div>
</section> 