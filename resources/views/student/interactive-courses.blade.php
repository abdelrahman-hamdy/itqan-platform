<x-student title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - الكورسات التفاعلية">
  <x-slot name="description">استكشف الكورسات التفاعلية المتاحة - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>
  <x-slot name="head">
    <style>
      .progress-bar {
        background: linear-gradient(90deg, #4169E1 0%, #6495ED 100%);
      }
    </style>
  </x-slot>
      
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-book-open-line text-primary ml-2"></i>
              الكورسات التفاعلية
            </h1>
            <p class="text-gray-600">
              انضم إلى الكورسات التفاعلية المباشرة في مختلف المواد الأكاديمية
            </p>
          </div>
          <div class="flex items-center space-x-4 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
              <span class="text-sm text-gray-600">كورساتي النشطة: </span>
              <span class="font-semibold text-primary">{{ $enrolledCourses->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      @if($enrolledCourses->count() > 0)
      <!-- My Active Courses -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">كورساتي النشطة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($enrolledCourses as $course)
          @php
            $enrollment = $course->enrollments->first();
            $progress = $enrollment->progress_percentage ?? 0;
          @endphp
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="ri-book-open-line text-blue-600 text-xl"></i>
              </div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                نشط
              </span>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $course->title }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $course->description }}</p>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-star-line ml-2"></i>
                <span>{{ $course->assignedTeacher->full_name ?? 'غير محدد' }}</span>
              </div>
              @if($course->subject)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-bookmark-line ml-2"></i>
                <span>{{ $course->subject->name }}</span>
              </div>
              @endif
              @if($course->gradeLevel)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-graduation-cap-line ml-2"></i>
                <span>{{ $course->gradeLevel->name }}</span>
              </div>
              @endif
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>{{ $course->lessons_count ?? 0 }} درس</span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-4">
              <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                <span>التقدم</span>
                <span>{{ $progress }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="progress-bar h-2 rounded-full" style="width: {{ $progress }}%"></div>
              </div>
            </div>

            <div class="mt-6 flex space-x-2 space-x-reverse">
              <a href="{{ route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id]) }}" class="flex-1 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center">
                <i class="ri-play-line ml-1"></i>
                متابعة التعلم
              </a>
              <a href="{{ route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id]) }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ri-information-line"></i>
              </a>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Available Courses -->
      <div>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">الكورسات المتاحة</h2>
          <div class="flex items-center space-x-4 space-x-reverse">
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع المواد</option>
              <option>الرياضيات</option>
              <option>العلوم</option>
              <option>اللغة العربية</option>
              <option>اللغة الإنجليزية</option>
            </select>
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع المستويات</option>
              <option>الصف الأول</option>
              <option>الصف الثاني</option>
              <option>الصف الثالث</option>
            </select>
          </div>
        </div>

        @if($availableCourses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($availableCourses as $course)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="ri-book-open-line text-purple-600 text-xl"></i>
              </div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $course->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $course->is_published ? 'متاح' : 'غير متاح' }}
              </span>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $course->title }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $course->description }}</p>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-star-line ml-2"></i>
                <span>{{ $course->assignedTeacher->full_name ?? 'غير محدد' }}</span>
              </div>
              @if($course->subject)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-bookmark-line ml-2"></i>
                <span>{{ $course->subject->name }}</span>
              </div>
              @endif
              @if($course->gradeLevel)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-graduation-cap-line ml-2"></i>
                <span>{{ $course->gradeLevel->name }}</span>
              </div>
              @endif
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>{{ $course->total_sessions ?? 0 }} جلسة</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>{{ $course->duration_weeks ?? 0 }} أسبوع</span>
              </div>
              @if($course->student_price)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-money-dollar-circle-line ml-2"></i>
                <span>{{ $course->student_price }} ر.س</span>
              </div>
              @endif
            </div>

            <!-- Course Schedule -->
            @if($course->schedule && is_array($course->schedule))
            <div class="mt-4">
              <div class="flex flex-wrap gap-1">
                @foreach($course->schedule as $day => $time)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ $day }}: {{ $time }}
                </span>
                @endforeach
              </div>
            </div>
            @endif

            <div class="mt-6 flex flex-col space-y-2">
              <a href="{{ route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id]) }}"
                 class="block w-full border border-primary text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition-colors text-center">
                <i class="ri-eye-line ml-1"></i>
                عرض التفاصيل
              </a>
            </div>
          </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
          {{ $availableCourses->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
          <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-book-open-line text-gray-400 text-3xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات متاحة حالياً</h3>
          <p class="text-gray-600 mb-6">ستتم إضافة كورسات جديدة قريباً. تابع معنا للحصول على التحديثات</p>
          <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            <i class="ri-arrow-right-line ml-2"></i>
            العودة للملف الشخصي
          </a>
        </div>
        @endif
      </div>

</x-student>