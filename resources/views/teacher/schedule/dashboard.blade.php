<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? 'أكاديمية إتقان' }} - إدارة الجدول</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
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
          <i class="ri-calendar-schedule-line text-primary ml-2"></i>
          إدارة الجدول والمواعيد
        </h1>
        <p class="text-gray-600">جدولة الجلسات التجريبية وإدارة جلسات الاشتراكات</p>
      </div>

      <!-- Success/Error Messages -->
      @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center">
            <i class="ri-check-circle-line text-lg ml-2"></i>
            {{ session('success') }}
          </div>
        </div>
      @endif

      @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center">
            <i class="ri-error-warning-line text-lg ml-2"></i>
            {{ session('error') }}
          </div>
        </div>
      @endif

      <!-- Availability Settings -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          <i class="ri-time-line text-green-600 ml-2"></i>
          إدارة أوقاتي المتاحة
        </h3>
        
        <form action="{{ route('teacher.schedule.availability.update', ['subdomain' => $academy->subdomain]) }}" method="POST">
          @csrf
          @method('PUT')
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                           name="available_days[]"
                           value="{{ $key }}"
                           id="{{ $key }}" 
                           {{ in_array($key, $teacherProfile->available_days ?? []) ? 'checked' : '' }}
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
                         name="available_time_start"
                         value="{{ $teacherProfile->available_time_start ? \Carbon\Carbon::parse($teacherProfile->available_time_start)->format('H:i') : '08:00' }}"
                         class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                </div>
                <div>
                  <label class="block text-sm text-gray-700 mb-1">إلى الساعة</label>
                  <input type="time" 
                         name="available_time_end"
                         value="{{ $teacherProfile->available_time_end ? \Carbon\Carbon::parse($teacherProfile->available_time_end)->format('H:i') : '18:00' }}"
                         class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                </div>
                <div>
                  <label class="block text-sm text-gray-700 mb-1">مدة الجلسة (بالدقائق)</label>
                  <select name="session_duration" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                                                    <option value="30" {{ ($teacherProfile->session_duration ?? 30) == 30 ? 'selected' : '' }}>30 دقيقة</option>
                                <option value="60" {{ ($teacherProfile->session_duration ?? 30) == 60 ? 'selected' : '' }}>60 دقيقة</option>
                  </select>
                </div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                  <i class="ri-save-line ml-2"></i>
                  حفظ الأوقات المتاحة
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-yellow-100">
              <i class="ri-time-line text-2xl text-yellow-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">طلبات الجلسات التجريبية</p>
              <p class="text-2xl font-bold text-yellow-600">{{ $pendingTrialRequests->count() }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-green-100">
              <i class="ri-user-follow-line text-2xl text-green-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">الاشتراكات النشطة</p>
              <p class="text-2xl font-bold text-green-600">{{ $activeSubscriptions->count() }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-blue-100">
              <i class="ri-calendar-event-line text-2xl text-blue-600"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">جلسات هذا الأسبوع</p>
              <p class="text-2xl font-bold text-blue-600">{{ $upcomingSessions->count() }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-primary-100">
              <i class="ri-calendar-check-line text-2xl text-primary"></i>
            </div>
            <div class="mr-4">
              <p class="text-sm text-gray-600">جلسات اليوم</p>
              <p class="text-2xl font-bold text-primary">{{ $todaySessions->count() }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Pending Trial Requests -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-timer-line text-yellow-600 ml-2"></i>
              طلبات الجلسات التجريبية
            </h3>
            <p class="text-sm text-gray-600 mt-1">طلبات تحتاج إلى جدولة</p>
          </div>
          
          <div class="p-6">
            @if($pendingTrialRequests->count() > 0)
              <div class="space-y-4">
                @foreach($pendingTrialRequests as $request)
                  <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center mb-2">
                          <h4 class="font-medium text-gray-900">{{ $request->student_name }}</h4>
                          <span class="mr-2 px-2 py-1 text-xs font-medium rounded-full
                            {{ $request->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ $request->status_label }}
                          </span>
                        </div>
                        
                        <div class="text-sm text-gray-600 space-y-1">
                          <div class="flex items-center">
                            <i class="ri-user-line ml-1"></i>
                            <span>{{ $request->student->name ?? $request->student_name }}</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-phone-line ml-1"></i>
                            <span>{{ $request->phone }}</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-book-line ml-1"></i>
                            <span>{{ $request->level_label }}</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-time-line ml-1"></i>
                            <span>{{ $request->time_label ?? 'غير محدد' }}</span>
                          </div>
                        </div>

                        @if($request->notes)
                          <div class="mt-2 p-2 bg-gray-50 rounded text-sm text-gray-700">
                            <i class="ri-sticky-note-line ml-1"></i>
                            {{ $request->notes }}
                          </div>
                        @endif

                        <div class="mt-3 text-xs text-gray-500">
                          طُلبت في {{ $request->created_at->diffForHumans() }}
                        </div>
                      </div>
                      
                      <div class="flex flex-col space-y-2 mr-4">
                        @if($request->status === 'pending')
                          <div class="flex space-x-2 space-x-reverse">
                            <form action="{{ route('teacher.schedule.trial.approve', ['subdomain' => $academy->subdomain, 'trialRequest' => $request->id]) }}" method="POST" class="inline">
                              @csrf
                              <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                <i class="ri-check-line ml-1"></i>
                                قبول
                              </button>
                            </form>
                            <form action="{{ route('teacher.schedule.trial.reject', ['subdomain' => $academy->subdomain, 'trialRequest' => $request->id]) }}" method="POST" class="inline">
                              @csrf
                              <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                                <i class="ri-close-line ml-1"></i>
                                رفض
                              </button>
                            </form>
                          </div>
                        @endif
                        
                        @if($request->canBeScheduled())
                          <a href="{{ route('teacher.schedule.trial.show', ['subdomain' => $academy->subdomain, 'trialRequest' => $request->id]) }}" 
                             class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center">
                            <i class="ri-calendar-event-line ml-1"></i>
                            جدولة
                          </a>
                        @endif
                        
                        <a href="/teacher-panel/quran-trial-requests/{{ $request->id }}" target="_blank"
                           class="bg-gray-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors text-center">
                          <i class="ri-external-link-line ml-1"></i>
                          التفاصيل
                        </a>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-calendar-check-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد طلبات جلسات تجريبية معلقة</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Active Subscriptions Needing Schedule Setup -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-user-follow-line text-green-600 ml-2"></i>
              الاشتراكات النشطة
            </h3>
            <p class="text-sm text-gray-600 mt-1">إعداد جدول الجلسات المنتظمة</p>
          </div>
          
          <div class="p-6">
            @if($activeSubscriptions->count() > 0)
              <div class="space-y-4">
                @foreach($activeSubscriptions as $subscription)
                  <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center mb-2">
                          <h4 class="font-medium text-gray-900">{{ $subscription->student->name }}</h4>
                          <span class="mr-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                            نشط
                          </span>
                        </div>
                        
                        <div class="text-sm text-gray-600 space-y-1">
                          <div class="flex items-center">
                            <i class="ri-package-line ml-1"></i>
                            <span>{{ $subscription->package->getDisplayName() ?? 'باقة مخصصة' }}</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-calendar-line ml-1"></i>
                            <span>{{ $subscription->total_sessions }} جلسة</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-time-line ml-1"></i>
                            <span>{{ $subscription->sessions_remaining }} جلسة متبقية</span>
                          </div>
                          <div class="flex items-center">
                            <i class="ri-calendar-todo-line ml-1"></i>
                            <span>ينتهي في {{ $subscription->expires_at->format('Y/m/d') }}</span>
                          </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                          بدأ في {{ $subscription->starts_at->diffForHumans() }}
                        </div>
                      </div>
                      
                      <div class="flex flex-col space-y-2 mr-4">
                        <a href="{{ route('teacher.schedule.subscription.show', ['subdomain' => $academy->subdomain, 'subscription' => $subscription->id]) }}" 
                           class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center">
                          <i class="ri-calendar-event-line ml-1"></i>
                          جدولة جلسات
                        </a>
                        
                        <a href="/teacher-panel/quran-sessions?tableFilters[subscription_id][value]={{ $subscription->id }}" target="_blank"
                           class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors text-center">
                          <i class="ri-time-line ml-1"></i>
                          عرض الجلسات
                        </a>
                        
                        <a href="/teacher-panel/quran-subscriptions/{{ $subscription->id }}" target="_blank"
                           class="bg-gray-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors text-center">
                          <i class="ri-external-link-line ml-1"></i>
                          التفاصيل
                        </a>
                        
                        @if($subscription->sessions_remaining > 0)
                          <button onclick="quickScheduleSession({{ $subscription->id }})" 
                                  class="bg-green-600 text-white px-3 py-1 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors text-center">
                            <i class="ri-add-line ml-1"></i>
                            جلسة سريعة
                          </button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <i class="ri-user-follow-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد اشتراكات نشطة حالياً</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Today's Sessions -->
      @if($todaySessions->count() > 0)
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-calendar-check-line text-primary ml-2"></i>
              جلسات اليوم
            </h3>
            <p class="text-sm text-gray-600 mt-1">{{ today()->format('l، d F Y') }}</p>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              @foreach($todaySessions as $session)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                      <i class="ri-time-line text-primary ml-2"></i>
                      <span class="font-medium text-gray-900">
                        {{ $session->scheduled_at->format('H:i') }}
                      </span>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                      {{ $session->session_type === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                      {{ $session->session_type === 'trial' ? 'تجريبية' : 'منتظمة' }}
                    </span>
                  </div>
                  
                  <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-center">
                      <i class="ri-user-line ml-1"></i>
                      <span>{{ $session->student->name }}</span>
                    </div>
                    <div class="flex items-center">
                      <i class="ri-timer-line ml-1"></i>
                      <span>{{ $session->duration_minutes }} دقيقة</span>
                    </div>
                    @if($session->meeting_link)
                      <div class="flex items-center">
                        <i class="ri-video-line ml-1"></i>
                        <a href="{{ $session->meeting_link }}" target="_blank" 
                           class="text-primary hover:underline">
                          رابط الاجتماع
                        </a>
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      <!-- Upcoming Sessions This Week -->
      @if($upcomingSessions->count() > 0)
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">
              <i class="ri-calendar-event-line text-blue-600 ml-2"></i>
              الجلسات القادمة (هذا الأسبوع)
            </h3>
          </div>
          
          <div class="p-6">
            <div class="space-y-4">
              @foreach($upcomingSessions->groupBy(fn($session) => $session->scheduled_at->format('Y-m-d')) as $date => $sessionsOnDate)
                <div>
                  <h4 class="font-medium text-gray-900 mb-3">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('l، d F') }}
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($sessionsOnDate as $session)
                      <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                          <span class="font-medium text-gray-900">
                            {{ $session->scheduled_at->format('H:i') }}
                          </span>
                          <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $session->session_type === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $session->session_type === 'trial' ? 'تجريبية' : 'منتظمة' }}
                          </span>
                        </div>
                        
                        <div class="space-y-1 text-sm text-gray-600">
                          <div>{{ $session->student->name }}</div>
                          <div>{{ $session->duration_minutes }} دقيقة</div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif

    </div>
  </main>

  <script>
    function quickScheduleSession(subscriptionId) {
      // Simple implementation - you can enhance this with a modal or more sophisticated scheduling
      if (confirm('هل تريد جدولة جلسة سريعة لهذا الاشتراك؟')) {
        // For now, just redirect to the subscription scheduling page
        window.location.href = `/teacher/schedule/subscription/${subscriptionId}`;
      }
    }

    // Add confirmation to approve/reject buttons
    document.addEventListener('DOMContentLoaded', function() {
      // Approve buttons
      document.querySelectorAll('button[type="submit"]').forEach(button => {
        if (button.textContent.includes('قبول')) {
          button.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من قبول هذا الطلب؟')) {
              e.preventDefault();
            }
          });
        } else if (button.textContent.includes('رفض')) {
          button.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من رفض هذا الطلب؟')) {
              e.preventDefault();
            }
          });
        }
      });
    });
  </script>

</body>
</html>