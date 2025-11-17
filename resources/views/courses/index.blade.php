<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? 'أكاديمية إتقان' }} - الدورات المسجلة</title>
  <meta name="description" content="استكشف الدورات المسجلة المتاحة - {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
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
        },
      },
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
  @auth
    @if(auth()->user()->user_type === 'student')
      <x-navigation.app-navigation role="student" />
      @include('components.sidebar.student-sidebar')
    @else
      @include('components.navigation.public-nav')
    @endif
  @else
    @include('components.navigation.public-nav')
  @endauth

  <!-- Main Content -->
  <main class="{{ auth()->check() && auth()->user()->user_type === 'student' ? 'mr-80 pt-20' : 'pt-20' }} min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-play-circle-line text-primary ml-2"></i>
              {{ auth()->check() && auth()->user()->user_type === 'student' ? 'الكورسات المسجلة' : 'الدورات المسجلة' }}
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
        <form method="GET" action="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="filter-card border border-gray-200 rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            
            <!-- Search -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">البحث</label>
              <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="ابحث في الدورات..." 
                       class="w-full pr-10 pl-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
              </div>
            </div>

            <!-- Subject Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">المادة</label>
              <select name="subject_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">جميع المواد</option>
                @foreach($subjects as $subject)
                  <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                    {{ $subject->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Grade Level Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">الصف الدراسي</label>
              <select name="grade_level_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">جميع الصفوف</option>
                @foreach($gradeLevels as $gradeLevel)
                  <option value="{{ $gradeLevel->id }}" {{ request('grade_level_id') == $gradeLevel->id ? 'selected' : '' }}>
                    {{ $gradeLevel->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Difficulty Level Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">مستوى الصعوبة</label>
              <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">جميع المستويات</option>
                @foreach($levels as $level)
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


          </div>

          <!-- Filter Actions -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 space-x-reverse">
              <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="ri-filter-line ml-2"></i>
                تطبيق الفلاتر
              </button>
              <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
                 class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="ri-refresh-line ml-2"></i>
                إعادة تعيين
              </a>
            </div>
            
            @if(request('search') || request('subject_id') || request('grade_level_id') || request('level'))
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($courses as $course)
            <x-course-card :course="$course" :academy="$academy" />
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
             class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="ri-refresh-line ml-2"></i>
            عرض جميع الدورات
          </a>
        </div>
      @endif

    </div>
  </main>

  <!-- Footer for public users -->
  @guest
    @include('components.footer.public-footer')
  @endguest

  <!-- Mobile Sidebar Toggle (for authenticated students) -->
  @auth
    @if(auth()->user()->user_type === 'student')
      <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
        <i class="ri-menu-line text-xl"></i>
      </button>
    @endif
  @endauth


</body>

</html>