  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          معلمو القرآن الكريم
        </h1>
        <p class="text-gray-600">
          اختر من بين نخبة من معلمي القرآن الكريم المؤهلين للحصول على دروس خاصة
        </p>
      </div>
      @auth
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">معلميني الحاليين: </span>
        <span class="font-bold text-2xl text-yellow-600">{{ $activeSubscriptionsCount }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.quran-filters
    :route="route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :showSearch="true"
    :showExperience="true"
    :showGender="true"
    :showDays="true"
    color="yellow"
  />

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $quranTeachers->total() }}</span>
      معلم متاح
    </p>
    @if($quranTeachers->total() > 0)
    <p class="text-sm text-gray-500">
      عرض {{ $quranTeachers->firstItem() }} - {{ $quranTeachers->lastItem() }} من {{ $quranTeachers->total() }}
    </p>
    @endif
  </div>

  <!-- Teachers Grid -->
  @if($quranTeachers->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    @foreach($quranTeachers as $teacher)
      <x-quran-teacher-card-list
        :teacher="$teacher"
        :academy="$academy"
        :availablePackages="$availablePackages ?? collect()" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($quranTeachers->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Page Info -->
      <div class="text-sm text-gray-600">
        صفحة <span class="font-semibold text-gray-900">{{ $quranTeachers->currentPage() }}</span>
        من <span class="font-semibold text-gray-900">{{ $quranTeachers->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2">
        <!-- Previous Button -->
        @if($quranTeachers->onFirstPage())
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </span>
        @else
        <a href="{{ $quranTeachers->previousPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
          @php
            $start = max(1, $quranTeachers->currentPage() - 2);
            $end = min($quranTeachers->lastPage(), $quranTeachers->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $quranTeachers->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $quranTeachers->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-yellow-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $quranTeachers->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $quranTeachers->lastPage())
          @if($end < $quranTeachers->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $quranTeachers->url($quranTeachers->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $quranTeachers->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($quranTeachers->hasMorePages())
        <a href="{{ $quranTeachers->nextPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
          التالي
          <i class="ri-arrow-left-s-line"></i>
        </a>
        @else
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          التالي
          <i class="ri-arrow-left-s-line"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-sm text-gray-500">
        {{ $quranTeachers->count() }} من أصل {{ $quranTeachers->total() }} معلم
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
      <i class="ri-user-star-line text-gray-400 text-4xl"></i>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-3">لا يوجد معلمون متاحون</h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
        لم نجد معلمين يطابقون معايير البحث. جرّب تعديل الفلاتر.
      @else
        لا يوجد معلمو قرآن كريم متاحون حالياً. ستتم إضافة معلمين جدد قريباً.
      @endif
    </p>
    <div class="flex items-center justify-center gap-3">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
      <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors shadow-sm font-medium">
        <i class="ri-refresh-line ml-2"></i>
        إعادة تعيين الفلاتر
      </a>
      @endif
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
