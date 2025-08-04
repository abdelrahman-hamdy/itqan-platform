<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - الكورسات المسجلة</title>
  <meta name="description" content="الكورسات المسجلة - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          },
          borderRadius: {
            none: "0px",
            sm: "4px",
            DEFAULT: "8px",
            md: "12px",
            lg: "16px",
            xl: "20px",
            "2xl": "24px",
            "3xl": "32px",
            full: "9999px",
            button: "8px",
          },
        }
      }
    };
  </script>
  <style>
    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .filter-card {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95);
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-play-circle-line text-primary ml-2"></i>
              الكورسات المسجلة
            </h1>
            <p class="text-gray-600">اكتشف مجموعة متنوعة من الدورات المسجلة عالية الجودة</p>
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
        <form method="GET" action="{{ route('student.recorded-courses', ['subdomain' => $academy->subdomain]) }}" class="filter-card border border-gray-200 rounded-xl p-6">
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
              <select name="category" class="w-full py-2 px-4 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">جميع التصنيفات</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                    {{ $cat }}
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Level Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">المستوى</label>
              <select name="level" class="w-full py-2 px-4 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">جميع المستويات</option>
                @foreach($levels as $lvl)
                  <option value="{{ $lvl }}" {{ request('level') == $lvl ? 'selected' : '' }}>
                    {{ $lvl }}
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">حالة التسجيل</label>
              <select name="status" class="w-full py-2 px-4 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>جميع الكورسات</option>
                <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>مسجل بها</option>
                <option value="not_enrolled" {{ request('status') == 'not_enrolled' ? 'selected' : '' }}>غير مسجل</option>
              </select>
            </div>
          </div>

          <div class="flex justify-between">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
              <i class="ri-filter-line ml-2"></i>
              تطبيق الفلاتر
            </button>
            <a href="{{ route('student.recorded-courses', ['subdomain' => $academy->subdomain]) }}" 
               class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
              <i class="ri-refresh-line ml-2"></i>
              إعادة تعيين
            </a>
          </div>
        </form>
      </div>

      <!-- Courses Grid -->
      @if($courses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
          @foreach($courses as $course)
            @php
              $enrollment = $course->enrollments->first();
              $isEnrolled = $enrollment !== null;
              $progressPercentage = $isEnrolled ? ($enrollment->progress_percentage ?? 0) : 0;
              $instructorName = $course->instructor && $course->instructor->user 
                ? trim($course->instructor->user->first_name . ' ' . $course->instructor->user->last_name)
                : 'مدرب غير محدد';
            @endphp
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
              <!-- Course Image -->
              <div class="relative h-48 bg-gradient-to-br from-primary to-secondary">
                @if($course->featured_image)
                  <img src="{{ $course->featured_image }}" alt="{{ $course->title }}" 
                       class="w-full h-full object-cover">
                @endif
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                  @if($isEnrolled)
                    <span class="px-3 py-1 bg-green-500 text-white text-xs font-medium rounded-full">
                      مسجل
                    </span>
                  @else
                    <span class="px-3 py-1 bg-blue-500 text-white text-xs font-medium rounded-full">
                      متاح
                    </span>
                  @endif
                </div>

                <!-- Duration Badge -->
                @if($course->duration_hours)
                  <div class="absolute bottom-4 left-4">
                    <span class="px-2 py-1 bg-black bg-opacity-60 text-white text-xs rounded-md">
                      <i class="ri-time-line ml-1"></i>
                      {{ $course->duration_hours }} ساعة
                    </span>
                  </div>
                @endif
              </div>

              <!-- Course Content -->
              <div class="p-6">
                <div class="mb-4">
                  <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2">
                    {{ $course->title }}
                  </h3>
                  <p class="text-gray-600 text-sm line-clamp-3">
                    {{ $course->description }}
                  </p>
                </div>

                <!-- Course Meta -->
                <div class="flex items-center text-sm text-gray-500 mb-4">
                  <i class="ri-user-line ml-1"></i>
                  <span>{{ $instructorName }}</span>
                </div>

                <!-- Progress Bar (if enrolled) -->
                @if($isEnrolled && $progressPercentage > 0)
                  <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                      <span class="text-gray-600">التقدم</span>
                      <span class="text-primary font-medium">{{ $progressPercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                           style="width: {{ $progressPercentage }}%"></div>
                    </div>
                  </div>
                @endif

                <!-- Course Stats -->
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-500 mb-6">
                  <div class="flex items-center">
                    <i class="ri-play-circle-line ml-1"></i>
                    <span>{{ $course->total_lessons ?? 0 }} درس</span>
                  </div>
                  @if($course->level)
                    <div class="flex items-center">
                      <i class="ri-bar-chart-line ml-1"></i>
                      <span>{{ $course->level }}</span>
                    </div>
                  @endif
                </div>

                <!-- Action Button -->
                <div class="text-center">
                  @if($isEnrolled)
                    <a href="#" class="inline-block w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                      <i class="ri-play-line ml-2"></i>
                      متابعة التعلم
                    </a>
                  @else
                    <a href="#" class="inline-block w-full bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 transition-colors">
                      <i class="ri-add-circle-line ml-2"></i>
                      التسجيل في الكورس
                    </a>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
          {{ $courses->appends(request()->query())->links() }}
        </div>
      @else
        <!-- Empty State -->
        <div class="text-center py-16">
          <div class="max-w-md mx-auto">
            <div class="mb-6">
              <i class="ri-video-line text-6xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد دورات متاحة</h3>
            <p class="text-gray-600 mb-6">
              لم يتم العثور على دورات مسجلة تطابق معايير البحث الخاصة بك.
            </p>
            <a href="{{ route('student.recorded-courses', ['subdomain' => $academy->subdomain]) }}" 
               class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
              <i class="ri-refresh-line ml-2"></i>
              عرض جميع الدورات
            </a>
          </div>
        </div>
      @endif

    </div>
  </main>

  <script>
    // Auto-submit form when filters change
    document.querySelectorAll('select[name]').forEach(select => {
      select.addEventListener('change', function() {
        this.closest('form').submit();
      });
    });
  </script>
</body>
</html>