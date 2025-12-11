  <!-- Header Section -->
  <div class="mb-6 md:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          المعلمون الأكاديميون
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          اختر من بين نخبة من المعلمين المتخصصين في المواد الأكاديمية للحصول على دروس خاصة
        </p>
      </div>
      @auth
      <div class="bg-white rounded-xl px-4 md:px-6 py-2.5 md:py-3 border border-gray-200 shadow-sm flex-shrink-0">
        <span class="text-xs md:text-sm text-gray-600">معلميني الحاليين: </span>
        <span class="font-bold text-xl md:text-2xl text-violet-600">{{ $activeSubscriptionsCount }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.academic-filters
    :route="route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :subjects="$subjects"
    :gradeLevels="$gradeLevels"
    :showSearch="true"
    :showSubjects="true"
    :showGradeLevels="true"
    :showExperience="false"
    :showGender="true"
    color="violet"
  />

  <!-- Results Summary -->
  <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <p class="text-sm md:text-base text-gray-600">
      <span class="font-semibold text-gray-900">{{ $academicTeachers->total() }}</span>
      معلم متاح
    </p>
    @if($academicTeachers->total() > 0)
    <p class="text-xs md:text-sm text-gray-500">
      عرض {{ $academicTeachers->firstItem() }} - {{ $academicTeachers->lastItem() }} من {{ $academicTeachers->total() }}
    </p>
    @endif
  </div>

  <!-- Teachers Grid -->
  @if($academicTeachers->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
    @foreach($academicTeachers as $teacher)
      <x-academic-teacher-card-list
        :teacher="$teacher"
        :academy="$academy"
        :subjects="$subjects"
        :gradeLevels="$gradeLevels" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($academicTeachers->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4">
      <!-- Page Info -->
      <div class="text-xs md:text-sm text-gray-600 order-2 sm:order-1">
        صفحة <span class="font-semibold text-gray-900">{{ $academicTeachers->currentPage() }}</span>
        من <span class="font-semibold text-gray-900">{{ $academicTeachers->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2 order-1 sm:order-2">
        <!-- Previous Button -->
        @if($academicTeachers->onFirstPage())
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <i class="ri-arrow-right-s-line"></i>
          <span class="hidden sm:inline mr-1">السابق</span>
        </span>
        @else
        <a href="{{ $academicTeachers->previousPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors flex items-center">
          <i class="ri-arrow-right-s-line"></i>
          <span class="hidden sm:inline mr-1">السابق</span>
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden md:flex items-center gap-1">
          @php
            $start = max(1, $academicTeachers->currentPage() - 2);
            $end = min($academicTeachers->lastPage(), $academicTeachers->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $academicTeachers->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $academicTeachers->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-violet-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $academicTeachers->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $academicTeachers->lastPage())
          @if($end < $academicTeachers->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $academicTeachers->url($academicTeachers->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            {{ $academicTeachers->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($academicTeachers->hasMorePages())
        <a href="{{ $academicTeachers->nextPageUrl() }}"
           class="min-h-[44px] px-3 md:px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs md:text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors flex items-center">
          <span class="hidden sm:inline ml-1">التالي</span>
          <i class="ri-arrow-left-s-line"></i>
        </a>
        @else
        <span class="min-h-[44px] px-3 md:px-4 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs md:text-sm font-medium cursor-not-allowed flex items-center">
          <span class="hidden sm:inline ml-1">التالي</span>
          <i class="ri-arrow-left-s-line"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-xs md:text-sm text-gray-500 order-3 hidden sm:block">
        {{ $academicTeachers->count() }} من أصل {{ $academicTeachers->total() }} معلم
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
    <div class="w-16 h-16 md:w-24 md:h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-inner">
      <i class="ri-graduation-cap-line text-gray-400 text-2xl md:text-4xl"></i>
    </div>
    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 md:mb-3">لا يوجد معلمون متاحون</h3>
    <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['search', 'subject', 'grade_level', 'gender']))
        لم نجد معلمين يطابقون معايير البحث. جرّب تعديل الفلاتر.
      @else
        لا يوجد معلمون أكاديميون متاحون حالياً. ستتم إضافة معلمين جدد قريباً.
      @endif
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      @if(request()->hasAny(['search', 'subject', 'grade_level', 'gender']))
      <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 bg-violet-600 text-white rounded-xl hover:bg-violet-700 transition-colors shadow-sm font-medium">
        <i class="ri-refresh-line ml-2"></i>
        إعادة تعيين الفلاتر
      </a>
      @endif
      @auth
      <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للملف الشخصي
      </a>
      @else
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للرئيسية
      </a>
      @endauth
    </div>
  </div>
  @endif
