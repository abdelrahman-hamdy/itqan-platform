<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - ملف القرآن الكريم</title>
  <meta name="description" content="ملف القرآن الكريم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
    };
  </script>
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          <i class="ri-book-mark-line text-green-600 ml-2"></i>
          ملف القرآن الكريم
        </h1>
        <p class="text-gray-600">تابع رحلتك في حفظ وتلاوة القرآن الكريم</p>
      </div>

      <!-- Quran Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-green-100">
              <i class="ri-group-line text-2xl text-green-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">حلقات القرآن</p>
              <p class="text-2xl font-bold text-green-600">{{ $quranStats['totalCircles'] }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-blue-100">
              <i class="ri-user-star-line text-2xl text-blue-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">الاشتراكات النشطة</p>
              <p class="text-2xl font-bold text-blue-600">{{ $quranStats['activeSubscriptions'] }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-purple-100">
              <i class="ri-calendar-check-line text-2xl text-purple-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">الجلسات المكتملة</p>
              <p class="text-2xl font-bold text-purple-600">{{ $quranStats['completedSessions'] }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-yellow-100">
              <i class="ri-book-line text-2xl text-yellow-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">الآيات المحفوظة</p>
              <p class="text-2xl font-bold text-yellow-600">{{ $quranStats['totalVersesMemorized'] }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Overview -->
      @if($quranStats['averageProgress'] > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="ri-progress-3-line text-primary ml-2"></i>
            إجمالي التقدم
          </h3>
          
          <div class="mb-4">
            <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
              <span>متوسط التقدم العام</span>
              <span>{{ $quranStats['averageProgress'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-500" 
                   style="width: {{ $quranStats['averageProgress'] }}%"></div>
            </div>
          </div>
        </div>
      @endif

      <!-- Trial Requests Section -->
      @if($quranTrialRequests->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-time-line text-yellow-600 ml-2"></i>
              طلبات الجلسات التجريبية
            </h3>
            <p class="text-sm text-gray-600 mt-1">جلساتك التجريبية مع معلمي القرآن</p>
          </div>
          
          <div class="p-6">
            <div class="space-y-4">
              @foreach($quranTrialRequests as $request)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center mb-2">
                        <h4 class="font-medium text-gray-900">{{ $request->teacher->full_name }}</h4>
                        <span class="mr-2 px-2 py-1 text-xs font-medium rounded-full
                          {{ $request->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                             ($request->status === 'scheduled' ? 'bg-blue-100 text-blue-800' :
                             ($request->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) }}">
                          {{ $request->status_label }}
                        </span>
                      </div>
                      
                      <div class="text-sm text-gray-600 space-y-1">
                        <div class="flex items-center">
                          <i class="ri-calendar-line ml-1"></i>
                          <span>طُلب في {{ $request->created_at->format('Y/m/d') }}</span>
                        </div>
                        <div class="flex items-center">
                          <i class="ri-book-line ml-1"></i>
                          <span>{{ $request->level_label }}</span>
                        </div>
                        @if($request->scheduled_at)
                          <div class="flex items-center">
                            <i class="ri-time-line ml-1"></i>
                            <span>موعد الجلسة: {{ $request->scheduled_at->format('Y/m/d H:i') }}</span>
                          </div>
                        @endif
                      </div>
                      
                      @if($request->meeting_link && $request->status === 'scheduled')
                        <div class="mt-3">
                          <a href="{{ $request->meeting_link }}" target="_blank" 
                             class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-primary text-white hover:bg-secondary transition-colors">
                            <i class="ri-video-line ml-1"></i>
                            الانضمام للجلسة
                          </a>
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      <!-- Active Subscriptions -->
      @if($quranSubscriptions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-user-star-line text-blue-600 ml-2"></i>
              اشتراكات القرآن الكريم
            </h3>
            <p class="text-sm text-gray-600 mt-1">دروسك الخاصة مع معلمي القرآن</p>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              @foreach($quranSubscriptions as $subscription)
                <div class="border border-gray-200 rounded-lg p-6 hover:border-primary transition-colors">
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h4 class="font-bold text-gray-900">{{ $subscription->quranTeacher?->full_name ?? 'معلم غير محدد' }}</h4>
                      <p class="text-sm text-gray-600">{{ $subscription->package->getDisplayName() ?? 'اشتراك مخصص' }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                      {{ $subscription->subscription_status === 'active' ? 'bg-green-100 text-green-800' : 
                         ($subscription->subscription_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                      {{ $subscription->subscription_status === 'active' ? 'نشط' : 
                         ($subscription->subscription_status === 'pending' ? 'في الانتظار' : $subscription->subscription_status) }}
                    </span>
                  </div>
                  
                  <!-- Progress Bar -->
                  @if($subscription->progress_percentage)
                    <div class="mb-4">
                      <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
                        <span>التقدم</span>
                        <span>{{ $subscription->progress_percentage }}%</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500" 
                             style="width: {{ $subscription->progress_percentage }}%"></div>
                      </div>
                    </div>
                  @endif
                  
                  <!-- Sessions Info -->
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                      <div class="font-bold text-gray-900">{{ $subscription->sessions_used }}</div>
                      <div class="text-gray-600">جلسات مكتملة</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                      <div class="font-bold text-gray-900">{{ $subscription->sessions_remaining }}</div>
                      <div class="text-gray-600">جلسات متبقية</div>
                    </div>
                  </div>
                  
                  <!-- Recent Sessions -->
                  @if($subscription->sessions->count() > 0)
                    <div class="mt-4">
                      <h5 class="font-medium text-gray-900 mb-2">الجلسات الأخيرة:</h5>
                      <div class="space-y-2">
                        @foreach($subscription->sessions->take(3) as $session)
                          <div class="flex items-center justify-between text-sm p-2 bg-gray-50 rounded">
                            <div class="flex items-center">
                              <i class="ri-calendar-line text-gray-500 ml-1"></i>
                              <span>{{ $session->scheduled_at->format('Y/m/d') }}</span>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full
                              {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'bg-green-100 text-green-800' : 
                                 ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                              {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'مكتملة' : 
                                 ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'مجدولة' : $session->status->label()) }}
                            </span>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @endif
                  
                  <!-- Verses Memorized -->
                  @if($subscription->verses_memorized > 0)
                    <div class="mt-4 text-center p-3 bg-green-50 rounded-lg">
                      <div class="font-bold text-green-700">{{ $subscription->verses_memorized }}</div>
                      <div class="text-green-600 text-sm">آية محفوظة</div>
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      <!-- Quran Circles -->
      @if($quranCircles->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-group-line text-green-600 ml-2"></i>
              حلقات القرآن الكريم
            </h3>
            <p class="text-sm text-gray-600 mt-1">الحلقات الجماعية للحفظ والتلاوة</p>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              @foreach($quranCircles as $circle)
                <div class="border border-gray-200 rounded-lg p-6 hover:border-primary transition-colors">
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h4 class="font-bold text-gray-900">{{ $circle->name }}</h4>
                      <p class="text-sm text-gray-600">مع {{ $circle->quranTeacher->user->full_name }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                      نشط
                    </span>
                  </div>
                  
                  <div class="text-sm text-gray-600 space-y-2 mb-4">
                    <div class="flex items-center">
                      <i class="ri-group-line ml-1"></i>
                      <span>{{ $circle->students->count() }} طالب</span>
                    </div>
                    <div class="flex items-center">
                      <i class="ri-calendar-line ml-1"></i>
                      <span>{{ $circle->schedule_days_text ?? 'لم يحدد بعد' }}</span>
                    </div>
                    <div class="flex items-center">
                      <i class="ri-time-line ml-1"></i>
                      <span>{{ $circle->start_time ? $circle->start_time->format('H:i') : 'لم يحدد بعد' }}</span>
                    </div>
                  </div>
                  
                  @if($circle->description)
                    <div class="text-sm text-gray-700 p-3 bg-gray-50 rounded-lg">
                      {{ $circle->description }}
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      <!-- Empty State -->
      @if($quranCircles->count() === 0 && $quranSubscriptions->count() === 0 && $quranTrialRequests->count() === 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <div class="w-24 h-24 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
            <i class="ri-book-mark-line text-4xl text-green-600"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">ابدأ رحلتك مع القرآن الكريم</h3>
          <p class="text-gray-600 mb-6">
            لم تبدأ بعد في رحلة تعلم القرآن الكريم. ابدأ الآن واختر معلمك المفضل أو انضم لإحدى الحلقات.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('public.quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
               class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-secondary transition-colors">
              <i class="ri-search-line ml-2"></i>
              تصفح معلمي القرآن
            </a>
            <a href="{{ route('public.quran-circles.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
               class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors">
              <i class="ri-group-line ml-2"></i>
              تصفح حلقات القرآن
            </a>
          </div>
        </div>
      @endif

    </div>
  </main>

</body>
</html>