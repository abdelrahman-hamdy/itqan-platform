<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - لوحة المعلم</title>
  <meta name="description" content="لوحة المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
    :where([class^="ri-"])::before {
      content: "\f3c2";
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .stats-counter {
      font-family: 'Cairo', sans-serif;
      font-weight: bold;
    }
    
    /* Focus indicators */
    .focus\:ring-custom:focus {
      outline: 2px solid {{ auth()->user()->academy->primary_color ?? '#4169E1' }};
      outline-offset: 2px;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.teacher-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Welcome Section -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          مرحباً، {{ $teacherProfile->first_name ?? auth()->user()->name }}! 👨‍🏫
        </h1>
        <p class="text-gray-600">
          إدارة جلساتك وطلابك ومتابعة أرباحك من خلال لوحة التحكم المخصصة للمعلمين
        </p>
        <div class="mt-4">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
            <i class="ri-shield-check-line ml-2"></i>
            {{ $teacherProfile->teacher_code ?? 'معلم معتمد' }}
          </span>
          @if($teacherProfile->is_active)
            <span class="mr-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
              <i class="ri-check-line ml-2"></i>
              نشط
            </span>
          @endif
        </div>
      </div>

      <!-- Quick Stats -->
      @include('components.stats.teacher-stats', ['stats' => $stats])

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        @if($teacherType === 'quran')
          <!-- Quran Teacher Content -->
          
          <!-- Assigned Circles -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-group-line text-purple-600 ml-2"></i>
                دوائر القرآن المكلف بها
              </h3>
              <span class="text-sm text-gray-500">{{ $assignedCircles->count() }} دائرة</span>
            </div>
            
            @if($assignedCircles->count() > 0)
              <div class="space-y-3">
                @foreach($assignedCircles->take(3) as $circle)
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $circle->name }}</h4>
                      <p class="text-sm text-gray-500">{{ $circle->students->count() }} طالب</p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                      <span class="text-sm text-green-600">نشط</span>
                    </div>
                  </div>
                @endforeach
                
                @if($assignedCircles->count() > 3)
                  <div class="text-center pt-2">
                    <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                       class="text-primary hover:text-secondary text-sm font-medium">
                      عرض جميع الدوائر ({{ $assignedCircles->count() }})
                    </a>
                  </div>
                @endif
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-group-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لم يتم تعيين دوائر قرآن بعد</p>
                <p class="text-sm text-gray-400">سيقوم المشرف بتعيين الدوائر لك</p>
              </div>
            @endif
          </div>

        @else
          <!-- Academic Teacher Content -->
          
          <!-- Created Courses -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-book-line text-blue-600 ml-2"></i>
                دوراتي التي أنشأتها
              </h3>
              <span class="text-sm text-gray-500">
                {{ $createdInteractiveCourses->count() + $createdRecordedCourses->count() }} دورة
              </span>
            </div>
            
            @if($createdInteractiveCourses->count() > 0 || $createdRecordedCourses->count() > 0)
              <div class="space-y-3">
                @foreach($createdInteractiveCourses->take(2) as $course)
                  <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $course->title }}</h4>
                      <p class="text-sm text-gray-500">تفاعلية • {{ $course->enrollments->count() }} طالب</p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      @if($course->is_approved)
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        <span class="text-sm text-green-600">معتمدة</span>
                      @else
                        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                        <span class="text-sm text-yellow-600">في انتظار الموافقة</span>
                      @endif
                    </div>
                  </div>
                @endforeach
                
                @foreach($createdRecordedCourses->take(2) as $course)
                  <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $course->title }}</h4>
                      <p class="text-sm text-gray-500">مسجلة • {{ $course->enrollments->count() }} طالب</p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      @if($course->is_approved)
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        <span class="text-sm text-green-600">معتمدة</span>
                      @else
                        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                        <span class="text-sm text-yellow-600">في انتظار الموافقة</span>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-book-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لم تقم بإنشاء دورات بعد</p>
                <p class="text-sm text-gray-400">ابدأ بإنشاء دورتك الأولى</p>
              </div>
            @endif
          </div>

          <!-- Assigned Courses -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-graduation-cap-line text-green-600 ml-2"></i>
                الدورات المكلف بإدارتها
              </h3>
              <span class="text-sm text-gray-500">
                {{ $assignedInteractiveCourses->count() + $assignedRecordedCourses->count() }} دورة
              </span>
            </div>
            
            @if($assignedInteractiveCourses->count() > 0 || $assignedRecordedCourses->count() > 0)
              <div class="space-y-3">
                @foreach($assignedInteractiveCourses->take(3) as $course)
                  <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $course->title }}</h4>
                      <p class="text-sm text-gray-500">تفاعلية • مكلف من الإدارة</p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                      <span class="text-sm text-green-600">نشطة</span>
                    </div>
                  </div>
                @endforeach
                
                @foreach($assignedRecordedCourses->take(3) as $course)
                  <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $course->title }}</h4>
                      <p class="text-sm text-gray-500">مسجلة • مكلف من الإدارة</p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                      <span class="text-sm text-green-600">نشطة</span>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-graduation-cap-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لم يتم تكليفك بدورات بعد</p>
                <p class="text-sm text-gray-400">سيقوم المشرف بتكليفك بالدورات</p>
              </div>
            @endif
          </div>
        @endif

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-flash-line text-yellow-600 ml-2"></i>
            إجراءات سريعة
          </h3>
          
          <div class="space-y-3">
            <!-- Dashboard Access -->
            <a href="/teacher-panel" target="_blank"
               class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-secondary transition-colors flex items-center justify-center card-hover">
              <i class="ri-dashboard-line ml-2"></i>
              الذهاب إلى لوحة التحكم
            </a>
            
            <!-- Earnings -->
            <a href="{{ route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full bg-green-600 text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors flex items-center justify-center card-hover">
              <i class="ri-money-dollar-circle-line ml-2"></i>
              عرض الأرباح
            </a>
            
            <!-- Schedule -->
            <a href="{{ route('teacher.schedule', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center justify-center card-hover">
              <i class="ri-calendar-line ml-2"></i>
              جدول المواعيد
            </a>
            
            <!-- Students -->
            <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full bg-purple-600 text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors flex items-center justify-center card-hover">
              <i class="ri-group-line ml-2"></i>
              إدارة الطلاب
            </a>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-time-line text-gray-600 ml-2"></i>
            النشاط الأخير
          </h3>
          
          <div class="space-y-4">
            <!-- Sample activities - would be dynamic -->
            <div class="flex items-start space-x-3 space-x-reverse">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <i class="ri-check-line text-green-600 text-sm"></i>
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-900">تم إنهاء جلسة مع الطالب أحمد</p>
                <p class="text-xs text-gray-500">منذ ساعتين</p>
              </div>
            </div>
            
            <div class="flex items-start space-x-3 space-x-reverse">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="ri-user-add-line text-blue-600 text-sm"></i>
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-900">انضم طالب جديد لدورة الرياضيات</p>
                <p class="text-xs text-gray-500">أمس</p>
              </div>
            </div>
            
            <div class="flex items-start space-x-3 space-x-reverse">
              <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="ri-money-dollar-circle-line text-yellow-600 text-sm"></i>
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-900">تم استلام دفعة الراتب الشهري</p>
                <p class="text-xs text-gray-500">الأسبوع الماضي</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>
</body>
</html>