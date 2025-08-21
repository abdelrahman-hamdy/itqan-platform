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
  @include('components.navigation.teacher-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      
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
                  'description' => 'باقة ' . ($subscription->package->getDisplayName() ?? 'مخصصة') . 
                                   ' - متبقي ' . ($subscription->remaining_sessions ?? 0) . ' جلسة',
                  'icon' => 'ri-user-star-line',
                  'iconBgColor' => 'bg-purple-100',
                  'iconColor' => 'text-purple-600',
                  'progress' => $subscription->progress_percentage ?? 0,
                  'status' => $subscription->subscription_status === 'active' ? 'active' : 'pending',
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

          <!-- Trial Requests for Quran Teachers -->
          <div id="trial-requests">
            @include('components.cards.learning-section-card', [
              'title' => 'طلبات الجلسات التجريبية',
              'subtitle' => 'مراجعة والموافقة على طلبات الجلسات التجريبية الجديدة',
              'icon' => 'ri-user-add-line',
              'iconBgColor' => 'bg-orange-500',
              'hideDots' => true,
              'items' => $pendingTrialRequests->take(3)->map(function($request) {
                return [
                  'title' => $request->student->name ?? 'طالب جديد',
                  'description' => 'المستوى المطلوب: ' . $request->current_level . 
                                   ' - ' . $request->created_at->diffForHumans(),
                  'icon' => 'ri-user-add-line',
                  'iconBgColor' => 'bg-orange-100',
                  'iconColor' => 'text-orange-600',
                  'status' => $request->status === 'pending' ? 'pending' : 'active',
                  'link' => route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع الطلبات',
                'link' => route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-user-add-line', 'value' => $pendingTrialRequests->count() . ' طلب معلق'],
                ['icon' => 'ri-check-line', 'value' => $pendingTrialRequests->where('status', 'approved')->count() . ' طلب معتمد']
              ],
              'emptyTitle' => 'لا توجد طلبات جلسات تجريبية',
              'emptyDescription' => 'ستظهر الطلبات الجديدة هنا عند تقديمها',
              'emptyActionText' => 'تحديث الإعدادات'
            ])
          </div>

          <!-- Recent Sessions for Quran Teachers -->
          <div id="recent-sessions">
            @include('components.cards.learning-section-card', [
              'title' => 'الجلسات الأخيرة',
              'subtitle' => 'مراجعة الجلسات المكتملة والقادمة',
              'icon' => 'ri-time-line',
              'iconBgColor' => 'bg-blue-500',
              'hideDots' => true,
              'items' => $recentSessions->take(3)->map(function($session) {
                return [
                  'title' => $session->student->name ?? 'طالب',
                  'description' => ($session->scheduled_at ? $session->scheduled_at->format('d/m/Y H:i') : 'غير محدد') . 
                                   ' - ' . ($session->duration ?? 60) . ' دقيقة',
                  'icon' => 'ri-time-line',
                  'iconBgColor' => 'bg-blue-100',
                  'iconColor' => 'text-blue-600',
                  'status' => $session->status === App\Enums\SessionStatus::COMPLETED ? 'active' : 'pending',
                  'link' => '/teacher-panel/quran-sessions'
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع الجلسات',
                'link' => '/teacher-panel/quran-sessions'
              ],
              'stats' => [
                ['icon' => 'ri-time-line', 'value' => $recentSessions->count() . ' جلسة حديثة'],
                ['icon' => 'ri-check-line', 'value' => $recentSessions->where('status', 'completed')->count() . ' جلسة مكتملة']
              ],
              'emptyTitle' => 'لا توجد جلسات حديثة',
              'emptyDescription' => 'ستظهر الجلسات المجدولة والمكتملة هنا',
              'emptyActionText' => 'عرض التقويم'
            ])
          </div>

        @else
          <!-- Academic Teacher Content -->
          
          <!-- Created Courses -->
          <div id="created-courses">
            @include('components.cards.learning-section-card', [
              'title' => 'دوراتي التي أنشأتها',
              'subtitle' => 'إدارة الدورات التي قمت بإنشائها وتطويرها',
              'icon' => 'ri-book-line',
              'iconBgColor' => 'bg-blue-500',
              'hideDots' => true,
              'items' => collect()
                ->merge($createdInteractiveCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة تفاعلية - ' . $course->enrollments->count() . ' طالب مسجل',
                    'icon' => 'ri-book-open-line',
                    'iconBgColor' => 'bg-blue-100',
                    'iconColor' => 'text-blue-600',
                    'status' => $course->is_approved ? 'active' : 'pending',
                    'link' => '#'
                  ];
                }))
                ->merge($createdRecordedCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة مسجلة - ' . $course->enrollments->count() . ' طالب مسجل',
                    'icon' => 'ri-video-line',
                    'iconBgColor' => 'bg-purple-100',
                    'iconColor' => 'text-purple-600',
                    'status' => $course->is_approved ? 'active' : 'pending',
                    'link' => '#'
                  ];
                }))
                ->toArray(),
              'footer' => [
                'text' => 'عرض جميع دوراتي',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-book-line', 'value' => ($createdInteractiveCourses->count() + $createdRecordedCourses->count()) . ' دورة منشأة'],
                ['icon' => 'ri-user-line', 'value' => ($createdInteractiveCourses->sum(fn($c) => $c->enrollments->count()) + $createdRecordedCourses->sum(fn($c) => $c->enrollments->count())) . ' طالب مسجل']
              ],
              'emptyTitle' => 'لم تقم بإنشاء دورات بعد',
              'emptyDescription' => 'ابدأ بإنشاء دورتك الأولى وشاركها مع الطلاب',
              'emptyActionText' => 'إنشاء دورة جديدة'
            ])
          </div>

          <!-- Assigned Courses -->
          <div id="assigned-courses">
            @include('components.cards.learning-section-card', [
              'title' => 'الدورات المكلف بإدارتها',
              'subtitle' => 'الدورات التي تم تكليفك بإدارتها من قبل الإدارة',
              'icon' => 'ri-graduation-cap-line',
              'iconBgColor' => 'bg-green-500',
              'hideDots' => true,
              'items' => collect()
                ->merge($assignedInteractiveCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة تفاعلية - مكلف من الإدارة',
                    'icon' => 'ri-graduation-cap-line',
                    'iconBgColor' => 'bg-green-100',
                    'iconColor' => 'text-green-600',
                    'status' => 'active',
                    'link' => '#'
                  ];
                }))
                ->merge($assignedRecordedCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => 'دورة مسجلة - مكلف من الإدارة',
                    'icon' => 'ri-video-line',
                    'iconBgColor' => 'bg-green-100',
                    'iconColor' => 'text-green-600',
                    'status' => 'active',
                    'link' => '#'
                  ];
                }))
                ->toArray(),
              'footer' => [
                'text' => 'عرض جميع الدورات المكلفة',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-graduation-cap-line', 'value' => ($assignedInteractiveCourses->count() + $assignedRecordedCourses->count()) . ' دورة مكلفة'],
                ['icon' => 'ri-check-line', 'value' => $assignedInteractiveCourses->where('is_approved', true)->count() + $assignedRecordedCourses->where('is_approved', true)->count() . ' دورة نشطة']
              ],
              'emptyTitle' => 'لم يتم تكليفك بدورات بعد',
              'emptyDescription' => 'سيقوم المشرف بتكليفك بالدورات المناسبة لخبرتك',
              'emptyActionText' => 'تواصل مع المشرف'
            ])
          </div>

          <!-- Academic Private Sessions -->
          <div id="academic-private-sessions">
            @include('components.cards.learning-section-card', [
              'title' => 'الجلسات الخاصة الأكاديمية',
              'subtitle' => 'الجلسات الفردية مع الطلاب في المواد الأكاديمية',
              'icon' => 'ri-user-3-line',
              'iconBgColor' => 'bg-orange-500',
              'hideDots' => true,
              'items' => [],
              'footer' => [
                'text' => 'عرض جميع الجلسات الخاصة',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-user-3-line', 'value' => '0 جلسة نشطة'],
                ['icon' => 'ri-calendar-line', 'value' => '0 جلسة مجدولة']
              ],
              'emptyTitle' => 'لا توجد جلسات خاصة',
              'emptyDescription' => 'ستظهر الجلسات الخاصة مع الطلاب هنا عند حجزها',
              'emptyActionText' => 'إعداد الجلسات الخاصة'
            ])
          </div>

          <!-- Class Schedule -->
          <div id="class-schedule">
            @include('components.cards.learning-section-card', [
              'title' => 'جدول الحصص الأكاديمية',
              'subtitle' => 'الحصص الدراسية المجدولة والقادمة',
              'icon' => 'ri-calendar-2-line',
              'iconBgColor' => 'bg-indigo-500',
              'hideDots' => true,
              'items' => [],
              'footer' => [
                'text' => 'عرض الجدول الكامل',
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-calendar-line', 'value' => '0 حصة هذا الأسبوع'],
                ['icon' => 'ri-time-line', 'value' => '0 ساعة تدريس']
              ],
              'emptyTitle' => 'لا توجد حصص مجدولة',
              'emptyDescription' => 'ستظهر الحصص الدراسية المجدولة هنا',
              'emptyActionText' => 'عرض التقويم'
            ])
          </div>
        @endif





      </div>
    </div>
  </main>
</body>
</html>


