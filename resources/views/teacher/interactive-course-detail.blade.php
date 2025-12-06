<x-layouts.teacher
  title="إدارة الكورس - {{ $course->title }}"
  description="إدارة الكورس التفاعلي {{ $course->title }} - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">

  <!-- Breadcrumb -->
  <nav class="mb-6">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-500">
      <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-blue-600 transition-colors">الملف الشخصي</a></li>
      <li><i class="ri-arrow-left-s-line"></i></li>
      <li class="text-gray-900 font-medium">{{ $course->title }}</li>
    </ol>
  </nav>

  <!-- Success Messages -->
  @if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
      <div class="flex">
        <i class="ri-check-line text-green-500 mt-0.5 ml-2"></i>
        <div>{{ session('success') }}</div>
      </div>
    </div>
  @endif

  <!-- Error Messages -->
  @if (session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
      <div class="flex">
        <i class="ri-error-warning-line text-red-500 mt-0.5 ml-2"></i>
        <div>{{ session('error') }}</div>
      </div>
    </div>
  @endif

  @php
    $now = now();
    $isOngoing = $course->start_date && $course->start_date <= $now->toDateString() && $course->end_date && $course->end_date >= $now->toDateString();
    $isFinished = $course->end_date && $course->end_date < $now->toDateString();
    $isUpcoming = $course->start_date && $course->start_date > $now->toDateString();

    if ($isFinished) {
        $statusLabel = 'انتهى';
        $statusBg = 'bg-gray-100';
        $statusText = 'text-gray-700';
        $statusIcon = 'ri-checkbox-circle-line';
    } elseif ($isOngoing) {
        $statusLabel = 'جاري الآن';
        $statusBg = 'bg-green-100';
        $statusText = 'text-green-700';
        $statusIcon = 'ri-play-circle-fill';
    } elseif ($isUpcoming) {
        $statusLabel = 'قادم';
        $statusBg = 'bg-blue-100';
        $statusText = 'text-blue-700';
        $statusIcon = 'ri-time-line';
    } else {
        $statusLabel = 'نشط';
        $statusBg = 'bg-green-100';
        $statusText = 'text-green-700';
        $statusIcon = 'ri-check-circle-fill';
    }
  @endphp

  <!-- Hero Section -->
  <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-8 md:p-10 mb-8 border border-blue-100">
    <!-- Status Badge with Rating -->
    <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full {{ $statusBg }} {{ $statusText }} text-sm font-medium">
        <i class="{{ $statusIcon }}"></i>
        <span>{{ $statusLabel }}</span>
      </div>

      <!-- Rating Stars -->
      @if($course->total_reviews > 0)
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1">
          @for($i = 1; $i <= 5; $i++)
            @if($i <= floor($course->avg_rating))
              <i class="ri-star-fill text-yellow-400 text-lg"></i>
            @elseif($i - 0.5 <= $course->avg_rating)
              <i class="ri-star-half-fill text-yellow-400 text-lg"></i>
            @else
              <i class="ri-star-line text-gray-300 text-lg"></i>
            @endif
          @endfor
        </div>
        <span class="text-sm font-medium text-gray-700">{{ number_format($course->avg_rating, 1) }}</span>
        <span class="text-sm text-gray-500">({{ $course->total_reviews }})</span>
      </div>
      @endif
    </div>

    <!-- Title -->
    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">{{ $course->title }}</h1>

    <!-- Description -->
    @if($course->description)
      <p class="text-lg text-gray-600 leading-relaxed">{{ $course->description }}</p>
    @endif
  </div>

  <!-- Two Column Layout -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-sticky-container>

    <!-- Main Content (Left Column - 2/3) -->
    <div class="lg:col-span-2 space-y-8">

      <!-- Tabs Component -->
      @php
        $studentsWithCertificates = $course->enrollments()->whereHas('certificate')->count();
      @endphp

      <x-tabs id="course-tabs" default-tab="sessions" variant="default" color="primary">
        <x-slot name="tabs">
          <x-tabs.tab
            id="sessions"
            label="الجلسات"
            icon="ri-calendar-line"
          />
          <x-tabs.tab
            id="students"
            label="الطلاب المسجلين"
            icon="ri-user-3-line"
            :badge="$course->enrollments->count()"
          />
          <x-tabs.tab
            id="quizzes"
            label="الاختبارات"
            icon="ri-file-list-3-line"
          />
          <x-tabs.tab
            id="certificates"
            label="الشهادات"
            icon="ri-award-line"
            :badge="$studentsWithCertificates"
          />
        </x-slot>

        <x-slot name="panels">
          <x-tabs.panel id="sessions">
            @php
              $allCourseSessions = collect($upcomingSessions ?? [])->merge($pastSessions ?? []);
            @endphp

            <x-sessions.sessions-list
              :sessions="$allCourseSessions"
              view-type="teacher"
              :show-tabs="false"
              empty-message="لا توجد جلسات مجدولة بعد" />
          </x-tabs.panel>

          <x-tabs.panel id="students">
            @if($course->enrollments->count() > 0)
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطالب</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ التسجيل</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الشهادة</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($course->enrollments as $enrollment)
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex items-center">
                            <x-avatar
                              :user="$enrollment->student->user"
                              size="md"
                              userType="student"
                              :gender="$enrollment->student->gender ?? 'male'" />
                            <div class="mr-4">
                              <div class="text-sm font-medium text-gray-900">{{ $enrollment->student->user->name }}</div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {{ $enrollment->created_at->format('Y/m/d') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          @if($enrollment->certificate)
                            <div class="flex items-center gap-2">
                              <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                                <i class="ri-award-fill ml-1"></i>
                                صدرت
                              </span>
                              <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $enrollment->certificate->id]) }}"
                                 target="_blank"
                                 class="text-blue-600 hover:text-blue-800 text-xs">
                                <i class="ri-eye-line"></i>
                              </a>
                            </div>
                          @else
                            <button type="button"
                                    onclick="Livewire.dispatch('openModal', { subscriptionType: 'interactive', subscriptionId: {{ $enrollment->id }}, circleId: null })"
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                              <i class="ri-award-line ml-1"></i>
                              إصدار شهادة
                            </button>
                          @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          @php
                            $studentUser = $enrollment->student->user;
                            $conv = auth()->user()->getOrCreatePrivateConversation($studentUser);
                            $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

                            // If conversation exists, link to it, otherwise link to chats list
                            $chatUrl = $conv
                              ? route('chat', ['subdomain' => $subdomain, 'conversation' => $conv->id])
                              : route('chats', ['subdomain' => $subdomain]);
                          @endphp
                          <a href="{{ $chatUrl }}"
                             class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                            <i class="ri-message-3-line"></i>
                            مراسلة
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="ri-user-3-line text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">لا يوجد طلاب مسجلين بعد</h3>
                <p class="text-gray-600">سيظهر الطلاب المسجلين في الكورس هنا</p>
              </div>
            @endif
          </x-tabs.panel>

          <x-tabs.panel id="quizzes">
            <livewire:quizzes-widget :assignable="$course" />
          </x-tabs.panel>

          <x-tabs.panel id="certificates">
            <!-- Certificates List Section -->
            @php
              // Get all certificates for students enrolled in this course
              $certificates = \App\Models\Certificate::whereIn('student_id', $course->enrollments->pluck('student_id'))
                  ->where('certificate_type', 'interactive_course')
                  ->latest('issued_at')
                  ->get();
            @endphp

            @if($certificates->count() > 0)
              <div class="bg-green-50 rounded-lg p-4 mb-6 border border-green-200">
                <p class="text-sm text-green-800 font-medium">
                  <i class="ri-checkbox-circle-fill ml-1"></i>
                  تم إصدار {{ $certificates->count() }} شهادة للطلاب
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($certificates as $certificate)
                  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Student Info Header -->
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-4 py-3 border-b border-amber-100">
                      <div class="flex items-center gap-3">
                        <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                        <div>
                          <p class="font-bold text-gray-900 text-sm">{{ $certificate->student->name }}</p>
                          <p class="text-xs text-gray-600">{{ $certificate->certificate_number }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Certificate Details -->
                    <div class="p-4 space-y-3">
                      <!-- Issue Date -->
                      <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-calendar-line ml-2 text-amber-500"></i>
                        <span>{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
                      </div>

                      <!-- Action Buttons -->
                      <div class="flex gap-2 pt-2">
                        <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                           target="_blank"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                          <i class="ri-eye-line ml-1"></i>
                          عرض
                        </a>
                        <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                          <i class="ri-download-line ml-1"></i>
                          تحميل
                        </a>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <!-- Empty State -->
              <div class="text-center py-12">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="ri-award-line text-3xl text-amber-500"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد شهادات</h3>
                <p class="text-gray-600 text-sm mb-6">لم يتم إصدار أي شهادات للطلاب بعد</p>
                <p class="text-sm text-gray-500">يمكنك إصدار الشهادات من خلال القسم الجانبي</p>
              </div>
            @endif
          </x-tabs.panel>
        </x-slot>
      </x-tabs>

    </div>

    <!-- Sidebar (Right Column - 1/3) -->
    <div data-sticky-sidebar>
      <div class="space-y-6">
        <!-- Course Information Widget -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="ri-information-line text-primary"></i>
          معلومات الكورس
        </h3>

        <div class="space-y-4">
          <!-- Subject -->
          @if($course->subject)
            <div class="p-3 bg-blue-50 rounded-lg">
              <p class="text-xs text-gray-500 mb-0.5">المادة</p>
              <p class="font-bold text-gray-900">{{ $course->subject->name }}</p>
            </div>
          @endif

          <!-- Grade Level -->
          @if($course->gradeLevel)
            <div class="p-3 bg-green-50 rounded-lg">
              <p class="text-xs text-gray-500 mb-0.5">المرحلة</p>
              <p class="font-bold text-gray-900">{{ $course->gradeLevel->name }}</p>
            </div>
          @endif

          <!-- Students Count -->
          <div class="p-3 bg-orange-50 rounded-lg">
            <p class="text-xs text-gray-500 mb-0.5">عدد الطلاب</p>
            <p class="font-bold text-gray-900">{{ $teacherData['total_students'] ?? 0 }}/{{ $course->max_students }}</p>
          </div>

          <!-- Sessions Count -->
          <div class="p-3 bg-purple-50 rounded-lg">
            <p class="text-xs text-gray-500 mb-0.5">عدد الجلسات</p>
            <p class="font-bold text-gray-900">{{ $teacherData['total_sessions'] ?? 0 }}</p>
          </div>

          <!-- Divider -->
          <div class="border-t border-gray-200 my-4"></div>

          <!-- Course Status Badge -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">الحالة:</span>
            @php
              // Handle both enum and string status
              if ($course->status instanceof \App\Enums\InteractiveCourseStatus) {
                $statusValue = $course->status->value;
                $statusLabel = $course->status->label();
                $colorMap = [
                  'gray' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
                  'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
                  'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
                  'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800'],
                  'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
                ];
                $colors = $colorMap[$course->status->color()] ?? $colorMap['gray'];
                $config = ['label' => $statusLabel, 'bg' => $colors['bg'], 'text' => $colors['text']];
              } else {
                $statusConfig = [
                  'published' => ['label' => 'منشور', 'bg' => 'bg-green-100', 'text' => 'text-green-800'],
                  'draft' => ['label' => 'مسودة', 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
                  'active' => ['label' => 'نشط', 'bg' => 'bg-green-100', 'text' => 'text-green-800'],
                  'upcoming' => ['label' => 'قادم', 'bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
                  'completed' => ['label' => 'مكتمل', 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
                ];
                $config = $statusConfig[$course->status] ?? ['label' => $course->status, 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'];
              }
            @endphp
            <span class="{{ $config['bg'] }} {{ $config['text'] }} px-3 py-1 rounded-full text-sm font-medium">{{ $config['label'] }}</span>
          </div>

          <!-- Progress -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">التقدم:</span>
            <span class="font-medium text-gray-900">
              {{ $teacherData['completed_sessions'] ?? 0 }}/{{ $teacherData['total_sessions'] ?? 0 }} جلسة
            </span>
          </div>

          <!-- Start Date -->
          @if($course->start_date)
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">تاريخ البدء:</span>
              <span class="font-medium text-gray-900">{{ $course->start_date->format('Y/m/d') }}</span>
            </div>
          @endif

          <!-- End Date -->
          @if($course->end_date)
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">تاريخ الانتهاء:</span>
              <span class="font-medium text-gray-900">{{ $course->end_date->format('Y/m/d') }}</span>
            </div>
          @endif

          <!-- Duration -->
          @if($course->duration_weeks)
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">المدة:</span>
              <span class="font-medium text-gray-900">{{ $course->duration_weeks }} أسبوع</span>
            </div>
          @endif
        </div>
      </div>

      <!-- Quick Actions Widget -->
      <x-circle.quick-actions
        :circle="$course"
        type="group"
        view-type="teacher"
        context="interactive"
      />

      <!-- Certificates Widget -->
      <x-certificate.teacher-issue-widget type="interactive" :entity="$course" />
      </div>
    </div>
  </div>

  <!-- Certificate Modal -->
  @livewire('issue-certificate-modal')

</x-layouts.teacher>
