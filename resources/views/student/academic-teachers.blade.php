<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - المعلمون الأكاديميون</title>
  <meta name="description" content="استكشف المعلمين الأكاديميين المتاحين - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
      
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-graduation-cap-line text-primary ml-2"></i>
              المعلمون الأكاديميون
            </h1>
            <p class="text-gray-600">
              اختر من بين نخبة من المعلمين المتخصصين في المواد الأكاديمية للحصول على دروس خاصة
            </p>
          </div>
          <div class="flex items-center space-x-4 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
              <span class="text-sm text-gray-600">معلميني الحاليين: </span>
              <span class="font-semibold text-primary">{{ $academicProgress->groupBy('teacher_id')->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      @if($academicProgress->count() > 0)
      <!-- My Current Teachers -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">معلميني الحاليين</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($academicProgress->groupBy('teacher_id') as $teacherId => $progressItems)
          @php
            $firstProgress = $progressItems->first();
            $teacher = $firstProgress->teacher;
            $completedLessons = $progressItems->where('progress_status', 'completed')->count();
            $totalLessons = $progressItems->count();
          @endphp
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <span class="text-green-600 font-semibold">
                  {{ substr($teacher->first_name ?? 'أ', 0, 1) }}{{ substr($teacher->last_name ?? 'أ', 0, 1) }}
                </span>
              </div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                نشط
              </span>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $teacher->full_name ?? 'معلم أكاديمي' }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $firstProgress->course->name ?? 'درس أكاديمي' }}</p>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-book-line ml-2"></i>
                <span>{{ $progressItems->count() }} درس</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-check-line ml-2"></i>
                <span>{{ $completedLessons }} درس مكتمل</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-percent-line ml-2"></i>
                <span>{{ $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0 }}% تقدم</span>
              </div>
            </div>

            <div class="mt-6 flex space-x-2 space-x-reverse">
              <button class="flex-1 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                <i class="ri-video-line ml-1"></i>
                الدرس القادم
              </button>
              <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ri-message-3-line"></i>
              </button>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Available Teachers -->
      <div>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">معلمون متاحون</h2>
          <div class="flex items-center space-x-4 space-x-reverse">
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع المواد</option>
              <option>الرياضيات</option>
              <option>العلوم</option>
              <option>اللغة العربية</option>
              <option>اللغة الإنجليزية</option>
              <option>التاريخ</option>
              <option>الجغرافيا</option>
            </select>
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع المستويات</option>
              <option>المرحلة الابتدائية</option>
              <option>المرحلة المتوسطة</option>
              <option>المرحلة الثانوية</option>
            </select>
          </div>
        </div>

        @if($academicTeachers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($academicTeachers as $teacher)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <span class="text-orange-600 font-semibold">
                  {{ substr($teacher->user->first_name, 0, 1) }}{{ substr($teacher->user->last_name, 0, 1) }}
                </span>
              </div>
              <div class="flex items-center">
                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                <span class="text-sm text-gray-600 mr-1">{{ number_format($teacher->average_rating ?? 4.8, 1) }}</span>
              </div>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $teacher->user->full_name }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $teacher->bio ?? 'معلم أكاديمي مؤهل' }}</p>
            
            <div class="space-y-2">
              @if($teacher->experience_years)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>{{ $teacher->experience_years }} سنوات خبرة</span>
              </div>
              @endif
              @if($teacher->students_count)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $teacher->students_count }} طالب نشط</span>
              </div>
              @endif
              @if($teacher->hourly_rate)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-money-dollar-circle-line ml-2"></i>
                <span>{{ $teacher->hourly_rate }} ر.س / ساعة</span>
              </div>
              @endif
              @if($teacher->qualification)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-medal-line ml-2"></i>
                <span>{{ $teacher->qualification }}</span>
              </div>
              @endif
            </div>

            <!-- Subjects -->
            @if($teacher->subjects && $teacher->subjects->count() > 0)
            <div class="mt-4">
              <div class="flex flex-wrap gap-1">
                @foreach($teacher->subjects->take(3) as $subject)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ $subject->name }}
                </span>
                @endforeach
                @if($teacher->subjects->count() > 3)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                  +{{ $teacher->subjects->count() - 3 }}
                </span>
                @endif
              </div>
            </div>
            @endif

            <!-- Grade Levels -->
            @if($teacher->gradeLevels && $teacher->gradeLevels->count() > 0)
            <div class="mt-2">
              <div class="flex flex-wrap gap-1">
                @foreach($teacher->gradeLevels->take(2) as $gradeLevel)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ $gradeLevel->name }}
                </span>
                @endforeach
                @if($teacher->gradeLevels->count() > 2)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                  +{{ $teacher->gradeLevels->count() - 2 }}
                </span>
                @endif
              </div>
            </div>
            @endif

            <div class="mt-6 flex space-x-2 space-x-reverse">
              <button class="flex-1 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                <i class="ri-calendar-check-line ml-1"></i>
                حجز درس تجريبي
              </button>
              <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ri-information-line"></i>
              </button>
            </div>
          </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
          {{ $academicTeachers->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
          <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-graduation-cap-line text-gray-400 text-3xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون متاحون حالياً</h3>
          <p class="text-gray-600 mb-6">ستتم إضافة معلمين جدد قريباً. تابع معنا للحصول على التحديثات</p>
          <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            <i class="ri-arrow-right-line ml-2"></i>
            العودة للملف الشخصي
          </a>
        </div>
        @endif
      </div>

    </div>
  </main>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>

</body>
</html>