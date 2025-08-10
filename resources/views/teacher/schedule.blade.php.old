<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - جدول المعلم</title>
  <meta name="description" content="جدول المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
        },
      },
    };
  </script>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.teacher-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          <i class="ri-calendar-line text-blue-600 ml-2"></i>
          جدول المواعيد
        </h1>
        <p class="text-gray-600">إدارة مواعيدك وأوقات التدريس المتاحة</p>
      </div>

      <!-- Availability Settings -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          <i class="ri-time-line text-green-600 ml-2"></i>
          أوقاتي المتاحة
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Available Days -->
          <div>
            <h4 class="font-medium text-gray-900 mb-3">الأيام المتاحة</h4>
            <div class="space-y-2">
              @php
                $days = [
                  'sunday' => 'الأحد',
                  'monday' => 'الاثنين', 
                  'tuesday' => 'الثلاثاء',
                  'wednesday' => 'الأربعاء',
                  'thursday' => 'الخميس',
                  'friday' => 'الجمعة',
                  'saturday' => 'السبت'
                ];
              @endphp
              
              @foreach($days as $key => $day)
                <div class="flex items-center">
                  <input type="checkbox" 
                         id="{{ $key }}" 
                         {{ in_array($key, $availableDays ?? []) ? 'checked' : '' }}
                         class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                  <label for="{{ $key }}" class="mr-2 text-sm text-gray-700">{{ $day }}</label>
                </div>
              @endforeach
            </div>
          </div>
          
          <!-- Available Hours -->
          <div>
            <h4 class="font-medium text-gray-900 mb-3">الساعات المتاحة</h4>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">من الساعة</label>
                <input type="time" 
                       value="{{ $availableTimeStart ? \Carbon\Carbon::parse($availableTimeStart)->format('H:i') : '08:00' }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">إلى الساعة</label>
                <input type="time" 
                       value="{{ $availableTimeEnd ? \Carbon\Carbon::parse($availableTimeEnd)->format('H:i') : '18:00' }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
              </div>
              <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                حفظ الأوقات
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Sessions -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">
            <i class="ri-calendar-event-line text-blue-600 ml-2"></i>
            الجلسات القادمة
          </h3>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            <i class="ri-add-line ml-2"></i>
            جدولة جلسة جديدة
          </button>
        </div>

        @if($upcomingSessions->count() > 0)
          <div class="space-y-4">
            @foreach($upcomingSessions as $session)
              <!-- Session Card -->
              <div class="border border-gray-200 rounded-lg p-4">
                <!-- Session details would go here -->
              </div>
            @endforeach
          </div>
        @else
          <!-- Sample upcoming sessions -->
          <div class="space-y-4">
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">
                    @if(auth()->user()->isQuranTeacher())
                      دائرة الحفظ المسائية
                    @else
                      دورة الرياضيات للمرحلة الثانوية
                    @endif
                  </h4>
                  <p class="text-sm text-gray-500">غداً - 4:00 مساءً</p>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                  <span class="text-sm text-gray-600">15 طالب</span>
                  <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                </div>
              </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">
                    @if(auth()->user()->isQuranTeacher())
                      جلسة خاصة - أحمد محمد
                    @else
                      دورة الفيزياء التطبيقية
                    @endif
                  </h4>
                  <p class="text-sm text-gray-500">الخميس - 6:00 مساءً</p>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                  <span class="text-sm text-gray-600">
                    @if(auth()->user()->isQuranTeacher())
                      جلسة فردية
                    @else
                      22 طالب
                    @endif
                  </span>
                  <span class="w-3 h-3 bg-blue-400 rounded-full"></span>
                </div>
              </div>
            </div>
          </div>
        @endif
      </div>

      <!-- Weekly Calendar View -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          <i class="ri-calendar-2-line text-purple-600 ml-2"></i>
          العرض الأسبوعي
        </h3>
        
        <!-- Simple calendar grid -->
        <div class="grid grid-cols-7 gap-4">
          @foreach(['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] as $day)
            <div class="text-center">
              <h4 class="font-medium text-gray-900 mb-2">{{ $day }}</h4>
              <div class="space-y-2">
                <div class="bg-blue-100 text-blue-800 text-xs p-2 rounded">
                  4:00 م - دورة الرياضيات
                </div>
                @if($loop->index < 4)
                  <div class="bg-green-100 text-green-800 text-xs p-2 rounded">
                    6:00 م - دائرة القرآن
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>

    </div>
  </main>
</body>
</html>