<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - الملف الشخصي للطالب</title>
  <meta name="description" content="الملف الشخصي للطالب - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
      font-family: 'Tajawal', sans-serif;
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
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="pt-20 min-h-screen transition-all duration-300" id="main-content" style="margin-right: 320px;">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Welcome Section -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          مرحباً، {{ auth()->user()->studentProfile->first_name ?? auth()->user()->name }}! 👋
        </h1>
        <p class="text-gray-600">
          استمر في رحلة التعلم واكتشف المزيد من المحتوى التعليمي المميز
        </p>
      </div>

      <!-- Quick Stats -->
      @include('components.stats.quick-stats')

      <!-- Learning Sections Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Quran Circles Section -->
        <div id="quran-circles">
          @include('components.cards.learning-section-card', [
            'title' => 'حلقات القرآن الجماعية',
            'subtitle' => 'انضم إلى حلقات القرآن وشارك في حفظ وتلاوة القرآن الكريم',
            'icon' => 'ri-group-line',
            'iconBgColor' => 'bg-green-500',
            'hideDots' => true,
            'items' => $quranCircles->take(3)->map(function($circle) {
              return [
                'title' => $circle->name,
                'description' => 'مع ' . ($circle->quranTeacher->user->name ?? 'معلم القرآن') . 
                                 ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                'icon' => 'ri-group-line',
                'iconBgColor' => 'bg-green-100',
                'iconColor' => 'text-green-600',
                'status' => 'active',
                'link' => route('student.circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circleId' => $circle->id])
              ];
            })->toArray(),
            'footer' => [
              'text' => 'عرض جميع الحلقات',
              'link' => route('student.quran-circles', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-group-line', 'value' => $stats['quranCirclesCount'] . ' دائرة نشطة'],
              ['icon' => 'ri-book-line', 'value' => $stats['quranPages'] . ' آية محفوظة']
            ]
          ])
        </div>

        <!-- Quran Private Sessions -->
        <div id="quran-private">
          @include('components.cards.learning-section-card', [
            'title' => 'حلقات القرآن الخاصة',
            'subtitle' => 'دروس فردية مع معلمي القرآن المؤهلين',
            'icon' => 'ri-user-star-line',
            'iconBgColor' => 'bg-purple-500',
            'hideDots' => true,
            'items' => $quranPrivateSessions->take(3)->map(function($subscription) {
              $nextSession = $subscription->sessions->where('scheduled_at', '>', now())->first();
              return [
                'title' => $subscription->package->getDisplayName() ?? 'اشتراك مخصص',
                'description' => 'مع ' . ($subscription->quranTeacher->full_name ?? 'معلم القرآن') . 
                                 ($nextSession ? ' - ' . $nextSession->scheduled_at->format('l، d F H:i') : ''),
                'icon' => 'ri-user-star-line',
                'iconBgColor' => 'bg-purple-100',
                'iconColor' => 'text-purple-600',
                'progress' => $subscription->progress_percentage,
                'status' => $subscription->subscription_status,
                'link' => $subscription->individualCircle ? 
                    route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circle' => $subscription->individualCircle->id]) : 
                    '#'
              ];
            })->toArray(),
            'footer' => [
              'text' => 'عرض جميع الاشتراكات',
              'link' => route('student.quran-teachers', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-user-star-line', 'value' => $stats['activeQuranSubscriptions'] . ' اشتراك نشط'],
              ['icon' => 'ri-calendar-line', 'value' => $quranTrialRequests->where('status', 'scheduled')->count() . ' جلسة تجريبية']
            ]
          ])
        </div>

        <!-- Interactive Courses -->
        <div id="interactive-courses">
          @php
            $progressService = app(\App\Services\InteractiveCourseProgressService::class);
            $interactiveCourseItems = [];
            $totalSessions = 0;
            $completedSessions = 0;

            foreach($interactiveCourses as $course) {
              $enrollment = $course->enrollments->first();
              if ($enrollment && auth()->user()->student) {
                $progress = $progressService->calculateCourseProgress($course->id, auth()->user()->student->id);
                $totalSessions += $progress['total_sessions'];
                $completedSessions += $progress['completed_sessions'];

                $interactiveCourseItems[] = [
                  'title' => $course->title,
                  'description' => 'مع ' . ($course->assignedTeacher->user->name ?? 'المعلم') . ' - ' . $progress['completed_sessions'] . ' جلسة مكتملة من ' . $progress['total_sessions'],
                  'icon' => 'ri-book-open-line',
                  'iconBgColor' => 'bg-blue-100',
                  'iconColor' => 'text-blue-600',
                  'progress' => $progress['completion_percentage'],
                  'status' => 'active',
                  'link' => route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id])
                ];
              }
            }
          @endphp

          @include('components.cards.learning-section-card', [
            'title' => 'الكورسات التفاعلية',
            'subtitle' => 'دورات أكاديمية تفاعلية في مختلف المواد الدراسية',
            'icon' => 'ri-book-open-line',
            'iconBgColor' => 'bg-blue-500',
            'hideDots' => true,
            'progressFullWidth' => true,
            'items' => $interactiveCourseItems,
            'footer' => [
              'text' => 'عرض جميع الكورسات',
              'link' => route('student.interactive-courses', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-book-line', 'value' => count($interactiveCourses) . ' كورس' . (count($interactiveCourses) != 1 ? 'ات' : '') . ' نشط' . (count($interactiveCourses) != 1 ? 'ة' : '')],
              ['icon' => 'ri-check-line', 'value' => $completedSessions . ' جلسة مكتملة']
            ]
          ])
        </div>

        <!-- Academic Private Sessions -->
        <div id="academic-private-sessions">
          @include('components.cards.learning-section-card', [
            'title' => 'دروس خاصة مع المعلمين الأكاديميين',
            'subtitle' => 'دروس فردية مع معلمي المواد الأكاديمية المؤهلين',
            'icon' => 'ri-user-3-line',
            'iconBgColor' => 'bg-orange-500',
            'hideDots' => true,
            'items' => $academicPrivateSessions->count() > 0 ? $academicPrivateSessions->take(3)->map(function($subscription) {
              return [
                'title' => $subscription->subject_name ?? 'درس أكاديمي',
                'description' => 'مع ' . ($subscription->academicTeacher->full_name ?? 'معلم أكاديمي') . 
                                 ' - ' . ($subscription->grade_level_name ?? 'مرحلة دراسية') .
                                 ' - ' . number_format($subscription->monthly_amount) . ' ' . $subscription->currency . ' شهرياً',
                'icon' => 'ri-user-3-line',
                'iconBgColor' => 'bg-orange-100',
                'iconColor' => 'text-orange-600',
                'progress' => $subscription->completion_rate ?? 0,
                'status' => $subscription->status ?? 'active',
                'link' => route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain, 'subscriptionId' => $subscription->id])
              ];
            })->toArray() : [],
            'emptyTitle' => 'لا توجد دروس خاصة بعد',
            'emptyDescription' => 'ابدأ رحلتك التعليمية من خلال الاشتراك مع أحد المعلمين الأكاديميين المؤهلين',
            'emptyActionText' => 'تصفح المعلمين الأكاديميين',
            'footer' => [
              'text' => $academicPrivateSessions->count() > 0 ? 'عرض جميع الدروس' : 'تصفح المعلمين',
              'link' => route('student.academic-teachers', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => $academicPrivateSessions->count() > 0 ? [
              ['icon' => 'ri-user-3-line', 'value' => $academicPrivateSessions->count() . ' اشتراك نشط'],
              ['icon' => 'ri-calendar-line', 'value' => $academicPrivateSessions->sum('sessions_per_month') . ' جلسة شهرياً']
            ] : []
          ])
        </div>

      </div>

      <!-- Recorded Courses Section (Full Width) -->
      <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">الكورسات المسجلة</h2>
          <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
             class="text-primary hover:text-secondary text-sm font-medium transition-colors">
            عرض جميع الكورسات
            <i class="ri-arrow-left-s-line mr-1"></i>
          </a>
        </div>
        
        @php
          $debugRecordedCourses = isset($recordedCourses) ? $recordedCourses : collect();
          $debugCount = $debugRecordedCourses->count();
        @endphp
        
        @if($debugCount > 0)
          <!-- Courses Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($debugRecordedCourses->take(6) as $course)
              <x-course-card :course="$course" :academy="auth()->user()->academy" />
            @endforeach
          </div>
        @else
          <!-- Empty State -->
          <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="max-w-md mx-auto">
              <div class="mb-4">
                <i class="ri-video-line text-4xl text-gray-400"></i>
              </div>
              <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد كورسات مسجلة</h3>
              <p class="text-gray-600 mb-4">
                لم يتم العثور على كورسات مسجلة. استكشف المزيد من الدورات المتاحة.
              </p>
              <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
                 class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-search-line ml-2"></i>
                استكشاف الكورسات
              </a>
            </div>
          </div>
        @endif
      </div>



      <!-- Quran Trial Requests Section -->
      <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">طلبات الجلسات التجريبية للقرآن</h2>
          <a href="{{ route('student.quran-teachers', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
             class="text-primary hover:text-secondary text-sm font-medium transition-colors">
            عرض جميع المعلمين
            <i class="ri-arrow-left-s-line mr-1"></i>
          </a>
        </div>
        
        @if($quranTrialRequests && $quranTrialRequests->count() > 0)
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($quranTrialRequests->take(6) as $trialRequest)
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center space-x-3 space-x-reverse mb-4">
                  @if($trialRequest->teacher)
                    @include('components.teacher-avatar', [
                      'teacher' => $trialRequest->teacher,
                      'size' => 'sm',
                      'showBadge' => false
                    ])
                  @else
                    <div class="w-12 h-12 rounded-full border border-blue-200 overflow-hidden bg-blue-50">
                      <div class="w-full h-full flex items-center justify-center text-blue-600 bg-blue-100">
                        <i class="ri-user-star-line text-sm"></i>
                      </div>
                    </div>
                  @endif
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">
                      @if($trialRequest->teacher)
                        {{ $trialRequest->teacher->full_name ?? 
                           ($trialRequest->teacher->first_name && $trialRequest->teacher->last_name ? 
                            $trialRequest->teacher->first_name . ' ' . $trialRequest->teacher->last_name : null) ?? 
                           $trialRequest->teacher->first_name ?? 
                           $trialRequest->teacher->user?->name ?? 
                           'معلم القرآن' }}
                      @else
                        معلم القرآن
                      @endif
                    </h3>
                    <p class="text-sm text-gray-500">
                      @if($trialRequest->status === 'pending')
                        في انتظار الموافقة
                      @elseif($trialRequest->status === 'scheduled')
                        مجدولة
                      @elseif($trialRequest->status === 'completed')
                        مكتملة
                      @else
                        {{ $trialRequest->status }}
                      @endif
                    </p>
                  </div>
                  <div class="text-left">
                    @if($trialRequest->status === 'pending')
                      <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                        قيد المراجعة
                      </span>
                    @elseif($trialRequest->status === 'scheduled')
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                        مجدولة
                      </span>
                    @elseif($trialRequest->status === 'completed')
                      <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                        مكتملة
                      </span>
                    @endif
                  </div>
                </div>
                
                <div class="space-y-2 mb-4">
                  @if($trialRequest->preferred_time)
                    <div class="flex items-center text-sm text-gray-600">
                      <i class="ri-time-line ml-2"></i>
                      <span>
                        @php
                          $preferredTime = $trialRequest->preferred_time;
                          
                          if ($preferredTime instanceof \Carbon\Carbon) {
                            \Carbon\Carbon::setLocale('ar');
                            echo $preferredTime->translatedFormat('l، d F Y - H:i');
                          } elseif (is_string($preferredTime) && preg_match('/^\d{4}-\d{2}-\d{2}/', $preferredTime)) {
                            try {
                              $parsedTime = \Carbon\Carbon::parse($preferredTime);
                              \Carbon\Carbon::setLocale('ar');
                              echo $parsedTime->translatedFormat('l، d F Y - H:i');
                            } catch (\Exception $e) {
                              // Fallback to displaying as is if parsing fails
                              echo $preferredTime;
                            }
                          } else {
                            // Handle text preferences like "morning", "afternoon" etc.
                            $translations = [
                              'morning' => 'صباحاً',
                              'afternoon' => 'بعد الظهر', 
                              'evening' => 'مساءً',
                              'night' => 'ليلاً'
                            ];
                            echo $translations[strtolower($preferredTime)] ?? $preferredTime;
                          }
                        @endphp
                      </span>
                    </div>
                  @endif
                  @if($trialRequest->notes)
                    <div class="flex items-start text-sm text-gray-600">
                      <i class="ri-file-text-line ml-2 mt-1"></i>
                      <span class="line-clamp-2">{{ $trialRequest->notes }}</span>
                    </div>
                  @endif
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-calendar-line ml-2"></i>
                    <span>تم الطلب: {{ $trialRequest->created_at->diffForHumans() }}</span>
                  </div>
                </div>
                
                @if($trialRequest->status === 'scheduled' && $trialRequest->scheduled_session)
                  <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain, 'sessionId' => $trialRequest->scheduled_session->id]) }}"
                     class="w-full inline-block text-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                    <i class="ri-video-line ml-1"></i>
                    دخول الجلسة
                  </a>
                @elseif($trialRequest->status === 'pending')
                  <button class="w-full bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                    <i class="ri-time-line ml-1"></i>
                    في انتظار الرد
                  </button>
                @endif
              </div>
            @endforeach
          </div>
        @else
          <!-- Empty State for Trial Requests -->
          <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
            <div class="max-w-md mx-auto">
              <div class="mb-4">
                <i class="ri-calendar-todo-line text-4xl text-gray-400"></i>
              </div>
              <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد طلبات جلسات تجريبية</h3>
              <p class="text-gray-600 mb-4">
                احجز جلسة تجريبية مجانية مع أحد معلمي القرآن المؤهلين وابدأ رحلة التعلم.
              </p>
              <a href="{{ route('student.quran-teachers', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
                 class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-add-circle-line ml-2"></i>
                طلب جلسة تجريبية
              </a>
            </div>
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

