<x-layouts.student :title="__('student.profile.page_title')">

  <div style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect x='25' y='25' width='50' height='50' transform='rotate(45 50 50)' fill='none' stroke='%239ca3b8' stroke-width='0.8' stroke-opacity='0.12'/%3E%3Crect x='25' y='25' width='50' height='50' fill='none' stroke='%239ca3b8' stroke-width='0.8' stroke-opacity='0.12'/%3E%3Ccircle cx='50' cy='50' r='12' fill='none' stroke='%239ca3b8' stroke-width='0.5' stroke-opacity='0.12'/%3E%3C/svg%3E&quot;); background-size: 100px 100px;">
    <div>

      <!-- Welcome Section -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
          {{ __('student.profile.welcome') }} {{ auth()->user()->studentProfile->first_name ?? auth()->user()->name }}! 👋
        </h1>
        <p class="text-sm sm:text-base text-gray-600">
          {{ __('student.profile.welcome_description') }}
        </p>
      </div>

      <!-- Quick Stats -->
      @include('components.stats.quick-stats')

      <!-- Learning Sections (Full Width, Stacked) -->
      <div class="space-y-6">

        <!-- 1. Individual Quran Circles (Quran Private Sessions) -->
        <div id="quran-private">
          @include('components.cards.learning-section-card', [
            'title' => __('student.profile.individual_circles_title'),
            'subtitle' => __('student.profile.individual_circles_subtitle'),
            'icon' => 'ri-user-star-line',
            'iconBgColor' => 'bg-yellow-500',
            'primaryColor' => 'yellow',
            'hideDots' => true,
            'collapsible' => true,
            'startCollapsed' => $quranPrivateSessions->isEmpty(),
            'items' => $quranPrivateSessions->map(function($subscription) {
              $nextSession = $subscription->sessions->where('scheduled_at', '>', now())->first();

              // Compute descriptive status based on subscription status + payment status
              $status = $subscription->status;
              $paymentStatus = $subscription->payment_status ?? null;

              // Override status display for better UX
              if ($status === \App\Enums\SessionSubscriptionStatus::PENDING) {
                if ($paymentStatus === \App\Enums\SubscriptionPaymentStatus::PENDING ||
                    $paymentStatus === \App\Enums\SubscriptionPaymentStatus::UNPAID) {
                  // Show custom status for pending payment
                  $statusDisplay = (object)[
                    'label' => __('components.circle.header.awaiting_payment'),
                    'badgeClasses' => 'bg-yellow-100 text-yellow-800'
                  ];
                } elseif ($paymentStatus === \App\Enums\SubscriptionPaymentStatus::FAILED) {
                  $statusDisplay = (object)[
                    'label' => __('components.circle.header.payment_failed'),
                    'badgeClasses' => 'bg-red-100 text-red-800'
                  ];
                } else {
                  $statusDisplay = $status; // Use enum default
                }
              } else {
                $statusDisplay = $status; // Use enum for other statuses
              }

              // Calculate actual progress from subscription data
              $progress = 0;
              if ($subscription->total_sessions > 0) {
                $progress = round(($subscription->sessions_used / $subscription->total_sessions) * 100);
              }

              return [
                'title' => $subscription->individualCircle?->name ?? __('student.profile.custom_subscription'),
                'description' => __('student.profile.with_teacher') . ' ' . ($subscription->quranTeacher->full_name ?? __('student.profile.quran_teacher_default')) .
                                 ($nextSession ? ' - ' . formatDateTimeArabic($nextSession->scheduled_at) : ''),
                'icon' => 'ri-user-star-line',
                'iconBgColor' => 'bg-yellow-100',
                'iconColor' => 'text-yellow-600',
                'progress' => $progress,
                'status' => $statusDisplay,
                'link' => $subscription->individualCircle ?
                    route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circle' => $subscription->individualCircle->id]) :
                    '#'
              ];
            })->toArray(),
            'emptyTitle' => __('student.profile.no_quran_sessions_title'),
            'emptyDescription' => __('student.profile.no_quran_sessions_description'),
            'emptyActionText' => __('student.profile.browse_quran_teachers'),
            'emptyActionLink' => route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => __('student.profile.view_all_quran_teachers'),
              'link' => route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-user-star-line', 'value' => $stats['activeQuranSubscriptions'] . ' ' . __('student.profile.active_subscription'), 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- 2. Quran Trial Requests -->
        @php
          $trialRequestsCollection = isset($quranTrialRequests) ? $quranTrialRequests : collect();
          $trialRequestsCount = $trialRequestsCollection->count();
        @endphp
        <div id="trial-requests" x-data="{ open: {{ $trialRequestsCount > 0 ? 'true' : 'false' }} }">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <!-- Clickable Header -->
            <div class="p-6" :class="open ? 'border-b border-gray-100' : ''" @click="open = !open" role="button" style="cursor: pointer;">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-amber-500">
                    <i class="ri-gift-line text-xl text-white"></i>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('student.profile.trial_requests_title') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('student.profile.trial_requests_description') }}</p>
                  </div>
                </div>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200 text-lg" :class="{ '-rotate-90': !open }"></i>
              </div>
            </div>

            <!-- Collapsible Content -->
            <div x-show="open"
                 {!! $trialRequestsCount === 0 ? 'style="display:none"' : '' !!}
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
              <!-- Card Content -->
              <div class="p-6">
                @if($trialRequestsCount > 0)
                  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($trialRequestsCollection->take(6) as $trialRequest)
                      @php
                        $teacherName = __('student.profile.quran_teacher_default');
                        if($trialRequest->teacher) {
                          $teacherName = $trialRequest->teacher->full_name ??
                             ($trialRequest->teacher->first_name && $trialRequest->teacher->last_name ?
                              $trialRequest->teacher->first_name . ' ' . $trialRequest->teacher->last_name : null) ??
                             $trialRequest->teacher->first_name ??
                             $trialRequest->teacher->user?->name ??
                             __('student.profile.quran_teacher_default');
                        }

                        $statusConfig = [
                          'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => __('student.profile.trial_status_pending')],
                          'approved' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => __('student.profile.trial_status_approved')],
                          'scheduled' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => __('student.profile.trial_status_scheduled')],
                          'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('student.profile.trial_status_completed')],
                          'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => __('student.profile.trial_status_cancelled')],
                          'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => __('student.profile.trial_status_rejected')],
                        ];
                        // Handle enum status - get the string value
                        $statusValue = $trialRequest->status instanceof \App\Enums\TrialRequestStatus
                            ? $trialRequest->status->value
                            : (string) $trialRequest->status;
                        $statusStyle = $statusConfig[$statusValue] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $statusValue];

                        $preferredTimeText = '';
                        if($trialRequest->preferred_time) {
                          $preferredTime = $trialRequest->preferred_time;
                          if ($preferredTime instanceof \Carbon\Carbon) {
                            \Carbon\Carbon::setLocale(app()->getLocale());
                            $preferredTimeText = $preferredTime->translatedFormat('l، d F Y');
                          } elseif (is_string($preferredTime) && preg_match('/^\d{4}-\d{2}-\d{2}/', $preferredTime)) {
                            try {
                              $parsedTime = \Carbon\Carbon::parse($preferredTime);
                              \Carbon\Carbon::setLocale(app()->getLocale());
                              $preferredTimeText = $parsedTime->translatedFormat('l، d F Y');
                            } catch (\Exception $e) {
                              $preferredTimeText = $preferredTime;
                            }
                          } else {
                            $translations = [
                              'morning' => __('student.profile.time_preferences_morning'),
                              'afternoon' => __('student.profile.time_preferences_afternoon'),
                              'evening' => __('student.profile.time_preferences_evening'),
                              'night' => __('student.profile.time_preferences_night')
                            ];
                            $preferredTimeText = $translations[strtolower($preferredTime)] ?? $preferredTime;
                          }
                        }
                      @endphp

                      <a href="{{ route('student.trial-requests.show', ['subdomain' => auth()->user()->academy->subdomain, 'trialRequest' => $trialRequest->id]) }}" class="block">
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                          <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-100 me-3">
                            <i class="ri-user-star-line text-sm text-emerald-600"></i>
                          </div>
                          <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 truncate">{{ $teacherName }}</h4>
                            <p class="text-sm text-gray-500 truncate">
                              @if($preferredTimeText)
                                {{ $preferredTimeText }}
                              @else
                                {{ __('student.profile.requested_at') }} {{ $trialRequest->created_at->diffForHumans() }}
                              @endif
                            </p>
                          </div>
                          <div class="flex items-center gap-2 ms-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusStyle['bg'] }} {{ $statusStyle['text'] }}">
                              {{ $statusStyle['label'] }}
                            </span>
                            <div class="text-emerald-600 hover:text-emerald-700 transition-colors">
                              <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
                            </div>
                          </div>
                        </div>
                      </a>
                    @endforeach
                  </div>
                @else
                  <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <i class="ri-calendar-todo-line text-2xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('student.profile.no_trial_requests_title') }}</h4>
                    <p class="text-gray-500 mb-4">{{ __('student.profile.no_trial_requests_description') }}</p>
                    <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors inline-block">
                      {{ __('student.profile.request_trial_session') }}
                    </a>
                  </div>
                @endif
              </div>

              <!-- Card Footer -->
              <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                      <i class="ri-calendar-todo-line"></i>
                      <span>{{ $trialRequestsCount }} {{ $trialRequestsCount > 1 ? __('student.profile.trial_requests_plural') : __('student.profile.trial_requests') }}</span>
                    </div>
                  </div>
                  <a href="{{ route('quran-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                     class="text-emerald-600 hover:text-emerald-700 text-sm font-medium transition-colors">
                    {{ __('student.profile.view_all_teachers') }}
                    <i class="ri-arrow-left-s-line me-1 {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }} inline-block"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Group Quran Circles -->
        <div id="quran-circles">
          @include('components.cards.learning-section-card', [
            'title' => __('student.profile.group_circles_title'),
            'subtitle' => __('student.profile.group_circles_subtitle'),
            'icon' => 'ri-group-line',
            'iconBgColor' => 'bg-green-500',
            'primaryColor' => 'green',
            'hideDots' => true,
            'collapsible' => true,
            'startCollapsed' => $quranCircles->isEmpty(),
            'items' => $quranCircles->take(3)->map(function($circle) {
              // Get student's actual enrollment status from pivot table
              $studentPivot = $circle->students->where('id', auth()->id())->first()?->pivot;
              $status = $studentPivot?->status ?? 'enrolled';

              return [
                'title' => $circle->name,
                'description' => __('student.profile.with_teacher') . ' ' . ($circle->quranTeacher->user->name ?? __('student.profile.quran_teacher_default')) .
                                 ($circle->schedule_days_text ? ' - ' . $circle->schedule_days_text : ''),
                'icon' => 'ri-group-line',
                'iconBgColor' => 'bg-green-100',
                'iconColor' => 'text-green-600',
                'status' => $status,
                'link' => route('student.circles.show', ['subdomain' => auth()->user()->academy->subdomain, 'circleId' => $circle->id])
              ];
            })->toArray(),
            'emptyTitle' => __('student.profile.no_circles_title'),
            'emptyDescription' => __('student.profile.no_circles_description'),
            'emptyActionText' => __('student.profile.browse_circles'),
            'emptyActionLink' => route('quran-circles.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => __('student.profile.view_all_circles'),
              'link' => route('quran-circles.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-group-line', 'value' => $stats['quranCirclesCount'] . ' ' . __('student.profile.active_circles'), 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- 4. Academic Private Sessions -->
        <div id="academic-private-sessions">
          @include('components.cards.learning-section-card', [
            'title' => __('student.profile.academic_private_title'),
            'subtitle' => __('student.profile.academic_private_subtitle'),
            'icon' => 'ri-user-3-line',
            'iconBgColor' => 'bg-violet-500',
            'primaryColor' => 'violet',
            'hideDots' => true,
            'collapsible' => true,
            'startCollapsed' => $academicPrivateSessions->isEmpty(),
            'items' => $academicPrivateSessions->count() > 0 ? $academicPrivateSessions->take(3)->map(function($subscription) {
              // Calculate actual progress from subscription data
              $progress = 0;
              if ($subscription->total_sessions > 0) {
                $progress = round(($subscription->sessions_used / $subscription->total_sessions) * 100);
              }

              return [
                'title' => $subscription->subject_name ?? __('student.profile.academic_lesson'),
                'description' => __('student.profile.with_teacher') . ' ' . ($subscription->academicTeacher->full_name ?? __('student.profile.academic_teacher_default')) .
                                 ' - ' . ($subscription->grade_level_name ?? __('student.profile.grade_level_default')) .
                                 ' - ' . number_format($subscription->monthly_amount) . ' ' . getCurrencySymbol(null, $subscription->academy) . ' ' . __('student.profile.monthly'),
                'icon' => 'ri-user-3-line',
                'iconBgColor' => 'bg-violet-100',
                'iconColor' => 'text-violet-600',
                'progress' => $progress,
                'status' => $subscription->status,
                'link' => route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain, 'subscriptionId' => $subscription->id])
              ];
            })->toArray() : [],
            'emptyTitle' => __('student.profile.no_private_lessons_title'),
            'emptyDescription' => __('student.profile.no_private_lessons_description'),
            'emptyActionText' => __('student.profile.browse_academic_teachers'),
            'emptyActionLink' => route('academic-teachers.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => __('student.profile.view_all_academic_teachers'),
              'link' => route('academic-teachers.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => $academicPrivateSessions->count() > 0 ? [
              ['icon' => 'ri-user-3-line', 'value' => $academicPrivateSessions->count() . ' ' . __('student.profile.active_subscription'), 'isActiveCount' => true]
            ] : []
          ])
        </div>

        <!-- 5. Interactive Courses -->
        <div id="interactive-courses">
          @php
            // Calculate progress directly from sessions
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

                // Use the enrollment status enum directly
                $status = $enrollment->enrollment_status ?? \App\Enums\EnrollmentStatus::ENROLLED;

                $interactiveCourseItems[] = [
                  'title' => $course->title,
                  'description' => __('student.profile.with_teacher') . ' ' . ($course->assignedTeacher->user->name ?? __('student.profile.teacher_default')) . ' - ' . $courseCompleted . ' ' . __('student.profile.sessions_completed') . ' ' . $courseTotal,
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
            'title' => __('student.profile.interactive_courses_title'),
            'subtitle' => __('student.profile.interactive_courses_subtitle'),
            'icon' => 'ri-book-open-line',
            'iconBgColor' => 'bg-blue-500',
            'primaryColor' => 'blue',
            'hideDots' => true,
            'collapsible' => true,
            'startCollapsed' => empty($interactiveCourseItems),
            'progressFullWidth' => true,
            'items' => $interactiveCourseItems,
            'emptyTitle' => __('student.profile.no_interactive_courses_title'),
            'emptyDescription' => __('student.profile.no_interactive_courses_description'),
            'emptyActionText' => __('student.profile.browse_interactive_courses'),
            'emptyActionLink' => route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain]),
            'footer' => [
              'text' => __('student.profile.view_all_courses'),
              'link' => route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain])
            ],
            'stats' => [
              ['icon' => 'ri-book-line', 'value' => count($interactiveCourses) . ' ' . (count($interactiveCourses) != 1 ? __('student.profile.active_courses') : __('student.profile.active_course')), 'isActiveCount' => true]
            ]
          ])
        </div>

        <!-- 6. Recorded Courses -->
        @php
          $recordedCoursesCollection = isset($recordedCourses) ? $recordedCourses : collect();
          $recordedCoursesCount = $recordedCoursesCollection->count();
        @endphp
        <div id="recorded-courses" x-data="{ open: {{ $recordedCoursesCount > 0 ? 'true' : 'false' }} }">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <!-- Clickable Header -->
            <div class="p-6" :class="open ? 'border-b border-gray-100' : ''" @click="open = !open" role="button" style="cursor: pointer;">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cyan-500">
                    <i class="ri-video-line text-xl text-white"></i>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('student.profile.recorded_courses_title') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('student.profile.recorded_courses_description') }}</p>
                  </div>
                </div>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200 text-lg" :class="{ '-rotate-90': !open }"></i>
              </div>
            </div>

            <!-- Collapsible Content -->
            <div x-show="open"
                 {!! $recordedCoursesCount === 0 ? 'style="display:none"' : '' !!}
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
              <!-- Card Content -->
              <div class="p-6">
                @if($recordedCoursesCount > 0)
                  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($recordedCoursesCollection->take(6) as $course)
                      <a href="{{ route('courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'id' => $course->id]) }}" class="block">
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                          <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-cyan-100 me-3">
                            <i class="ri-video-line text-sm text-cyan-600"></i>
                          </div>
                          <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 truncate">{{ $course->title }}</h4>
                            <p class="text-sm text-gray-500 truncate">
                              {{ $course->lessons_count ?? $course->lessons->count() ?? 0 }} {{ __('student.profile.lesson_label') }}
                              @if($course->instructor)
                                - {{ $course->instructor->name }}
                              @endif
                            </p>
                            @if($course->pivot && $course->pivot->progress)
                              <div class="mt-2">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                  <span>{{ __('student.profile.progress_label') }}</span>
                                  <span>{{ number_format($course->pivot->progress, 0) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                  <div class="bg-cyan-600 h-2 rounded-full transition-all duration-300"
                                       style="width: {{ number_format($course->pivot->progress, 0) }}%"></div>
                                </div>
                              </div>
                            @endif
                          </div>
                          <div class="flex items-center gap-2 ms-3">
                            @if($course->is_published)
                              <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('student.profile.available_badge') }}</span>
                            @endif
                            <div class="text-cyan-600 hover:text-cyan-700 transition-colors">
                              <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('student.profile.no_recorded_courses_title') }}</h4>
                    <p class="text-gray-500 mb-4">{{ __('student.profile.no_recorded_courses_description') }}</p>
                    <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}" class="bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-cyan-700 transition-colors inline-block">
                      {{ __('student.profile.explore_courses') }}
                    </a>
                  </div>
                @endif
              </div>

              <!-- Card Footer -->
              <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                      <i class="ri-video-line"></i>
                      <span>{{ $recordedCoursesCount }} {{ $recordedCoursesCount > 1 ? __('student.profile.recorded_courses_plural') : __('student.profile.recorded_courses') }}</span>
                    </div>
                  </div>
                  <a href="{{ route('courses.index', ['subdomain' => auth()->user()->academy->subdomain]) }}"
                     class="text-cyan-600 hover:text-cyan-700 text-sm font-medium transition-colors">
                    {{ __('student.profile.view_all_courses') }}
                    <i class="ri-arrow-left-s-line me-1 {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }} inline-block"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

</x-layouts.student>
