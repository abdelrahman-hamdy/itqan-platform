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
                حلقات القرآن المكلف بها
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
                      عرض جميع الحلقات ({{ $assignedCircles->count() }})
                    </a>
                  </div>
                @endif
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-group-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لم يتم تعيين حلقات قرآن بعد</p>
                <p class="text-sm text-gray-400">سيقوم المشرف بتعيين الحلقات لك</p>
              </div>
            @endif
          </div>

          <!-- Trial Requests for Quran Teachers -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-user-add-line text-orange-600 ml-2"></i>
                طلبات الجلسات التجريبية
              </h3>
              <span class="text-sm text-gray-500">{{ $pendingTrialRequests->count() }} طلب معلق</span>
            </div>
            
            @if($pendingTrialRequests->count() > 0)
              <div class="space-y-3">
                @foreach($pendingTrialRequests->take(3) as $request)
                  <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $request->student->name ?? 'طالب جديد' }}</h4>
                      <p class="text-sm text-gray-500">
                        المستوى المطلوب: {{ $request->current_level }}
                        • {{ $request->created_at->diffForHumans() }}
                      </p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      @if($request->status === 'pending')
                        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                        <span class="text-sm text-yellow-600">في الانتظار</span>
                      @elseif($request->status === 'approved')
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        <span class="text-sm text-green-600">معتمد</span>
                      @endif
                    </div>
                  </div>
                @endforeach
                
                @if($pendingTrialRequests->count() > 3)
                  <div class="text-center pt-2">
                    <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                       class="text-primary hover:text-secondary text-sm font-medium">
                      عرض جميع الطلبات ({{ $pendingTrialRequests->count() }})
                    </a>
                  </div>
                @endif
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-user-add-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد طلبات جلسات تجريبية</p>
                <p class="text-sm text-gray-400">ستظهر الطلبات الجديدة هنا</p>
              </div>
            @endif
          </div>

          <!-- Active Subscriptions for Quran Teachers -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-book-open-line text-green-600 ml-2"></i>
                الاشتراكات النشطة
              </h3>
              <span class="text-sm text-gray-500">{{ $activeSubscriptions->count() }} اشتراك نشط</span>
            </div>
            
            @if($activeSubscriptions->count() > 0)
              <div class="space-y-3">
                @foreach($activeSubscriptions->take(3) as $subscription)
                  <x-cards.subscription-card 
                    :subscription="$subscription" 
                    view-type="teacher" 
                    :compact="true" 
                    :show-actions="false" />
                @endforeach
                
                @if($activeSubscriptions->count() > 3)
                  <div class="text-center pt-2">
                    <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                       class="text-primary hover:text-secondary text-sm font-medium">
                      عرض جميع الاشتراكات ({{ $activeSubscriptions->count() }})
                    </a>
                  </div>
                @endif
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-book-open-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد اشتراكات نشطة</p>
                <p class="text-sm text-gray-400">ستظهر الاشتراكات الجديدة هنا</p>
              </div>
            @endif
          </div>

          <!-- Recent Sessions for Quran Teachers -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-time-line text-blue-600 ml-2"></i>
                الجلسات الأخيرة
              </h3>
              <span class="text-sm text-gray-500">{{ $recentSessions->count() }} جلسة</span>
            </div>
            
            @if($recentSessions->count() > 0)
              <div class="space-y-3">
                @foreach($recentSessions->take(3) as $session)
                  <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ $session->student->name ?? 'طالب' }}</h4>
                      <p class="text-sm text-gray-500">
                        {{ $session->scheduled_at ? $session->scheduled_at->format('d/m/Y H:i') : 'غير محدد' }}
                      </p>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                      @if($session->status === 'scheduled')
                        <span class="w-3 h-3 bg-blue-400 rounded-full"></span>
                        <span class="text-sm text-blue-600">مجدولة</span>
                      @elseif($session->status === 'completed')
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        <span class="text-sm text-green-600">مكتملة</span>
                      @endif
                    </div>
                  </div>
                @endforeach
                
                @if($recentSessions->count() > 3)
                  <div class="text-center pt-2">
                    <a href="/teacher-panel/quran-sessions" target="_blank"
                       class="text-primary hover:text-secondary text-sm font-medium">
                      عرض جميع الجلسات ({{ $recentSessions->count() }})
                    </a>
                  </div>
                @endif
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-time-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد جلسات حديثة</p>
                <p class="text-sm text-gray-400">ستظهر الجلسات هنا</p>
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





      </div>
    </div>
  </main>
</body>
</html>


