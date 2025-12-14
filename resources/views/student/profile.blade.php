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
    <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">

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
        @php
          $recordedCoursesCollection = isset($recordedCourses) ? $recordedCourses : collect();
          $recordedCoursesCount = $recordedCoursesCollection->count();
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
          <!-- Card Header -->
          <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3 space-x-reverse">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cyan-500">
                  <i class="ri-video-line text-xl text-white"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">الكورسات المسجلة</h3>
                  <p class="text-sm text-gray-500">دورات مسجلة يمكنك مشاهدتها في أي وقت</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Content -->
          <div class="p-6">
            @if($recordedCoursesCount > 0)
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($recordedCoursesCollection->take(6) as $course)
                  <a href="{{ route('courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'id' => $course->id]) }}" class="block">
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-cyan-100 ml-3">
                        <i class="ri-video-line text-sm text-cyan-600"></i>
                      </div>
                      <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">{{ $course->title }}</h4>
                        <p class="text-sm text-gray-500 truncate">
                          {{ $course->lessons_count ?? $course->lessons->count() ?? 0 }} درس
                          @if($course->instructor)
                            - {{ $course->instructor->name }}
                          @endif
                        </p>
                        @if($course->pivot && $course->pivot->progress)
                          <div class="mt-2">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                              <span>التقدم</span>
                              <span>{{ $course->pivot->progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                              <div class="bg-cyan-600 h-2 rounded-full transition-all duration-300"
                                   style="width: {{ $course->pivot->progress }}%"></div>
                            </div>
                          </div>
                        @endif
                      </div>
                      <div class="flex items-center space-x-2 space-x-reverse mr-3">
                        @if($course->is_published)
                          <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">متاح</span>
                        @endif
                        <div class="text-cyan-600 hover:text-cyan-700 transition-colors">
                          <i class="ri-arrow-left-s-line"></i>
                        </div>
                      </div>
                    </div>
                  </a>
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="ri-video-line text-2xl text-gray-400"></i>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">لا توجد كورسات مسجلة</h4>
                <p class="text-gray-500 mb-4">لم يتم العثور على كورسات مسجلة. استكشف المزيد من الدورات المتاحة.</p>
                <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" class="bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-cyan-700 transition-colors inline-block">
                  استكشاف الكورسات
                </a>
              </div>
            @endif
          </div>

          <!-- Card Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
                <div class="flex items-center space-x-1 space-x-reverse">
                  <i class="ri-video-line"></i>
                  <span>{{ $recordedCoursesCount }} كورس{{ $recordedCoursesCount > 1 ? 'ات' : '' }}</span>
                </div>
              </div>
              <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                 class="text-cyan-600 hover:text-cyan-700 text-sm font-medium transition-colors">
                عرض جميع الكورسات
                <i class="ri-arrow-left-s-line mr-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>



      <!-- Quran Trial Requests Section -->
      <div class="mt-8">
        @php
          $trialRequestsCollection = isset($quranTrialRequests) ? $quranTrialRequests : collect();
          $trialRequestsCount = $trialRequestsCollection->count();
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
          <!-- Card Header -->
          <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3 space-x-reverse">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-emerald-500">
                  <i class="ri-calendar-todo-line text-xl text-white"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">طلبات الجلسات التجريبية للقرآن</h3>
                  <p class="text-sm text-gray-500">جلسات تجريبية مجانية مع معلمي القرآن المؤهلين</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Content -->
          <div class="p-6">
            @if($trialRequestsCount > 0)
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($trialRequestsCollection->take(6) as $trialRequest)
                  @php
                    $teacherName = 'معلم القرآن';
                    if($trialRequest->teacher) {
                      $teacherName = $trialRequest->teacher->full_name ??
                         ($trialRequest->teacher->first_name && $trialRequest->teacher->last_name ?
                          $trialRequest->teacher->first_name . ' ' . $trialRequest->teacher->last_name : null) ??
                         $trialRequest->teacher->first_name ??
                         $trialRequest->teacher->user?->name ??
                         'معلم القرآن';
                    }

                    $statusConfig = [
                      'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'قيد المراجعة'],
                      'scheduled' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'مجدولة'],
                      'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'مكتملة'],
                      'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'ملغية'],
                    ];
                    $statusStyle = $statusConfig[$trialRequest->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $trialRequest->status];

                    $preferredTimeText = '';
                    if($trialRequest->preferred_time) {
                      $preferredTime = $trialRequest->preferred_time;
                      if ($preferredTime instanceof \Carbon\Carbon) {
                        \Carbon\Carbon::setLocale('ar');
                        $preferredTimeText = $preferredTime->translatedFormat('l، d F Y');
                      } elseif (is_string($preferredTime) && preg_match('/^\d{4}-\d{2}-\d{2}/', $preferredTime)) {
                        try {
                          $parsedTime = \Carbon\Carbon::parse($preferredTime);
                          \Carbon\Carbon::setLocale('ar');
                          $preferredTimeText = $parsedTime->translatedFormat('l، d F Y');
                        } catch (\Exception $e) {
                          $preferredTimeText = $preferredTime;
                        }
                      } else {
                        $translations = ['morning' => 'صباحاً', 'afternoon' => 'بعد الظهر', 'evening' => 'مساءً', 'night' => 'ليلاً'];
                        $preferredTimeText = $translations[strtolower($preferredTime)] ?? $preferredTime;
                      }
                    }
                  @endphp

                  @if($trialRequest->trialSession)
                    <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain, 'sessionId' => $trialRequest->trialSession->id]) }}" class="block">
                  @else
                    <div>
                  @endif
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors {{ $trialRequest->trialSession ? 'cursor-pointer' : '' }}">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-100 ml-3">
                        <i class="ri-user-star-line text-sm text-emerald-600"></i>
                      </div>
                      <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">{{ $teacherName }}</h4>
                        <p class="text-sm text-gray-500 truncate">
                          @if($preferredTimeText)
                            {{ $preferredTimeText }}
                          @else
                            تم الطلب: {{ $trialRequest->created_at->diffForHumans() }}
                          @endif
                        </p>
                      </div>
                      <div class="flex items-center space-x-2 space-x-reverse mr-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusStyle['bg'] }} {{ $statusStyle['text'] }}">
                          {{ $statusStyle['label'] }}
                        </span>
                        @if($trialRequest->trialSession)
                          <div class="text-emerald-600 hover:text-emerald-700 transition-colors">
                            <i class="ri-arrow-left-s-line"></i>
                          </div>
                        @endif
                      </div>
                    </div>
                  @if($trialRequest->trialSession)
                    </a>
                  @else
                    </div>
                  @endif
                @endforeach
              </div>
            @else
              <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="ri-calendar-todo-line text-2xl text-gray-400"></i>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">لا توجد طلبات جلسات تجريبية</h4>
                <p class="text-gray-500 mb-4">احجز جلسة تجريبية مجانية مع أحد معلمي القرآن المؤهلين وابدأ رحلة التعلم.</p>
                <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors inline-block">
                  طلب جلسة تجريبية
                </a>
              </div>
            @endif
          </div>

          <!-- Card Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
                <div class="flex items-center space-x-1 space-x-reverse">
                  <i class="ri-calendar-todo-line"></i>
                  <span>{{ $trialRequestsCount }} طلب{{ $trialRequestsCount > 1 ? 'ات' : '' }}</span>
                </div>
              </div>
              <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                 class="text-emerald-600 hover:text-emerald-700 text-sm font-medium transition-colors">
                عرض جميع المعلمين
                <i class="ri-arrow-left-s-line mr-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</body>
</html> 

