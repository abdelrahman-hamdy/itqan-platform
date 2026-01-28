  @php
      $isTeacher = auth()->check() && (auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher());
      $isStudent = auth()->check() && auth()->user()->isStudent();
      $viewType = $isTeacher ? 'teacher' : 'student';
  @endphp

  <style>
    [x-cloak] { display: none !important; }
  </style>

  <!-- Breadcrumb -->
  <x-ui.breadcrumb
      :items="[
          ['label' => __('student.academic_teacher_detail.breadcrumb_teachers'), 'route' => route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])],
          ['label' => $teacher->user->name, 'truncate' => true],
      ]"
      :view-type="$viewType"
  />

  <!-- Alert Messages -->
  @if (session('success'))
    <div class="bg-green-50 text-green-800 px-4 md:px-6 py-3 md:py-4 rounded-xl mb-4 md:mb-6">
      <div class="flex items-center gap-3">
        <i class="ri-checkbox-circle-line text-lg flex-shrink-0"></i>
        <p class="text-sm md:text-base">{{ session('success') }}</p>
      </div>
    </div>
  @endif

  @if (session('error'))
    <div class="bg-red-50 text-red-800 px-4 md:px-6 py-3 md:py-4 rounded-xl mb-4 md:mb-6">
      <div class="flex items-center gap-3">
        <i class="ri-error-warning-line text-lg flex-shrink-0"></i>
        <p class="text-sm md:text-base">{{ session('error') }}</p>
      </div>
    </div>
  @endif

  <!-- Profile Header -->
  <x-teacher.profile-header
    :teacher="$teacher"
    :stats="$stats"
    color="violet"
    badge-icon="ri-graduation-cap-line"
    :badge-text="__('student.academic_teacher_detail.badge_text')">
    <!-- Qualifications -->
    <x-teacher.qualifications-grid :teacher="$teacher" color="violet" />
  </x-teacher.profile-header>

  @if($isStudent && $teacher->preview_video)
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-8">
    <h3 class="font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2 text-sm md:text-base">
      <i class="ri-video-line text-violet-600"></i>
      فيديو تعريفي
    </h3>
    <video controls class="w-full rounded-lg" preload="metadata">
      <source src="{{ asset('storage/' . $teacher->preview_video) }}">
    </video>
  </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-4 md:space-y-8">
      <!-- Schedule -->
      <x-teacher.schedule-section :teacher="$teacher" color="violet" />
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-4 md:space-y-6">

      <!-- Subjects & Grade Levels -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
        <h3 class="font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2 text-sm md:text-base">
          <i class="ri-book-open-line text-violet-600"></i>
          {{ __('student.academic_teacher_detail.subjects_and_grades') }}
        </h3>

        @if($teacher->subjects && $teacher->subjects->count() > 0)
          <div class="mb-3 md:mb-4">
            <div class="text-xs md:text-sm text-gray-500 mb-2">{{ __('student.academic_teacher_detail.teaching_subjects') }}</div>
            <div class="flex flex-wrap gap-1.5 md:gap-2">
              @foreach($teacher->subjects as $subject)
                <span class="px-2.5 md:px-3 py-1 md:py-1.5 bg-violet-100 text-violet-800 rounded-lg text-xs md:text-sm font-medium">
                  {{ $subject->name }}
                </span>
              @endforeach
            </div>
          </div>
        @endif

        @if($teacher->gradeLevels && $teacher->gradeLevels->count() > 0)
          <div>
            <div class="text-xs md:text-sm text-gray-500 mb-2">{{ __('student.academic_teacher_detail.grade_levels') }}</div>
            <div class="flex flex-wrap gap-1.5 md:gap-2">
              @foreach($teacher->gradeLevels as $gradeLevel)
                <span class="px-2.5 md:px-3 py-1 md:py-1.5 bg-purple-100 text-purple-800 rounded-lg text-xs md:text-sm font-medium">
                  {{ $gradeLevel->getDisplayName() }}
                </span>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <!-- Why Choose Private Lessons -->
      <x-teacher.features-widget
        :title="__('student.academic_teacher_detail.features_title')"
        icon="ri-star-line"
        color="violet"
        :features="[
          __('student.academic_teacher_detail.features.personalized_learning'),
          __('student.academic_teacher_detail.features.custom_study_plan'),
          __('student.academic_teacher_detail.features.live_sessions'),
          __('student.academic_teacher_detail.features.homework_tracking'),
          __('student.academic_teacher_detail.features.parent_reports'),
          __('student.academic_teacher_detail.features.flexible_schedule')
        ]"
      />

    </div>
  </div>

  @if(!$isTeacher)
  <!-- Packages Section (Full Width) -->
  @if($packages->count() > 0)
    <div class="mt-6 md:mt-8" x-data="{ pricingPeriod: 'monthly' }">
      <div class="mb-6 md:mb-8 text-center">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">{{ __('student.academic_teacher_detail.choose_package') }}</h2>
        <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6">{{ __('student.academic_teacher_detail.package_description') }}</p>

        <!-- Pricing Period Toggle -->
        <div class="inline-flex bg-gray-100 rounded-xl p-1 gap-1 overflow-x-auto max-w-full">
          <button @click="pricingPeriod = 'monthly'"
                  :class="pricingPeriod === 'monthly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            {{ __('student.academic_teacher_detail.monthly') }}
          </button>
          <button @click="pricingPeriod = 'quarterly'"
                  :class="pricingPeriod === 'quarterly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            {{ __('student.academic_teacher_detail.quarterly') }}
          </button>
          <button @click="pricingPeriod = 'yearly'"
                  :class="pricingPeriod === 'yearly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            {{ __('student.academic_teacher_detail.yearly') }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        @foreach($packages as $package)
          <x-teacher.package-card
            :package="$package"
            :teacher="$teacher"
            :academy="$academy"
            color="violet"
            subscribe-route="{{ route('public.academic-packages.subscribe', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id, 'packageId' => $package->id]) }}"
            :is-popular="$loop->index === 1"
          />
        @endforeach
      </div>
    </div>
  @endif
  @endif

