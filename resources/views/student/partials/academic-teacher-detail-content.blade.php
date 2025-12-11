  <style>
    [x-cloak] { display: none !important; }
  </style>

  <!-- Breadcrumb -->
  <nav class="mb-4 md:mb-6 overflow-x-auto">
    <ol class="flex items-center gap-2 text-xs md:text-sm text-gray-500 whitespace-nowrap">
      <li><a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-gray-900 min-h-[44px] inline-flex items-center">الرئيسية</a></li>
      <li>/</li>
      <li><a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-gray-900">المعلمون</a></li>
      <li>/</li>
      <li class="text-gray-900 font-medium truncate max-w-[150px] md:max-w-[250px]">{{ $teacher->user->name }}</li>
    </ol>
  </nav>

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
    badge-text="معلم أكاديمي">
    <!-- Qualifications -->
    <x-teacher.qualifications-grid :teacher="$teacher" color="violet" />
  </x-teacher.profile-header>

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
          المواد والمراحل الدراسية
        </h3>

        @if($teacher->subjects && $teacher->subjects->count() > 0)
          <div class="mb-3 md:mb-4">
            <div class="text-xs md:text-sm text-gray-500 mb-2">المواد التدريسية</div>
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
            <div class="text-xs md:text-sm text-gray-500 mb-2">المراحل الدراسية</div>
            <div class="flex flex-wrap gap-1.5 md:gap-2">
              @foreach($teacher->gradeLevels as $gradeLevel)
                <span class="px-2.5 md:px-3 py-1 md:py-1.5 bg-purple-100 text-purple-800 rounded-lg text-xs md:text-sm font-medium">
                  {{ $gradeLevel->name }}
                </span>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <!-- Why Choose Private Lessons -->
      <x-teacher.features-widget
        title="مميزات الدروس الخصوصية"
        icon="ri-star-line"
        color="violet"
        :features="[
          'تعليم فردي مخصص لكل طالب',
          'خطة دراسية تناسب مستواك',
          'جلسات مباشرة عبر الإنترنت',
          'واجبات منزلية ومتابعة مستمرة',
          'تقارير دورية لولي الأمر',
          'مرونة في اختيار الأوقات'
        ]"
      />

    </div>
  </div>

  <!-- Packages Section (Full Width) -->
  @if($packages->count() > 0)
    <div class="mt-6 md:mt-8" x-data="{ pricingPeriod: 'monthly' }">
      <div class="mb-6 md:mb-8 text-center">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">اختر الباقة المناسبة لك</h2>
        <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6">خطط تعليمية مصممة لتناسب احتياجاتك وأهدافك</p>

        <!-- Pricing Period Toggle -->
        <div class="inline-flex bg-gray-100 rounded-xl p-1 gap-1 overflow-x-auto max-w-full">
          <button @click="pricingPeriod = 'monthly'"
                  :class="pricingPeriod === 'monthly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            شهري
          </button>
          <button @click="pricingPeriod = 'quarterly'"
                  :class="pricingPeriod === 'quarterly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            ربع سنوي
          </button>
          <button @click="pricingPeriod = 'yearly'"
                  :class="pricingPeriod === 'yearly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            سنوي
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

