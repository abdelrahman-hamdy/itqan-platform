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
      @auth
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">كورساتي النشطة: </span>
        <span class="font-bold text-2xl text-blue-500">{{ $enrolledCoursesCount }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.course-filters
    :route="route('interactive-courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
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
      $enrollment = $isAuthenticated ? $course->enrollments->first() : null;
    @endphp

    <x-interactive-course-card
      :course="$course"
      :academy="$academy"
      :enrollment="$enrollment"
    />
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
    <div class="flex items-center justify-center gap-3">
      <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
        <i class="ri-refresh-line ml-2"></i>
        عرض جميع الكورسات
      </a>
      @auth
      <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للملف الشخصي
      </a>
      @else
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للرئيسية
      </a>
      @endauth
    </div>
  </div>
  @endif
