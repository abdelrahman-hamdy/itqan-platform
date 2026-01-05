<x-layouts.teacher
    :title="auth()->user()->academy->name ?? __('teacher.panel.academy_default') . ' - ' . __('teacher.panel.title')"
    :description="__('teacher.panel.description') . ' - ' . (auth()->user()->academy->name ?? __('teacher.panel.academy_default'))">

<div>
      <!-- Welcome Section -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          {{ __('teacher.profile.welcome', ['name' => $teacherProfile->first_name ?? auth()->user()->name]) }} 👨‍🏫
        </h1>
        <p class="text-sm md:text-base text-gray-600">
          {{ __('teacher.profile.dashboard_description') }}
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
              'title' => __('teacher.circles.group.title'),
              'subtitle' => __('teacher.circles.group.subtitle'),
              'icon' => 'ri-group-line',
              'iconBgColor' => 'bg-green-500',
              'hideDots' => true,
              'items' => $assignedCircles->take(3)->map(function($circle) {
                return [
                  'title' => $circle->name,
                  'description' => $circle->students->count() . ' ' . __('teacher.circles.group.registered_students') .
                                   ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                  'icon' => 'ri-group-line',
                  'iconBgColor' => 'bg-green-100',
                  'iconColor' => 'text-green-600',
                  'status' => 'active',
                  'link' => route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id])
                ];
              })->toArray(),
              'footer' => [
                'text' => __('teacher.circles.group.view_all_circles'),
                'link' => route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-group-line', 'value' => $assignedCircles->count() . ' ' . __('teacher.circles.group.active_circle')],
                ['icon' => 'ri-user-line', 'value' => $assignedCircles->sum(function($circle) { return $circle->students->count(); }) . ' ' . __('teacher.common.student')]
              ],
              'emptyTitle' => __('teacher.circles.group.empty_title'),
              'emptyDescription' => __('teacher.circles.group.empty_description'),
              'emptyActionText' => ''
            ])
          </div>

          <!-- Individual Quran Sessions (Private) -->
          <div id="individual-quran-sessions">
            @include('components.cards.learning-section-card', [
              'title' => __('teacher.circles.individual.title'),
              'subtitle' => __('teacher.circles.individual.subtitle'),
              'icon' => 'ri-user-star-line',
              'iconBgColor' => 'bg-purple-500',
              'hideDots' => true,
              'items' => $activeSubscriptions->take(3)->map(function($subscription) {
                // Skip subscriptions without individual circles
                if (!$subscription->individualCircle) {
                  return null;
                }

                return [
                  'title' => $subscription->student->name ?? __('teacher.circles.individual.student_label'),
                  'description' => __('teacher.circles.individual.package_label') . ' ' . ($subscription->package ? $subscription->package->getDisplayName() : __('teacher.circles.individual.package_custom')) .
                                   ' - ' . __('teacher.circles.individual.remaining_sessions', ['count' => $subscription->remaining_sessions ?? 0]),
                  'icon' => 'ri-user-star-line',
                  'iconBgColor' => 'bg-purple-100',
                  'iconColor' => 'text-purple-600',
                  'progress' => $subscription->progress_percentage ?? 0,
                  'status' => (is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'active' : 'pending',
                  'link' => route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id])
                ];
              })->filter()->toArray(),
              'footer' => [
                'text' => __('teacher.circles.individual.view_all_subscriptions'),
                'link' => route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
              ],
              'stats' => [
                ['icon' => 'ri-user-star-line', 'value' => $activeSubscriptions->count() . ' ' . __('teacher.circles.individual.active_subscription')],
                ['icon' => 'ri-calendar-line', 'value' => $activeSubscriptions->sum('remaining_sessions') . ' ' . __('teacher.circles.individual.remaining_total')]
              ],
              'emptyTitle' => __('teacher.circles.individual.empty_title'),
              'emptyDescription' => __('teacher.circles.individual.empty_description'),
              'emptyActionText' => ''
            ])
          </div>

        @else
          <!-- Academic Teacher Content -->

          <!-- Private Academic Lessons -->
          <div id="academic-private-sessions">
            @include('components.cards.learning-section-card', [
              'title' => __('teacher.sessions.academic.title'),
              'subtitle' => __('teacher.sessions.academic.subtitle'),
              'icon' => 'ri-user-3-line',
              'iconBgColor' => 'bg-orange-500',
              'hideDots' => true,
              'items' => $privateLessons->take(3)->map(function($subscription) {
                return [
                  'title' => $subscription->student->name ?? __('teacher.common.student'),
                  'description' => ($subscription->subject->name ?? $subscription->subject_name ?? __('teacher.sessions.academic.subject_label')) . ' - ' .
                                   ($subscription->gradeLevel ? $subscription->gradeLevel->getDisplayName() : ($subscription->grade_level_name ?? __('teacher.sessions.academic.level_label'))) .
                                   ' - ' . $subscription->sessions_per_week . ' ' . __('teacher.sessions.academic.per_week'),
                  'icon' => 'ri-user-3-line',
                  'iconBgColor' => 'bg-orange-100',
                  'iconColor' => 'text-orange-600',
                  'status' => (is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'active' : ((is_object($subscription->status) ? $subscription->status->value : $subscription->status) === \App\Enums\SubscriptionStatus::PENDING->value ? 'pending' : 'completed'),
                  'progress' => $subscription->completion_rate ?? 0,
                  'link' => route('teacher.academic.lessons.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'lesson' => $subscription->id])
                ];
              })->toArray(),
              'footer' => [
                'text' => __('teacher.sessions.academic.view_all_lessons'),
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-user-3-line', 'value' => $privateLessons->count() . ' ' . __('teacher.sessions.academic.private_lesson_count')],
                ['icon' => 'ri-calendar-line', 'value' => $privateLessons->filter(fn($l) => (is_object($l->status) ? $l->status->value : $l->status) === \App\Enums\SubscriptionStatus::ACTIVE->value)->count() . ' ' . __('teacher.sessions.academic.active_lesson_count')]
              ],
              'emptyTitle' => __('teacher.sessions.academic.empty_title'),
              'emptyDescription' => __('teacher.sessions.academic.empty_description'),
              'emptyActionText' => ''
            ])
          </div>

          <!-- Interactive Courses -->
          <div id="interactive-courses">
            @include('components.cards.learning-section-card', [
              'title' => __('teacher.sessions.interactive.title'),
              'subtitle' => __('teacher.sessions.interactive.subtitle'),
              'icon' => 'ri-book-open-line',
              'iconBgColor' => 'bg-blue-500',
              'hideDots' => true,
              'items' => collect()
                ->merge($createdInteractiveCourses->take(2)->map(function($course) {
                  return [
                    'title' => $course->title,
                    'description' => __('teacher.sessions.interactive.created_by_you') . ' - ' . $course->enrollments->count() . ' ' . __('teacher.sessions.interactive.students_enrolled') .
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
                    'description' => __('teacher.sessions.interactive.assigned_to_you') . ' - ' . $course->enrollments->count() . ' ' . __('teacher.sessions.interactive.students_enrolled') .
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
                'text' => __('teacher.sessions.interactive.view_all_courses'),
                'link' => '#'
              ],
              'stats' => [
                ['icon' => 'ri-book-open-line', 'value' => ($createdInteractiveCourses->count() + $assignedInteractiveCourses->count()) . ' ' . __('teacher.sessions.interactive.interactive_course_count')],
                ['icon' => 'ri-user-line', 'value' => ($createdInteractiveCourses->sum(fn($c) => $c->enrollments->count()) + $assignedInteractiveCourses->sum(fn($c) => $c->enrollments->count())) . ' ' . __('teacher.sessions.interactive.students_enrolled')]
              ],
              'emptyTitle' => __('teacher.sessions.interactive.empty_title'),
              'emptyDescription' => __('teacher.sessions.interactive.empty_description'),
              'emptyActionText' => ''
            ])
          </div>
        @endif

      </div>

      @if($teacherType === 'quran')
        <!-- Trial Requests Section - Full Width -->
        <div class="mt-6 md:mt-8" id="trial-requests">
          @include('components.cards.learning-section-card', [
            'title' => __('teacher.trial.title'),
            'subtitle' => __('teacher.trial.subtitle'),
            'icon' => 'ri-gift-line',
            'iconBgColor' => 'bg-amber-500',
            'hideDots' => true,
            'items' => $pendingTrialRequests->map(function($request) {
              $statusColors = [
                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'label' => __('teacher.trial.status.pending')],
                'approved' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => __('teacher.trial.status.approved')],
                'scheduled' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => __('teacher.trial.status.scheduled')],
              ];
              $statusKey = is_object($request->status) ? $request->status->value : $request->status;
              $status = $statusColors[$statusKey] ?? $statusColors['pending'];

              return [
                'title' => $request->student->name ?? __('teacher.trial.new_student'),
                'description' => __('teacher.trial.trial_request') . ' - ' . $status['label'] .
                                 ($request->preferred_time ? ' - ' . __('teacher.trial.preferred_time') . ': ' . $request->preferred_time : '') .
                                 ($request->trialSession?->scheduled_at ? ' - ' . __('teacher.trial.scheduled_at') . ': ' . $request->trialSession->scheduled_at->format('Y-m-d H:i') : ''),
                'icon' => 'ri-user-add-line',
                'iconBgColor' => $status['bg'],
                'iconColor' => $status['text'],
                'status' => $request->status === 'scheduled' ? 'active' : ($request->status === 'approved' ? 'pending' : 'warning'),
                'link' => route('teacher.trial-sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'trialRequest' => $request->id])
              ];
            })->toArray(),
            'footer' => [
              'text' => __('teacher.trial.view_all_requests'),
              'link' => route('teacher.trial-sessions.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
            ],
            'stats' => [
              ['icon' => 'ri-time-line', 'value' => $pendingTrialRequests->where('status', 'pending')->count() . ' ' . __('teacher.trial.pending_request')],
              ['icon' => 'ri-calendar-check-line', 'value' => $pendingTrialRequests->where('status', 'scheduled')->count() . ' ' . __('teacher.trial.scheduled_session')]
            ],
            'emptyTitle' => __('teacher.trial.empty_title'),
            'emptyDescription' => __('teacher.trial.empty_description'),
            'emptyActionText' => ''
          ])
        </div>
      @endif

</div>

</x-layouts.teacher>
