<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - دروسي الخاصة</title>
  <meta name="description" content="إدارة الدروس الخاصة مع المعلمين الأكاديميين - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
              <i class="ri-book-open-line text-primary ml-2"></i>
              دروسي الخاصة
            </h1>
            <p class="text-gray-600">
              إدارة جميع الدروس الخاصة مع المعلمين الأكاديميين
            </p>
          </div>
          <div class="flex items-center space-x-4 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
              <span class="text-sm text-gray-600">إجمالي الدروس: </span>
              <span class="font-semibold text-primary">{{ $subscriptions->total() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Breadcrumb -->
      <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
          <li><a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain]) }}" class="hover:text-primary">الملف الشخصي</a></li>
          <li>/</li>
          <li class="text-gray-900">دروسي الخاصة</li>
        </ol>
      </nav>

      @if($subscriptions->count() > 0)
      <!-- Academic Private Lessons Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($subscriptions as $subscription)
        @php
          $teacher = $subscription->academicTeacher;
          $package = $subscription->academicPackage;
        @endphp
        @if($teacher)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
          <!-- Header with Teacher Info -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3 space-x-reverse">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="ri-user-3-line text-xl text-primary"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">{{ $teacher->full_name ?? 'معلم أكاديمي' }}</h3>
                <p class="text-sm text-gray-600">{{ $subscription->subject_name ?? 'مادة دراسية' }}</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
              @if($subscription->status === 'active') bg-green-100 text-green-800
              @elseif($subscription->status === 'paused') bg-yellow-100 text-yellow-800
              @elseif($subscription->status === 'completed') bg-blue-100 text-blue-800
              @elseif($subscription->status === 'cancelled') bg-red-100 text-red-800
              @else bg-gray-100 text-gray-800 @endif">
              @if($subscription->status === 'active') نشط
              @elseif($subscription->status === 'paused') متوقف
              @elseif($subscription->status === 'completed') مكتمل
              @elseif($subscription->status === 'cancelled') ملغي
              @else {{ $subscription->status }} @endif
            </span>
          </div>
          
          <!-- Subscription Details -->
          <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
              <i class="ri-book-line ml-2"></i>
              <span>{{ $subscription->grade_level_name ?? 'مرحلة دراسية' }}</span>
            </div>
            @if($package)
            <div class="flex items-center text-sm text-gray-600">
              <i class="ri-package-line ml-2"></i>
              <span>{{ $package->name }}</span>
            </div>
            @endif
            <div class="flex items-center text-sm text-gray-600">
              <i class="ri-calendar-line ml-2"></i>
              <span>{{ $subscription->sessions_per_month ?? 8 }} جلسة شهرياً</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <i class="ri-money-dollar-circle-line ml-2"></i>
              <span>{{ number_format($subscription->monthly_amount) }} {{ $subscription->currency }} شهرياً</span>
            </div>
            @if($subscription->completion_rate !== null)
            <div class="flex items-center text-sm text-gray-600">
              <i class="ri-progress-line ml-2"></i>
              <span>التقدم: {{ $subscription->completion_rate ?? 0 }}%</span>
            </div>
            @endif
          </div>

          <!-- Progress Bar -->
          @if($subscription->completion_rate !== null)
          <div class="mb-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                   style="width: {{ $subscription->completion_rate ?? 0 }}%"></div>
            </div>
          </div>
          @endif

          <!-- Action Button -->
          <div class="mt-6">
            <a href="{{ route('student.academic-private-lessons.show', ['subdomain' => auth()->user()->academy->subdomain, 'subscription' => $subscription->id]) }}" 
               class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center inline-block">
              <i class="ri-book-open-line ml-2"></i>
              عرض تفاصيل الدرس
            </a>
          </div>
        </div>
        @endif
        @endforeach
      </div>

      <!-- Pagination -->
      @if($subscriptions->hasPages())
      <div class="flex justify-center">
        {{ $subscriptions->links() }}
      </div>
      @endif

      @else
      <!-- Empty State -->
      <div class="text-center py-16">
        <div class="max-w-md mx-auto">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <i class="ri-book-open-line text-4xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد دروس خاصة بعد</h3>
          <p class="text-gray-600 mb-6">ابدأ رحلتك التعليمية من خلال الاشتراك مع أحد المعلمين الأكاديميين</p>
          <a href="{{ route('student.academic-teachers', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
             class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            <i class="ri-user-search-line ml-2"></i>
            اختر معلماً أكاديمياً
          </a>
        </div>
      </div>
      @endif

    </div>
  </main>
</body>

</html>
