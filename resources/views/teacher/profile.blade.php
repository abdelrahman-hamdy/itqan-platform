<x-layouts.teacher title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - لوحة المعلم">
  <x-slot name="description">لوحة المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <div class="w-full">
      
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
              'items' => $pendingTrialRequests->take(3)->map(function($request) use ($academy) {
                // Determine status and appropriate link
                $statusText = match($request->status) {
                  'scheduled' => 'مجدولة',
                  'approved' => 'معتمدة',
                  'pending' => 'معلقة',
                  default => $request->status
                };

                // If session is scheduled, link to session page. Otherwise, link to calendar
                $link = $request->status === 'scheduled' && $request->trialSession
                  ? route('teacher.sessions.show', ['subdomain' => $academy->subdomain, 'sessionId' => $request->trialSession->id])
                  : route('teacher.schedule.dashboard', ['subdomain' => $academy->subdomain ?? 'itqan-academy']);

                return [
                  'title' => $request->student_name ?? ($request->student->name ?? 'طالب جديد'),
                  'description' => 'المستوى: ' . $request->current_level . ' - ' . $statusText,
                  'icon' => $request->status === 'scheduled' ? 'ri-video-line' : 'ri-user-add-line',
                  'iconBgColor' => $request->status === 'scheduled' ? 'bg-green-100' : 'bg-orange-100',
                  'iconColor' => $request->status === 'scheduled' ? 'text-green-600' : 'text-orange-600',
                  'status' => $request->status === 'scheduled' ? 'active' : 'pending',
                  'link' => $link
                ];
              })->toArray(),
              'footer' => [
                'text' => 'عرض جميع الطلبات',
                'link' => route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-user-add-line', 'value' => $pendingTrialRequests->where('status', 'pending')->count() . ' طلب معلق'],
                ['icon' => 'ri-check-line', 'value' => $pendingTrialRequests->where('status', 'scheduled')->count() . ' طلب مجدول']
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
                  'status' => $subscription->status === 'active' ? 'active' : ($subscription->status === 'pending' ? 'pending' : 'completed'),
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
                ['icon' => 'ri-calendar-line', 'value' => $privateLessons->where('status', 'active')->count() . ' درس نشط']
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
                    'status' => $course->is_approved ? 'active' : 'pending',
                    'link' => route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id])
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
                    'status' => $course->is_approved ? 'active' : 'pending',
                    'link' => route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id])
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
  </div>
</x-layouts.teacher>


