  @php
      $isTeacher = auth()->check() && (auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher());
      $viewType = $isTeacher ? 'teacher' : 'student';
  @endphp

  <style>
    [x-cloak] { display: none !important; }
  </style>

  <!-- Breadcrumb -->
  <x-ui.breadcrumb
      :items="[
          ['label' => 'معلمو القرآن', 'route' => route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])],
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
    color="yellow"
    badge-icon="ri-book-read-line"
    badge-text="معلم قرآن كريم">
    <!-- Qualifications -->
    <x-teacher.qualifications-grid :teacher="$teacher" color="yellow" />
  </x-teacher.profile-header>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-4 md:space-y-8">
      <!-- Schedule -->
      <x-teacher.schedule-section :teacher="$teacher" color="yellow" />
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-4 md:space-y-6">

      @if(!$isTeacher)
      <!-- Trial Session -->
      @if($offersTrialSessions)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
          <div class="text-center mb-4 md:mb-6">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
              <i class="ri-gift-line text-2xl md:text-3xl text-green-600"></i>
            </div>
            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-2">جلسة تجريبية مجانية</h3>
            <p class="text-xs md:text-sm text-gray-600">جرّب مع المعلم وقيّم مستواك مجاناً</p>
          </div>

          @if($isAuthenticated)
            @if($existingTrialRequest)
              <div class="bg-yellow-50 text-yellow-800 rounded-xl p-3 md:p-4 text-center">
                <i class="ri-time-line text-xl md:text-2xl mb-2"></i>
                <p class="font-medium mb-1 text-sm md:text-base">لديك طلب موجود</p>
                <p class="text-xs md:text-sm">حالة الطلب: {{ $existingTrialRequest->status }}</p>
                @if($existingTrialRequest->scheduled_at)
                  <p class="text-xs mt-2">{{ \Carbon\Carbon::parse($existingTrialRequest->scheduled_at)->format('Y/m/d') }}</p>
                @endif
              </div>
            @else
              <form action="{{ route('quran-teachers.trial.submit', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}" method="POST">
                @csrf
                <button type="submit"
                   class="flex items-center justify-center gap-2 w-full min-h-[48px] bg-green-600 hover:bg-green-700 text-white text-center font-medium py-3 rounded-xl transition-colors">
                  <i class="ri-calendar-check-line"></i>
                  احجز الآن
                </button>
              </form>
            @endif
          @else
            <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
               class="flex items-center justify-center gap-2 w-full min-h-[48px] bg-green-600 hover:bg-green-700 text-white text-center font-medium py-3 rounded-xl transition-colors">
              <i class="ri-login-box-line"></i>
              سجل دخولك
            </a>
          @endif
        </div>
      @endif
      @endif

      <!-- Why Choose Individual Circles -->
      <x-teacher.features-widget
        title="مميزات الحلقات الفردية"
        icon="ri-star-line"
        color="yellow"
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

  @if(!$isTeacher)
  <!-- Packages Section (Full Width) -->
  @if($packages->count() > 0)
    <div class="mt-6 md:mt-8" x-data="{ pricingPeriod: 'monthly' }">
      <div class="mb-6 md:mb-8 text-center">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">اختر الباقة المناسبة لك</h2>
        <p class="text-sm md:text-base text-gray-600 mb-4 md:mb-6">خطط تعليمية مصممة لتناسب احتياجاتك وأهدافك</p>

        <!-- Pricing Period Toggle -->
        <div class="inline-flex bg-gray-100 rounded-xl p-1 gap-1 overflow-x-auto max-w-full">
          <button @click="pricingPeriod = 'monthly'"
                  :class="pricingPeriod === 'monthly' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            شهري
          </button>
          <button @click="pricingPeriod = 'quarterly'"
                  :class="pricingPeriod === 'quarterly' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                  class="px-4 md:px-6 py-2 md:py-2.5 min-h-[44px] rounded-lg font-medium text-xs md:text-sm transition-all whitespace-nowrap">
            ربع سنوي
          </button>
          <button @click="pricingPeriod = 'yearly'"
                  :class="pricingPeriod === 'yearly' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
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
            color="blue"
            subscribe-route="{{ route('quran-teachers.subscribe', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id, 'packageId' => $package->id]) }}"
            :is-popular="$loop->index === 1"
          />
        @endforeach
      </div>
    </div>
  @endif
  @endif

