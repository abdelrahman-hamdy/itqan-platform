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
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
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
            'title' => 'دوائر القرآن الكريم',
            'subtitle' => 'انضم إلى دوائر القرآن وشارك في حفظ وتلاوة القرآن الكريم',
            'icon' => 'ri-book-mark-line',
            'iconBgColor' => 'bg-green-500',
            'badge' => $quranCircles->count() > 0 ? 'نشط' : 'متاح',
            'badgeColor' => $quranCircles->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
            'items' => $quranCircles->take(3)->map(function($circle) {
              return [
                'title' => $circle->name,
                'description' => 'مع ' . $circle->teacher->full_name . 
                                 ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                'icon' => 'ri-group-line',
                'iconBgColor' => 'bg-green-100',
                'status' => 'active'
              ];
            })->toArray(),
            'footer' => [
              'text' => 'عرض جميع الدوائر',
              'link' => route('student.quran', ['subdomain' => auth()->user()->academy->subdomain])
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
            'title' => 'الدروس الخاصة بالقرآن',
            'subtitle' => 'دروس فردية مع معلمي القرآن المؤهلين',
            'icon' => 'ri-user-star-line',
            'iconBgColor' => 'bg-purple-500',
            'badge' => $quranPrivateSessions->where('subscription_status', 'active')->count() > 0 ? 'نشط' : 'متاح',
            'badgeColor' => $quranPrivateSessions->where('subscription_status', 'active')->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800',
            'items' => $quranPrivateSessions->take(3)->map(function($subscription) {
              $nextSession = $subscription->sessions->where('scheduled_at', '>', now())->first();
              return [
                'title' => $subscription->package->getDisplayName() ?? 'اشتراك مخصص',
                'description' => 'مع ' . $subscription->quranTeacher->full_name . 
                                 ($nextSession ? ' - ' . $nextSession->scheduled_at->format('l، d F H:i') : ''),
                'icon' => $subscription->subscription_status === 'active' ? 'ri-calendar-check-line' : 'ri-time-line',
                'iconBgColor' => $subscription->subscription_status === 'active' ? 'bg-green-100' : 'bg-yellow-100',
                'progress' => $subscription->progress_percentage,
                'status' => $subscription->subscription_status
              ];
            })->toArray(),
            'footer' => [
              'text' => 'عرض جميع الاشتراكات',
              'link' => route('student.quran', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-user-star-line', 'value' => $stats['activeQuranSubscriptions'] . ' اشتراك نشط'],
              ['icon' => 'ri-calendar-line', 'value' => $quranTrialRequests->where('status', 'scheduled')->count() . ' جلسة تجريبية']
            ]
          ])
        </div>

        <!-- Interactive Courses -->
        <div id="interactive-courses">
          @include('components.cards.learning-section-card', [
            'title' => 'الكورسات التفاعلية',
            'subtitle' => 'دورات أكاديمية تفاعلية في مختلف المواد الدراسية',
            'icon' => 'ri-book-open-line',
            'iconBgColor' => 'bg-blue-500',
            'badge' => '3 نشط',
            'badgeColor' => 'bg-blue-100 text-blue-800',
            'items' => [
              [
                'title' => 'الرياضيات للصف الثالث',
                'description' => 'مع الأستاذة ليلى محمد - 15 درس مكتمل من 20',
                'icon' => 'ri-calculator-line',
                'iconBgColor' => 'bg-blue-100',
                'progress' => 75,
                'status' => 'active'
              ],
              [
                'title' => 'اللغة العربية - النحو',
                'description' => 'مع الأستاذ خالد أحمد - 8 درس مكتمل من 12',
                'icon' => 'ri-file-text-line',
                'iconBgColor' => 'bg-green-100',
                'progress' => 67,
                'status' => 'active'
              ],
              [
                'title' => 'العلوم - الفيزياء',
                'description' => 'مع الأستاذة نورا سعيد - 5 درس مكتمل من 15',
                'icon' => 'ri-flask-line',
                'iconBgColor' => 'bg-purple-100',
                'progress' => 33,
                'status' => 'active'
              ]
            ],
            'footer' => [
              'text' => 'عرض جميع الكورسات',
              'link' => '#'
            ],
            'stats' => [
              ['icon' => 'ri-book-line', 'value' => '3 كورسات نشطة'],
              ['icon' => 'ri-check-line', 'value' => '28 درس مكتمل']
            ]
          ])
        </div>

        <!-- Recorded Courses -->
        <div id="recorded-courses">
          @php
            // Debug: Check if recordedCourses exists and what data it contains
            $debugRecordedCourses = isset($recordedCourses) ? $recordedCourses : collect();
            $debugCount = $debugRecordedCourses->count();
          @endphp
          
          @if($debugCount === 0)
            <!-- Show static content when no enrolled courses -->
            @include('components.cards.learning-section-card', [
              'title' => 'الكورسات المسجلة',
              'subtitle' => 'دورات مسجلة يمكنك مشاهدتها في أي وقت',
              'icon' => 'ri-video-line',
              'iconBgColor' => 'bg-red-500',
              'badge' => 'متاح',
              'badgeColor' => 'bg-red-100 text-red-800',
              'items' => [
                [
                  'title' => 'أساسيات البرمجة للأطفال',
                  'description' => 'دورة شاملة في البرمجة - متاحة للتسجيل',
                  'icon' => 'ri-code-line',
                  'iconBgColor' => 'bg-red-100',
                  'progress' => 0,
                  'status' => 'available'
                ],
                [
                  'title' => 'تعلم اللغة الإنجليزية',
                  'description' => 'دورة تفاعلية في اللغة الإنجليزية - متاحة للتسجيل',
                  'icon' => 'ri-translate-2',
                  'iconBgColor' => 'bg-blue-100',
                  'progress' => 0,
                  'status' => 'available'
                ]
              ],
              'footer' => [
                'text' => 'استكشاف المزيد',
                'link' => route('student.recorded-courses', ['subdomain' => auth()->user()->academy->subdomain])
              ],
              'stats' => [
                ['icon' => 'ri-video-line', 'value' => 'لا توجد كورسات مسجلة'],
                ['icon' => 'ri-time-line', 'value' => '0 ساعات مشاهدة']
              ]
            ])
          @else
            <!-- Show dynamic content when enrolled courses exist -->
            @include('components.cards.learning-section-card', [
              'title' => 'الكورسات المسجلة',
              'subtitle' => 'دورات مسجلة يمكنك مشاهدتها في أي وقت',
              'icon' => 'ri-video-line',
              'iconBgColor' => 'bg-red-500',
              'badge' => 'مسجل',
              'badgeColor' => 'bg-green-100 text-green-800',
              'items' => $debugRecordedCourses->take(3)->map(function($course) {
                $enrollment = $course->enrollments->first();
                return [
                  'title' => $course->title ?? 'كورس مسجل',
                  'description' => 'كورس مسجل' . 
                                   ($enrollment ? ' - تقدم: ' . ($enrollment->progress_percentage ?? 0) . '%' : ''),
                  'icon' => 'ri-video-line',
                  'iconBgColor' => 'bg-red-100',
                  'progress' => $enrollment ? ($enrollment->progress_percentage ?? 0) : 0,
                  'status' => $enrollment ? ($enrollment->status ?? 'available') : 'available'
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع كورساتي',
                'link' => route('student.recorded-courses', ['subdomain' => auth()->user()->academy->subdomain])
              ],
              'stats' => [
                ['icon' => 'ri-video-line', 'value' => $debugCount . ' كورس مسجل'],
                ['icon' => 'ri-time-line', 'value' => '0 ساعات مشاهدة']
              ]
            ])
          @endif
        </div>

      </div>

      <!-- Recent Activity Section -->
      <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">النشاط الأخير</h2>
          <a href="#" class="text-primary hover:text-secondary text-sm font-medium transition-colors">
            عرض الكل
            <i class="ri-arrow-left-s-line mr-1"></i>
          </a>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6">
            <div class="space-y-4">
              <div class="flex items-start space-x-4 space-x-reverse">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <i class="ri-check-line text-green-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">أكملت درس الرياضيات - الجمع والطرح</p>
                  <p class="text-xs text-gray-500">منذ ساعتين</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 space-x-reverse">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                  <i class="ri-video-line text-blue-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">شاهدت درس البرمجة - المتغيرات</p>
                  <p class="text-xs text-gray-500">منذ 4 ساعات</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 space-x-reverse">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                  <i class="ri-book-mark-line text-purple-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">حفظت صفحة من سورة البقرة</p>
                  <p class="text-xs text-gray-500">منذ 6 ساعات</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 space-x-reverse">
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                  <i class="ri-medal-line text-yellow-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">حصلت على شارة "المتعلم المثابر"</p>
                  <p class="text-xs text-gray-500">منذ يوم</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Sessions -->
      <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">الجلسات القادمة</h2>
          <a href="#" class="text-primary hover:text-secondary text-sm font-medium transition-colors">
            عرض الجدول الكامل
            <i class="ri-arrow-left-s-line mr-1"></i>
          </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="ri-book-mark-line text-green-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">دائرة الحفظ</h3>
                <p class="text-sm text-gray-500">مع الأستاذ أحمد محمد</p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>غداً - الأحد</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>4:00 مساءً - 5:30 مساءً</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-map-pin-line ml-2"></i>
                <span>الغرفة الافتراضية</span>
              </div>
            </div>
            <button class="w-full mt-4 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
              انضم للجلسة
            </button>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="ri-calculator-line text-blue-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">درس الرياضيات</h3>
                <p class="text-sm text-gray-500">مع الأستاذة ليلى محمد</p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>الثلاثاء</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>3:00 مساءً - 4:00 مساءً</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-map-pin-line ml-2"></i>
                <span>الغرفة الافتراضية</span>
              </div>
            </div>
            <button class="w-full mt-4 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
              انضم للجلسة
            </button>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="ri-user-star-line text-purple-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">درس خاص - القرآن</h3>
                <p class="text-sm text-gray-500">مع الأستاذ محمد عبدالله</p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>الخميس</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>6:00 مساءً - 7:00 مساءً</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-map-pin-line ml-2"></i>
                <span>الغرفة الافتراضية</span>
              </div>
            </div>
            <button class="w-full mt-4 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
              انضم للجلسة
            </button>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>

</body>
</html> 