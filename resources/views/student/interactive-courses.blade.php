@php
  $academy = auth()->user()->academy ?? null;
@endphp

<x-student title="{{ $academy->name ?? 'أكاديمية إتقان' }} - الكورسات التفاعلية">
  <x-slot name="description">استكشف الكورسات التفاعلية المتاحة - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          الكورسات التفاعلية
        </h1>
        <p class="text-gray-600">
          انضم إلى الكورسات التفاعلية المباشرة في مختلف المواد الأكاديمية
        </p>
      </div>
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">كورساتي النشطة: </span>
        <span class="font-bold text-2xl text-blue-500">{{ $enrolledCoursesCount }}</span>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.course-filters
    :route="route('student.interactive-courses', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :subjects="$subjects"
    :gradeLevels="$gradeLevels"
    :levels="[]"
    :showSearch="true"
    :showSubject="true"
    :showGradeLevel="true"
    :showDifficulty="false"
    color="blue"
  />

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $courses->total() }}</span>
      كورس متاح
    </p>
    @if($courses->total() > 0)
    <p class="text-sm text-gray-500">
      عرض {{ $courses->firstItem() }} - {{ $courses->lastItem() }} من {{ $courses->total() }}
    </p>
    @endif
  </div>

  <!-- Courses Grid -->
  @if($courses->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($courses as $course)
    @php
      $enrollment = $course->enrollments->first();
      $isEnrolled = $enrollment !== null;
      $progress = $enrollment->progress_percentage ?? 0;
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-all duration-300 flex flex-col">
      <div class="flex items-start justify-between mb-4">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
          {{ $isEnrolled ? 'bg-green-100 text-green-800' : ($course->is_published ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
          {{ $isEnrolled ? 'نشط' : ($course->is_published ? 'متاح' : 'غير متاح') }}
        </span>
      </div>

      <h3 class="font-bold text-xl text-gray-900 mb-2 line-clamp-2">{{ $course->title }}</h3>
      <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $course->description }}</p>

      <div class="grid grid-cols-1 gap-3 mb-4">
        <div class="flex items-center gap-3 bg-blue-50 rounded-lg p-3">
          <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
            <i class="ri-user-star-line text-blue-600 text-lg"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 mb-0.5">المعلم</p>
            <p class="text-sm font-semibold text-gray-900 truncate">{{ $course->assignedTeacher->full_name ?? 'غير محدد' }}</p>
          </div>
        </div>

        @if($course->subject || $course->gradeLevel)
        <div class="grid grid-cols-2 gap-3">
          @if($course->subject)
          <div class="flex items-center gap-2 bg-blue-50 rounded-lg p-3">
            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
              <i class="ri-bookmark-line text-blue-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 mb-0.5">المادة</p>
              <p class="text-xs font-semibold text-gray-900 truncate">{{ $course->subject->name }}</p>
            </div>
          </div>
          @endif

          @if($course->gradeLevel)
          <div class="flex items-center gap-2 bg-blue-50 rounded-lg p-3">
            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
              <i class="ri-graduation-cap-line text-blue-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 mb-0.5">الصف</p>
              <p class="text-xs font-semibold text-gray-900 truncate">{{ $course->gradeLevel->name }}</p>
            </div>
          </div>
          @endif
        </div>
        @endif

        <div class="bg-blue-50 rounded-lg p-3">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
              <i class="ri-calendar-line text-blue-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 mb-0.5">المدة</p>
              <p class="text-sm font-semibold text-gray-900">{{ $course->total_sessions ?? 0 }} جلسة <span class="text-gray-400">•</span> {{ $course->duration_weeks ?? 0 }} أسبوع</p>
            </div>
          </div>
          @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
          <div class="flex flex-wrap gap-1 mt-2 mr-12">
            @foreach($course->schedule as $day => $time)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-white text-blue-700 border border-blue-200">
              {{ $day }}: {{ $time }}
            </span>
            @endforeach
          </div>
          @endif
        </div>
      </div>

      @if($isEnrolled)
      <!-- Progress Bar for enrolled courses -->
      <div class="mb-4">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
          <span>التقدم</span>
          <span>{{ $progress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
        </div>
      </div>
      @endif

      <!-- Spacer to push button to bottom -->
      <div class="flex-grow"></div>

      @if($isEnrolled)
      <a href="{{ route('my.interactive-course.show', ['subdomain' => $academy->subdomain, 'course' => $course->id]) }}"
         class="w-full bg-blue-50 border-2 border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors text-center block mt-auto">
        <i class="ri-eye-line ml-1"></i>
        عرض التفاصيل
      </a>
      @else
      <a href="{{ route('interactive-courses.enroll', ['subdomain' => $academy->subdomain, 'course' => $course->id]) }}"
         class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg text-sm font-semibold hover:bg-blue-600 transition-colors text-center block mt-auto">
        اشترك في الدورة{{ $course->student_price ? ' - ' . number_format($course->student_price) . ' ر.س' : '' }}
      </a>
      @endif
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
    <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
      <i class="ri-book-open-line text-blue-400 text-4xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات متاحة</h3>
    <p class="text-gray-600 mb-6">لم يتم العثور على كورسات تطابق معايير البحث الخاصة بك</p>
    <a href="{{ route('student.interactive-courses', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
       class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
      <i class="ri-refresh-line ml-2"></i>
      عرض جميع الكورسات
    </a>
  </div>
  @endif

</x-student>
