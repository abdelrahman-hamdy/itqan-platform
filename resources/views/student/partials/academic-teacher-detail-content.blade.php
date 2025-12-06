  <style>
    [x-cloak] { display: none !important; }
  </style>

  <!-- Breadcrumb -->
  <nav class="mb-6">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-500">
      <li><a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-gray-900">الرئيسية</a></li>
      <li>/</li>
      <li><a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-gray-900">المعلمون الأكاديميون</a></li>
      <li>/</li>
      <li class="text-gray-900">{{ $teacher->user->name }}</li>
    </ol>
  </nav>

  <!-- Alert Messages -->
  @if (session('success'))
    <div class="bg-green-50 text-green-800 px-6 py-4 rounded-xl mb-6">
      <div class="flex items-center gap-3">
        <i class="ri-checkbox-circle-line text-lg"></i>
        <p>{{ session('success') }}</p>
      </div>
    </div>
  @endif

  @if (session('error'))
    <div class="bg-red-50 text-red-800 px-6 py-4 rounded-xl mb-6">
      <div class="flex items-center gap-3">
        <i class="ri-error-warning-line text-lg"></i>
        <p>{{ session('error') }}</p>
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

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
      <!-- Schedule -->
      <x-teacher.schedule-section :teacher="$teacher" color="violet" />
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">

      <!-- Subjects & Grade Levels -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="ri-book-open-line text-violet-600"></i>
          المواد والمراحل الدراسية
        </h3>

        @if($teacher->subjects && $teacher->subjects->count() > 0)
          <div class="mb-4">
            <div class="text-sm text-gray-500 mb-2">المواد التدريسية</div>
            <div class="flex flex-wrap gap-2">
              @foreach($teacher->subjects as $subject)
                <span class="px-3 py-1.5 bg-violet-100 text-violet-800 rounded-lg text-sm font-medium">
                  {{ $subject->name }}
                </span>
              @endforeach
            </div>
          </div>
        @endif

        @if($teacher->gradeLevels && $teacher->gradeLevels->count() > 0)
          <div>
            <div class="text-sm text-gray-500 mb-2">المراحل الدراسية</div>
            <div class="flex flex-wrap gap-2">
              @foreach($teacher->gradeLevels as $gradeLevel)
                <span class="px-3 py-1.5 bg-purple-100 text-purple-800 rounded-lg text-sm font-medium">
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
    <div class="mt-8" x-data="{ pricingPeriod: 'monthly' }">
      <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">اختر الباقة المناسبة لك</h2>
        <p class="text-gray-600 mb-6">خطط تعليمية مصممة لتناسب احتياجاتك وأهدافك</p>

        <!-- Pricing Period Toggle -->
        <div class="inline-flex bg-gray-100 rounded-xl p-1 gap-1">
          <button @click="pricingPeriod = 'monthly'"
                  :class="pricingPeriod === 'monthly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-6 py-2.5 rounded-lg font-medium text-sm transition-all">
            شهري
          </button>
          <button @click="pricingPeriod = 'quarterly'"
                  :class="pricingPeriod === 'quarterly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-6 py-2.5 rounded-lg font-medium text-sm transition-all">
            ربع سنوي
          </button>
          <button @click="pricingPeriod = 'yearly'"
                  :class="pricingPeriod === 'yearly' ? 'bg-white text-violet-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-6 py-2.5 rounded-lg font-medium text-sm transition-all">
            سنوي
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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

