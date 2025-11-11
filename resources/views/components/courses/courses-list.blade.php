@props(['courses', 'academy', 'isStudent' => false])

<!-- Header Section -->
<div class="mb-8">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="ri-play-circle-line text-primary ml-2"></i>
        {{ $isStudent ? 'الكورسات المسجلة' : 'الدورات المسجلة' }}
      </h1>
      <p class="text-gray-600">
        اكتشف مجموعة متنوعة من الدورات المسجلة عالية الجودة
      </p>
    </div>
    <div class="flex items-center space-x-4 space-x-reverse">
      <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
        <span class="text-sm text-gray-600">إجمالي الدورات: </span>
        <span class="font-semibold text-primary">{{ $courses->total() }}</span>
      </div>
    </div>
  </div>
</div>

<!-- Filters Section -->
<div class="mb-8">
          <form method="GET" action="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="filter-card border border-gray-200 rounded-xl p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      
      <!-- Search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">البحث</label>
        <div class="relative">
          <input type="text" name="search" value="{{ request('search') }}" 
                 placeholder="ابحث في الدورات..." 
                 class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <i class="ri-search-line absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        </div>
      </div>

      <!-- Category Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">التصنيف</label>
        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">جميع التصنيفات</option>
          @foreach($categories ?? [] as $category)
            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
              {{ $category }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Level Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">المستوى</label>
        <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">جميع المستويات</option>
          @foreach($levels ?? [] as $level)
            <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
              @switch($level)
                @case('easy') سهل @break
                @case('medium') متوسط @break
                @case('hard') صعب @break
                @default {{ $level }}
              @endswitch
            </option>
          @endforeach
        </select>
      </div>

      @if($isStudent)
      <!-- Enrollment Status Filter (Student Only) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">حالة التسجيل</label>
        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>جميع الدورات</option>
          <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>الدورات المسجلة</option>
          <option value="not_enrolled" {{ request('status') == 'not_enrolled' ? 'selected' : '' }}>الدورات غير المسجلة</option>
        </select>
      </div>
      @else
      <!-- Price Filter (Public Only) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">السعر</label>
        <select name="price" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          <option value="">جميع الأسعار</option>
          <option value="free" {{ request('price') == 'free' ? 'selected' : '' }}>مجاني</option>
          <option value="paid" {{ request('price') == 'paid' ? 'selected' : '' }}>مدفوع</option>
        </select>
      </div>
      @endif
    </div>

    <!-- Filter Actions -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2 space-x-reverse">
        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
          <i class="ri-filter-line ml-2"></i>
          تطبيق الفلاتر
        </button>
                      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
                 class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
          <i class="ri-refresh-line ml-2"></i>
          إعادة تعيين
        </a>
      </div>
      
      @if(request('search') || request('category') || request('level') || request('status') || request('price'))
        <div class="text-sm text-gray-600">
          <i class="ri-information-line ml-1"></i>
          {{ $courses->total() }} نتيجة
        </div>
      @endif
    </div>
  </form>
</div>

<!-- Courses Grid -->
@if($courses->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($courses as $course)
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
        <!-- Course Image -->
        <div class="relative">
          @if($course->thumbnail_url)
            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-48 object-cover">
          @else
            <div class="w-full h-48 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
              <i class="ri-play-circle-line text-white text-4xl"></i>
            </div>
          @endif
          
          <!-- Course Status Badge -->
          <div class="absolute top-3 right-3">
            @if($course->is_free)
              <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full font-medium">مجاني</span>
            @else
              <span class="px-2 py-1 bg-primary text-white text-xs rounded-full font-medium">مدفوع</span>
            @endif
          </div>

          @if($isStudent)
          <!-- Enrollment Status Badge -->
          <div class="absolute top-3 left-3">
            @if($course->enrollments->count() > 0)
              <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full font-medium">مسجل</span>
            @else
              <span class="px-2 py-1 bg-gray-500 text-white text-xs rounded-full font-medium">غير مسجل</span>
            @endif
          </div>
          @endif
        </div>

        <!-- Course Content -->
        <div class="p-6">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $course->title }}</h3>
            </div>
            @if($course->avg_rating)
              <div class="flex items-center">
                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                <span class="text-sm text-gray-600 mr-1">{{ number_format($course->avg_rating, 1) }}</span>
              </div>
            @endif
          </div>

          <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $course->description }}</p>

          <!-- Course Meta -->
          <div class="space-y-2 mb-4">
            @if($course->subject)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-book-line ml-2"></i>
                <span>{{ $course->subject->name }}</span>
              </div>
            @endif
            @if($course->gradeLevel)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-graduation-cap-line ml-2"></i>
                <span>{{ $course->gradeLevel->name }}</span>
              </div>
            @endif
            @if($course->total_enrollments)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $course->total_enrollments }} طالب مسجل</span>
              </div>
            @endif
          </div>

          <!-- Course Footer -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              @if($course->price > 0)
                <span class="text-lg font-bold text-primary">{{ number_format($course->price) }} ر.س</span>
                @if($course->original_price && $course->original_price > $course->price)
                  <span class="text-sm text-gray-500 line-through mr-2">{{ number_format($course->original_price) }} ر.س</span>
                @endif
              @else
                <span class="text-lg font-bold text-green-600">مجاني</span>
              @endif
            </div>
            <div class="flex space-x-2 space-x-reverse">
              <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}" 
                 class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                عرض التفاصيل
              </a>
            </div>
          </div>
        </div>
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
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <i class="ri-play-circle-line text-gray-400 text-3xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد دورات متاحة</h3>
    <p class="text-gray-600 mb-6">لم يتم العثور على دورات تطابق معايير البحث الخاصة بك</p>
              <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
             class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
      <i class="ri-refresh-line ml-2"></i>
      عرض جميع الدورات
    </a>
  </div>
@endif
