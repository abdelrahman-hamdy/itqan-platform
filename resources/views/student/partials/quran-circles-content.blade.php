  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          <i class="ri-book-mark-line text-green-600 ml-2"></i>
          حلقات القرآن الكريم
        </h1>
        <p class="text-gray-600">
          انضم إلى حلقات القرآن الكريم وشارك في حفظ وتلاوة كتاب الله
        </p>
      </div>
      @auth
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">حلقاتي النشطة: </span>
        <span class="font-bold text-2xl text-green-600">{{ count($enrolledCircleIds) }}</span>
      </div>
      @endauth
    </div>
  </div>

  <!-- Filters Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="space-y-4">
      <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          <i class="ri-filter-3-line ml-2"></i>
          تصفية النتائج
        </h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-search-line ml-1"></i>
            البحث
          </label>
          <input type="text"
                 name="search"
                 value="{{ request('search') }}"
                 placeholder="ابحث عن حلقة..."
                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
        </div>

        <!-- Enrollment Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-user-follow-line ml-1"></i>
            حالة التسجيل
          </label>
          <div class="relative">
            <select name="enrollment_status"
                    style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white">
              <option value="">الكل</option>
              @auth
              <option value="enrolled" {{ request('enrollment_status') === 'enrolled' ? 'selected' : '' }}>حلقاتي</option>
              @endauth
              <option value="available" {{ request('enrollment_status') === 'available' ? 'selected' : '' }}>متاحة للتسجيل</option>
              <option value="open" {{ request('enrollment_status') === 'open' ? 'selected' : '' }}>مفتوحة</option>
              <option value="full" {{ request('enrollment_status') === 'full' ? 'selected' : '' }}>مكتملة</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
          </div>
        </div>

        <!-- Memorization Level -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-bar-chart-line ml-1"></i>
            مستوى الحفظ
          </label>
          <div class="relative">
            <select name="memorization_level"
                    style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white">
              <option value="">جميع المستويات</option>
              <option value="beginner" {{ request('memorization_level') === 'beginner' ? 'selected' : '' }}>مبتدئ</option>
              <option value="intermediate" {{ request('memorization_level') === 'intermediate' ? 'selected' : '' }}>متوسط</option>
              <option value="advanced" {{ request('memorization_level') === 'advanced' ? 'selected' : '' }}>متقدم</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
          </div>
        </div>

        <!-- Schedule Days (Multi-select) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="ri-calendar-line ml-1"></i>
            أيام الدراسة
          </label>
          <div class="relative" x-data="{ open: false, selected: {{ json_encode(request('schedule_days', [])) }} }">
            <button type="button" @click="open = !open"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white text-right">
              <span x-text="selected.length > 0 ? selected.length + ' أيام' : 'جميع الأيام'" class="text-gray-700"></span>
            </button>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
              <i class="ri-arrow-down-s-line text-lg"></i>
            </div>
            <div x-show="open" @click.away="open = false"
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
              @foreach(['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'] as $day)
              <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" name="schedule_days[]" value="{{ $day }}"
                       x-model="selected"
                       {{ in_array($day, request('schedule_days', [])) ? 'checked' : '' }}
                       class="ml-3 rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-gray-700">{{ $day }}</span>
              </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <!-- Buttons Row -->
      <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
          <i class="ri-search-line ml-1"></i>
          تطبيق الفلاتر
        </button>

        @if(request()->hasAny(['enrollment_status', 'memorization_level', 'schedule_days', 'search']))
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
           class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
          <i class="ri-close-circle-line ml-1"></i>
          إعادة تعيين
        </a>
        @endif
      </div>
    </form>
  </div>

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $paginatedCircles->total() }}</span>
      حلقة متاحة
    </p>
    @if($paginatedCircles->total() > 0)
    <p class="text-sm text-gray-500">
      عرض {{ $paginatedCircles->firstItem() }} - {{ $paginatedCircles->lastItem() }} من {{ $paginatedCircles->total() }}
    </p>
    @endif
  </div>

  <!-- Circles Grid -->
  @if($paginatedCircles->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($paginatedCircles as $circle)
      <x-quran-circle-card-list
        :circle="$circle"
        :academy="$academy"
        :enrolledCircleIds="$enrolledCircleIds"
        :isAuthenticated="$isAuthenticated" />
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($paginatedCircles->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Page Info -->
      <div class="text-sm text-gray-600">
        صفحة <span class="font-semibold text-gray-900">{{ $paginatedCircles->currentPage() }}</span>
        من <span class="font-semibold text-gray-900">{{ $paginatedCircles->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2">
        <!-- Previous Button -->
        @if($paginatedCircles->onFirstPage())
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </span>
        @else
        <a href="{{ $paginatedCircles->previousPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
          @php
            $start = max(1, $paginatedCircles->currentPage() - 2);
            $end = min($paginatedCircles->lastPage(), $paginatedCircles->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $paginatedCircles->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $paginatedCircles->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-green-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $paginatedCircles->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $paginatedCircles->lastPage())
          @if($end < $paginatedCircles->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $paginatedCircles->url($paginatedCircles->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
            {{ $paginatedCircles->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($paginatedCircles->hasMorePages())
        <a href="{{ $paginatedCircles->nextPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-colors">
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
        {{ $paginatedCircles->count() }} من أصل {{ $paginatedCircles->total() }} حلقة
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
      <i class="ri-book-mark-line text-gray-400 text-4xl"></i>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-3">لا توجد حلقات متاحة</h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search']))
        لم نجد حلقات تطابق معايير البحث. جرّب تعديل الفلاتر.
      @else
        لا توجد حلقات قرآن كريم متاحة حالياً. ستتم إضافة حلقات جديدة قريباً.
      @endif
    </p>
    <div class="flex items-center justify-center gap-3">
      @if(request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search']))
      <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm font-medium">
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
