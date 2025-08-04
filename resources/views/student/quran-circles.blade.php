<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - دوائر القرآن الكريم</title>
  <meta name="description" content="استكشف دوائر القرآن الكريم المتاحة - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
              <i class="ri-book-mark-line text-primary ml-2"></i>
              دوائر القرآن الكريم
            </h1>
            <p class="text-gray-600">
              انضم إلى دوائر القرآن الكريم وشارك في حفظ وتلاوة كتاب الله مع مجموعة من الطلاب
            </p>
          </div>
          <div class="flex items-center space-x-4 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
              <span class="text-sm text-gray-600">دوائري النشطة: </span>
              <span class="font-semibold text-primary">{{ $enrolledCircles->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      @if($enrolledCircles->count() > 0)
      <!-- My Active Circles -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">دوائري النشطة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($enrolledCircles as $circle)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="ri-group-line text-green-600 text-xl"></i>
              </div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                نشط
              </span>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $circle->name }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $circle->description }}</p>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-star-line ml-2"></i>
                <span>{{ $circle->teacher->full_name }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $circle->students_count }} طالب</span>
              </div>
              @if($circle->schedule_days_text)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>{{ $circle->schedule_days_text }}</span>
              </div>
              @endif
            </div>

            <div class="mt-6 flex space-x-2 space-x-reverse">
              <button class="flex-1 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                <i class="ri-video-line ml-1"></i>
                دخول الجلسة
              </button>
              <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ri-information-line"></i>
              </button>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Available Circles -->
      <div>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">الدوائر المتاحة</h2>
          <div class="flex items-center space-x-4 space-x-reverse">
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع المستويات</option>
              <option>مبتدئ</option>
              <option>متوسط</option>
              <option>متقدم</option>
            </select>
          </div>
        </div>

        @if($availableCircles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($availableCircles as $circle)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="ri-book-mark-line text-blue-600 text-xl"></i>
              </div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                {{ $circle->status === 'available' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $circle->status === 'available' ? 'متاح' : 'مكتمل' }}
              </span>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">{{ $circle->name }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ $circle->description }}</p>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-star-line ml-2"></i>
                <span>{{ $circle->teacher->full_name }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $circle->students_count }}/{{ $circle->max_students }} طالب</span>
              </div>
              @if($circle->schedule_days_text)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>{{ $circle->schedule_days_text }}</span>
              </div>
              @endif
              @if($circle->monthly_fee)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-money-dollar-circle-line ml-2"></i>
                <span>{{ $circle->monthly_fee }} ر.س / شهرياً</span>
              </div>
              @endif
            </div>

            <div class="mt-6">
              @if($circle->status === 'available' && $circle->students_count < $circle->max_students)
              <button class="w-full bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                <i class="ri-add-line ml-1"></i>
                انضم للدائرة
              </button>
              @else
              <button class="w-full bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                غير متاح
              </button>
              @endif
            </div>
          </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
          {{ $availableCircles->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
          <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-book-mark-line text-gray-400 text-3xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد دوائر متاحة حالياً</h3>
          <p class="text-gray-600 mb-6">ستتم إضافة دوائر جديدة قريباً. تابع معنا للحصول على التحديثات</p>
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