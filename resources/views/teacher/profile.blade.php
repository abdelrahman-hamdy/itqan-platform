<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - لوحة المعلم</title>
  <meta name="description" content="لوحة التحكم للمعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <!-- Alpine.js for interactive components -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
  <x-navigation.app-navigation role="teacher" />

  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="pt-20 min-h-screen transition-all duration-300 mr-0 md:mr-80" id="main-content">
    <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">

      <!-- Welcome Section -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          مرحباً، {{ $teacherProfile->first_name ?? auth()->user()->name }}! 👨‍🏫
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          إدارة جلساتك وطلابك ومتابعة أرباحك من خلال لوحة التحكم المخصصة للمعلمين
        </p>
      </div>

      <!-- Quick Stats -->
      @include('components.stats.teacher-stats', ['stats' => $stats])

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 lg:gap-8">

        @if($teacherType === 'quran')
          <!-- Quran Teacher Content -->

          <!-- Assigned Group Circles -->
          <div id="group-quran-circles">
            @include('components.cards.learning-section-card', [
              'title' => 'حلقات القرآن الجماعية',
              'subtitle' => 'إدارة حلقات القرآن الجماعية والطلاب المسجلين',
              'icon' => 'ri-group-line',
              'iconBgColor' => 'bg-green-500',
              'hideDots' => true,
              'items' => $assignedCircles->take(3)->map(function($circle) {
                return [
                  'title' => $circle->name,
                  'description' => $circle->students->count() . ' طالب مسجل' .
                                   ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                  'icon' => 'ri-group-line',
                  'iconBgColor' => 'bg-green-100',
                  'iconColor' => 'text-green-600',
                  'status' => 'active',
                  'link' => route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id])
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع الحلقات',
                'link' => route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-group-line', 'value' => $assignedCircles->count() . ' دائرة نشطة'],
                ['icon' => 'ri-user-line', 'value' => $assignedCircles->sum(function($circle) { return $circle->students->count(); }) . ' طالب']
              ],
              'emptyTitle' => 'لم يتم تعيين حلقات قرآن بعد',
              'emptyDescription' => 'سيقوم المشرف بتعيين الحلقات الجماعية لك',
              'emptyActionText' => 'تواصل مع المشرف'
            ])
          </div>

          <!-- Individual Quran Sessions (Private) -->
          <div id="individual-quran-sessions">
            @include('components.cards.learning-section-card', [
              'title' => 'الجلسات الفردية',
              'subtitle' => 'إدارة الاشتراكات الفردية والجلسات الخاصة',
              'icon' => 'ri-user-star-line',
              'iconBgColor' => 'bg-purple-500',
              'hideDots' => true,
              'items' => $activeSubscriptions->take(3)->map(function($subscription) {
                // Skip subscriptions without individual circles
                if (!$subscription->individualCircle) {
                  return null;
                }

                return [
                  'title' => $subscription->student->name ?? 'طالب',
                  'description' => 'باقة ' . ($subscription->package ? $subscription->package->getDisplayName() : 'مخصصة') .
                                   ' - متبقي ' . ($subscription->remaining_sessions ?? 0) . ' جلسة',
                  'icon' => 'ri-user-star-line',
                  'iconBgColor' => 'bg-purple-100',
                  'iconColor' => 'text-purple-600',
                  'progress' => $subscription->progress_percentage ?? 0,
                  'status' => (is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'active' : 'pending',
                  'link' => route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id])
                ];
              })->filter()->toArray(),
              'footer' => [
                'text' => 'عرض جميع الاشتراكات',
                'link' => route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-user-star-line', 'value' => $activeSubscriptions->count() . ' اشتراك نشط'],
                ['icon' => 'ri-calendar-line', 'value' => $activeSubscriptions->sum('remaining_sessions') . ' جلسة متبقية']
              ],
              'emptyTitle' => 'لا توجد اشتراكات فردية نشطة',
              'emptyDescription' => 'ستظهر الاشتراكات الفردية الجديدة هنا',
              'emptyActionText' => 'مراجعة طلبات التجريب'
            ])
          </div>

        @else
          <!-- Academic Teacher Content -->

          <!-- Private Academic Lessons -->
          <div id="academic-private-sessions">
            @include('components.cards.learning-section-card', [
              'title' => 'الدروس الخاصة',
              'subtitle' => 'الجلسات الفردية والدروس الخاصة مع الطلاب',
              'icon' => 'ri-user-3-line',
              'iconBgColor' => 'bg-orange-500',
              'hideDots' => true,
              'items' => $privateLessons->take(3)->map(function($subscription) {
                return [
                  'title' => $subscription->student->name ?? 'طالب',
                  'description' => ($subscription->subject->name ?? $subscription->subject_name ?? 'مادة') . ' - ' .
                                   ($subscription->gradeLevel->name ?? $subscription->grade_level_name ?? 'مستوى') .
                                   ' - ' . $subscription->sessions_per_week . ' جلسة/أسبوع',
                  'icon' => 'ri-user-3-line',
                  'iconBgColor' => 'bg-orange-100',
                  'iconColor' => 'text-orange-600',
                  'status' => (is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'active' : ((is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::PENDING->value ? 'pending' : 'completed'),
                  'progress' => $subscription->completion_rate ?? 0,
                  'link' => route('teacher.academic.lessons.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'lesson' => $subscription->id])
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع الدروس الخاصة',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-user-3-line', 'value' => $privateLessons->count() . ' درس خاص'],
                ['icon' => 'ri-calendar-line', 'value' => $privateLessons->filter(fn($l) => (is_object($l->status) ? $l->status->value : $l->status) === \App\Enums\SubscriptionStatus::ACTIVE->value)->count() . ' درس نشط']
              ],
              'emptyTitle' => 'لا توجد دروس خاصة',
              'emptyDescription' => 'ستظهر الدروس الخاصة مع الطلاب هنا عند حجزها',
              'emptyActionText' => 'إعداد الدروس الخاصة'
            ])
          </div>

          <!-- Interactive Courses -->
          <div id="interactive-courses">
            @include('components.cards.learning-section-card', [
              'title' => 'الدورات التفاعلية',
              'subtitle' => 'جميع الدورات التفاعلية التي تديرها سواء أنشأتها أو كُلفت بها',
              'icon' => 'ri-book-open-line',
              'iconBgColor' => 'bg-blue-500',
              'hideDots' => true,
              'items' => collect()
                ->merge($createdInteractiveCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة من إنشائك - ' . $course->enrollments->count() . ' طالب مسجل' .
                                     ($course->schedule_days ? ' - ' . $course->schedule_days : ''),
                    'icon' => 'ri-book-open-line',
                    'iconBgColor' => 'bg-blue-100',
                    'iconColor' => 'text-blue-600',
                    'status' => $course->status,
                    'link' => route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'courseId' => $course->id])
                  ];
                }))
                ->merge($assignedInteractiveCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة مكلف بها - ' . $course->enrollments->count() . ' طالب مسجل' .
                                     ($course->schedule_days ? ' - ' . $course->schedule_days : ''),
                    'icon' => 'ri-graduation-cap-line',
                    'iconBgColor' => 'bg-blue-100',
                    'iconColor' => 'text-blue-600',
                    'status' => $course->status,
                    'link' => route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'courseId' => $course->id])
                  ];
                }))
                ->toArray(),
              'footer' => [
                'text' => 'عرض جميع الدورات التفاعلية',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-book-open-line', 'value' => ($createdInteractiveCourses->count() + $assignedInteractiveCourses->count()) . ' دورة تفاعلية'],
                ['icon' => 'ri-user-line', 'value' => ($createdInteractiveCourses->sum(fn($c) => $c->enrollments->count()) + $assignedInteractiveCourses->sum(fn($c) => $c->enrollments->count())) . ' طالب مسجل']
              ],
              'emptyTitle' => 'لا توجد دورات تفاعلية',
              'emptyDescription' => 'ستظهر الدورات التفاعلية التي تديرها هنا عند تكليفك بها',
              'emptyActionText' => 'التواصل مع الإدارة'
            ])
          </div>
        @endif

      </div>

      @if($teacherType === 'quran')
        <!-- Trial Requests Section - Full Width -->
        <div class="mt-6 md:mt-8" id="trial-requests">
          @include('components.cards.learning-section-card', [
            'title' => 'الجلسات التجريبيه',
            'subtitle' => 'طلبات الجلسات التجريبية المعلقة والمجدولة',
            'icon' => 'ri-user-add-line',
            'iconBgColor' => 'bg-amber-500',
            'hideDots' => true,
            'items' => $pendingTrialRequests->map(function($request) {
              $statusColors = [
                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'label' => 'معلق'],
                'approved' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'موافق عليه'],
                'scheduled' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'مجدول'],
              ];
              $status = $statusColors[$request->status] ?? $statusColors['pending'];

              return [
                'title' => $request->student->name ?? 'طالب جديد',
                'description' => 'طلب تجريبي - ' . $status['label'] .
                                 ($request->preferred_time ? ' - الوقت المفضل: ' . $request->preferred_time : '') .
                                 ($request->trialSession?->scheduled_at ? ' - موعد: ' . $request->trialSession->scheduled_at->format('Y-m-d H:i') : ''),
                'icon' => 'ri-user-add-line',
                'iconBgColor' => $status['bg'],
                'iconColor' => $status['text'],
                'status' => $request->status === 'scheduled' ? 'active' : ($request->status === 'approved' ? 'pending' : 'warning'),
                'link' => '#'
              ];
            })->toArray(),
            'footer' => [
              'text' => 'عرض جميع طلبات التجريب',
              'link' => '#'
            ],
            'stats' => [
              ['icon' => 'ri-time-line', 'value' => $pendingTrialRequests->where('status', 'pending')->count() . ' طلب معلق'],
              ['icon' => 'ri-calendar-check-line', 'value' => $pendingTrialRequests->where('status', 'scheduled')->count() . ' جلسة مجدولة']
            ],
            'emptyTitle' => 'لا توجد طلبات تجريب حالياً',
            'emptyDescription' => 'ستظهر طلبات الجلسات التجريبية الجديدة هنا عند تقديمها',
            'emptyActionText' => ''
          ])
        </div>
      @endif
    </div>
  </main>

</body>
</html>
