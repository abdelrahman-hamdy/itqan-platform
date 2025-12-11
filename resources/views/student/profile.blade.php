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
            primary: "{{ auth()->user()->academy->brand_color?->getHexValue(500) ?? '#0ea5e9' }}",
            secondary: "{{ auth()->user()->academy->secondary_color?->getHexValue(500) ?? '#10B981' }}",
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
      outline: 2px solid {{ auth()->user()->academy->brand_color?->getHexValue(500) ?? '#0ea5e9' }};
      outline-offset: 2px;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  <x-navigation.app-navigation role="student" />
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="pt-20 min-h-screen transition-all duration-300 mr-0 md:mr-80" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

      <!-- Welcome Section -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          مرحباً، {{ auth()->user()->studentProfile->first_name ?? auth()->user()->name }}! 👋
        </h1>
        <p class="text-sm sm:text-base text-gray-600">
          استمر في رحلة التعلم واكتشف المزيد من المحتوى التعليمي المميز
        </p>
      </div>

      <!-- Quick Stats -->
      @include('components.stats.quick-stats')

      <!-- Learning Sections Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Quran Circles Section -->
        <div id="quran-circles" class="flex">
          @include('components.cards.learning-section-card', [
            'title' => 'حلقات القرآن الجماعية',
            'subtitle' => 'انضم إلى حلقات القرآن وشارك في حفظ وتلاوة القرآن الكريم',
            'icon' => 'ri-group-line',
            'iconBgColor' => 'bg-green-500',
            'primaryColor' => 'green',
            'hideDots' => true,
            'items' => $quranCircles->take(3)->map(function($circle) {
              // Determine actual status based on circle enrollment status
              $status = $circle->enrollment_status === 'open' ? 'active' :
                       ($circle->enrollment_status === 'full' ? 'active' :
                       ($circle->enrollment_status === 'closed' ? 'cancelled' : 'active'));

              return [
                'title' => $circle->name,
                'description' => 'مع ' . ($circle->quranTeacher->user->name ?? 'معلم القرآن') .
                                 ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                'icon' => 'ri-group-line',
                'iconBgColor' => 'bg-green-100',
                'iconColor' => 'text-green-600',
                'status' => $status,
                'link' => route('student.circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circleId' => $circle->id])
              ];
            })->toArray(),
            'emptyActionLink' => route('quran-circles.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => 'عرض جميع الحلقات',
              'link' => route('quran-circles.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-group-line', 'value' => $stats['quranCirclesCount'] . ' دائرة نشطة', 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- Quran Private Sessions -->
        <div id="quran-private" class="flex">
          @include('components.cards.learning-section-card', [
            'title' => 'حلقات القرآن الخاصة',
            'subtitle' => 'دروس فردية مع معلمي القرآن المؤهلين',
            'icon' => 'ri-user-star-line',
            'iconBgColor' => 'bg-yellow-500',
            'primaryColor' => 'yellow',
            'hideDots' => true,
            'items' => $quranPrivateSessions->take(3)->map(function($subscription) {
              $nextSession = $subscription->sessions->where('scheduled_at', '>', now())->first();
              return [
                'title' => $subscription->package?->getDisplayName() ?? 'اشتراك مخصص',
                'description' => 'مع ' . ($subscription->quranTeacher->full_name ?? 'معلم القرآن') .
                                 ($nextSession ? ' - ' . formatDateTimeArabic($nextSession->scheduled_at) : ''),
                'icon' => 'ri-user-star-line',
                'iconBgColor' => 'bg-yellow-100',
                'iconColor' => 'text-yellow-600',
                'progress' => $subscription->progress_percentage,
                'status' => $subscription->status,
                'link' => $subscription->individualCircle ?
                    route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circle' => $subscription->individualCircle->id]) :
                    '#'
              ];
            })->toArray(),
            'emptyActionLink' => route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => 'عرض جميع معلمي القرآن',
              'link' => route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-user-star-line', 'value' => $stats['activeQuranSubscriptions'] . ' اشتراك نشط', 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- Interactive Courses -->
        <div id="interactive-courses" class="flex">
          @php
            // Calculate progress directly from sessions (replacing InteractiveCourseProgressService)
            $interactiveCourseItems = [];
            $totalSessions = 0;
            $completedSessions = 0;

            foreach($interactiveCourses as $course) {
              $enrollment = $course->enrollments->first();
              if ($enrollment && auth()->user()->studentProfile) {
                // Calculate progress from course sessions
                $courseSessions = $course->sessions;
                $courseTotal = $courseSessions->count();
                $courseCompleted = $courseSessions->where('status', 'completed')->count();
                $completionPercentage = $courseTotal > 0 ? round(($courseCompleted / $courseTotal) * 100) : 0;

                $totalSessions += $courseTotal;
                $completedSessions += $courseCompleted;

                // Determine status based on enrollment
                $enrollmentStatus = $enrollment->enrollment_status ?? 'enrolled';
                $status = $enrollmentStatus === 'enrolled' ? 'active' :
                         ($enrollmentStatus === 'completed' ? 'active' :
                         ($enrollmentStatus === 'withdrawn' ? 'cancelled' : 'pending'));

                $interactiveCourseItems[] = [
                  'title' => $course->title,
                  'description' => 'مع ' . ($course->assignedTeacher->user->name ?? 'المعلم') . ' - ' . $courseCompleted . ' جلسة مكتملة من ' . $courseTotal,
                  'icon' => 'ri-book-open-line',
                  'iconBgColor' => 'bg-blue-100',
                  'iconColor' => 'text-blue-600',
                  'progress' => $completionPercentage,
                  'status' => $status,
                  'link' => route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'courseId' => $course->id])
                ];
              }
            }
          @endphp

          @include('components.cards.learning-section-card', [
            'title' => 'الكورسات التفاعلية',
            'subtitle' => 'دورات أكاديمية تفاعلية في مختلف المواد الدراسية',
            'icon' => 'ri-book-open-line',
            'iconBgColor' => 'bg-blue-500',
            'primaryColor' => 'blue',
            'hideDots' => true,
            'progressFullWidth' => true,
            'items' => $interactiveCourseItems,
            'emptyActionLink' => route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => 'عرض جميع الكورسات',
              'link' => route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-book-line', 'value' => count($interactiveCourses) . ' كورس' . (count($interactiveCourses) != 1 ? 'ات' : '') . ' نشط' . (count($interactiveCourses) != 1 ? 'ة' : ''), 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- Academic Private Sessions -->
        <div id="academic-private-sessions" class="flex">
          @include('components.cards.learning-section-card', [
            'title' => 'دروس خاصة مع المعلمين الأكاديميين',
            'subtitle' => 'دروس فردية مع معلمي المواد الأكاديمية المؤهلين',
            'icon' => 'ri-user-3-line',
            'iconBgColor' => 'bg-violet-500',
            'primaryColor' => 'violet',
            'hideDots' => true,
            'items' => $academicPrivateSessions->count() > 0 ? $academicPrivateSessions->take(3)->map(function($subscription) {
              return [
                'title' => $subscription->subject_name ?? 'درس أكاديمي',
                'description' => 'مع ' . ($subscription->academicTeacher->full_name ?? 'معلم أكاديمي') .
                                 ' - ' . ($subscription->grade_level_name ?? 'مرحلة دراسية') .
                                 ' - ' . number_format($subscription->monthly_amount) . ' ' . $subscription->currency . ' شهرياً',
                'icon' => 'ri-user-3-line',
                'iconBgColor' => 'bg-violet-100',
                'iconColor' => 'text-violet-600',
                'progress' => $subscription->completion_rate ?? 0,
                'status' => $subscription->status ?? 'active',
                'link' => route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain, 'subscriptionId' => $subscription->id])
              ];
            })->toArray() : [],
            'emptyTitle' => 'لا توجد دروس خاصة بعد',
            'emptyDescription' => 'ابدأ رحلتك التعليمية من خلال الاشتراك مع أحد المعلمين الأكاديميين المؤهلين',
            'emptyActionText' => 'تصفح المعلمين الأكاديميين',
            'emptyActionLink' => route('academic-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => 'عرض جميع المعلمين الأكاديميين',
              'link' => route('academic-teachers.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => $academicPrivateSessions->count() > 0 ? [
              ['icon' => 'ri-user-3-line', 'value' => $academicPrivateSessions->count() . ' اشتراك نشط', 'isActiveCount' => true]
            ] : []
          ])
        </div>

      </div>

      <!-- Recorded Courses Section (Full Width) -->
      <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">الكورسات المسجلة</h2>
          <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
             class="text-cyan-500 hover:text-cyan-600 text-sm font-medium transition-colors">
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
                <i class="ri-video-line text-4xl text-cyan-400"></i>
              </div>
              <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد كورسات مسجلة</h3>
              <p class="text-gray-600 mb-4">
                لم يتم العثور على كورسات مسجلة. استكشف المزيد من الدورات المتاحة.
              </p>
              <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                 class="inline-block bg-cyan-500 text-white px-6 py-2 rounded-lg hover:bg-cyan-600 transition-colors">
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
          <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" 
             class="text-green-500 hover:text-green-600 text-sm font-medium transition-colors">
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
                
                @if($trialRequest->trialSession)
                  <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}"
                     class="w-full inline-block text-center {{ $trialRequest->status === 'completed' ? 'bg-green-600 hover:bg-green-700' : 'bg-primary hover:bg-secondary' }} text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="ri-video-line ml-1"></i>
                    {{ $trialRequest->status === 'completed' ? 'مراجعة الجلسة' : 'دخول الجلسة' }}
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
              <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                 class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
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
  <button id="sidebar-toggle-mobile"
          @click="$dispatch('toggle-sidebar')"
          class="fixed bottom-6 right-6 md:hidden bg-primary text-white min-h-[56px] min-w-[56px] p-4 rounded-full shadow-lg z-50 flex items-center justify-center">
    <i class="ri-menu-line text-2xl"></i>
  </button>


</body>
</html> 

